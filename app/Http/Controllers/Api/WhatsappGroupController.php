<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\WhatsappGroup;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\EvolutionService;

class WhatsappGroupController extends Controller
{
    protected $evolution;

    public function __construct(EvolutionService $evolution)
    {
        $this->evolution = $evolution;
    }
    public function index()
    {
        return WhatsappGroup::withCount('contacts')
                           ->orderBy('id')
                           ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:whatsapp_groups',
            'wa_group_id' => 'required|string|unique:whatsapp_groups',
            'invite_link' => 'nullable|url',
        ]);

        return WhatsappGroup::create($validated);
    }

    // CRIAÇÃO AUTOMÁTICA: adiciona contato e gerencia grupos
    public function addContact(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email',
        ]);

        $group = DB::transaction(function () use ($request) {
            // Busca contato ou cria (usando hash para busca)
            $phoneHash = hash_hmac('sha256', $request->phone, config('app.key'));
            $contact = Contact::where('phone_hash', $phoneHash)->first();

            if (!$contact) {
                $contact = Contact::create([
                    'phone' => $request->phone,
                    'name' => $request->name,
                    'email' => $request->email
                ]);
            }

            // Busca grupo ativo com vaga
            $group = WhatsappGroup::where('is_active', true)
                                 ->whereColumn('current_members', '<', 'max_members')
                                 ->orderBy('id')
                                 ->first();

            if (!$group) {
                // Cria novo grupo no Evolution API (precisa de ao menos 1 participante)
                $baseUrlName = env('GROUP_NAME', 'Shopee Achadinhos');
                $groupName = $baseUrlName . " #" . (WhatsappGroup::count() + 1);
                
                $evolutionGroup = $this->evolution->createGroup($groupName, [$contact->phone]);
                
                if (!$evolutionGroup) {
                    throw new \Exception("Falha ao criar grupo na API Evolution.");
                }

                \Illuminate\Support\Facades\Log::info('Evolution createGroup full response: ' . json_encode($evolutionGroup));

                // Tenta pegar o JID (V1 usa 'jid', V2 costuma usar 'id' ou estar dentro de 'response')
                $waGroupId = $evolutionGroup['id'] ?? $evolutionGroup['jid'] ?? 
                             $evolutionGroup['response']['id'] ?? $evolutionGroup['response']['jid'] ?? '';
                
                \Illuminate\Support\Facades\Log::info("Extracted WA Group ID: " . $waGroupId);
                
                if (empty($waGroupId)) {
                    \Illuminate\Support\Facades\Log::warning('JID não encontrado na resposta da Evolution API: ' . json_encode($evolutionGroup));
                    throw new \Exception("ID do grupo não retornado pela API Evolution.");
                } else {
                    
                    // Altera a imagem do grupo
                    $pictureResult = $this->evolution->updateGroupPicture($waGroupId, 'https://www.radardosmarketplaces.com.br/assets/logo-t1N8LHZF.png');
                    \Illuminate\Support\Facades\Log::info('Update picture result: ' . json_encode($pictureResult));

                    // Configura o grupo: apenas admins enviam mensagens
                    // Na V2 o action 'announcement' já ativa a restrição
                    $settingResult = $this->evolution->updateGroupSetting($waGroupId, 'announcement');
                    \Illuminate\Support\Facades\Log::info('Update setting result: ' . json_encode($settingResult));

                    // Bloqueia o grupo
                    $lockResult = $this->evolution->updateGroupSetting($waGroupId, 'locked');
                    \Illuminate\Support\Facades\Log::info('Update lock result: ' . json_encode($lockResult));
                }

                // Pequeno delay para garantir que o grupo esteja pronto na API
                sleep(2);

                $inviteLink = $this->evolution->getInviteLink($waGroupId);
                \Illuminate\Support\Facades\Log::info('Fetched invite link: ' . $inviteLink);

                $group = WhatsappGroup::create([
                    'name' => $groupName,
                    'wa_group_id' => $waGroupId,
                    'invite_link' => $inviteLink,
                    'current_members' => 0,
                ]);
            }

            // Verifica se o contato já está no banco para este grupo
            if (!$group->contacts()->where('contact_id', $contact->id)->exists()) {
                
                // Só chama addParticipant se o grupo já existia (se for novo, já foi add no catch acima)
                // Usamos a data de criação do grupo como critério simples ou verificamos se wa_group_id acabou de ser preenchido
                // Melhor: se o grupo foi criado agora na transação, ele não tinha registros na pivot.
                
                // Para simplificar e ser robusto: tentamos adicionar sempre na API (a API costuma ignorar se já estiver)
                // ou comparamos se o grupo acabou de ser criado.
                
                // Vamos adicionar apenas se o grupo já existia anteriormente na busca
                // mas para garantir, a Evolution API ignora duplicados no addParticipant.
                $this->evolution->addParticipant($group->wa_group_id, $contact->phone);
                
                $group->contacts()->attach($contact->id);
                $group->increment('current_members');
            }

            return $group;
        });

        return response()->json([
            'success' => true,
            'group' => WhatsappGroup::withCount('contacts')->find($group->id)
        ]);
    }

    // Para n8n: grupos ativos para envio
    public function activeGroups()
    {
        return WhatsappGroup::where('is_active', true)
                           ->pluck('wa_group_id')
                           ->values();
    }
}
