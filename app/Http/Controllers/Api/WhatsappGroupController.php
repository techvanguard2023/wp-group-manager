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

    // CRIAÇÃO AUTOMÁTICA: adiciona contato e gerencia grupos por ID de categoria
    public function addContact(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'selectedCategories' => 'nullable|array',
            'selectedCategories.*' => 'integer|exists:categories,id',
        ]);

        $categoryIds = $request->selectedCategories ?? [];
        
        // Se não houver categorias selecionadas, poderíamos ter uma padrão, 
        // mas aqui vamos assumir que o frontend deve enviar ao menos uma se quiser o comportamento.
        if (empty($categoryIds)) {
             return response()->json(['success' => false, 'message' => 'Nenhuma categoria selecionada.'], 422);
        }

        $joinedGroups = [];

        foreach ($categoryIds as $categoryId) {
            try {
                $category = \App\Models\Category::find($categoryId);
                if (!$category) continue;

                $group = DB::transaction(function () use ($request, $category) {
                    // Busca contato ou cria
                    $phoneHash = hash_hmac('sha256', $request->phone, config('app.key'));
                    $contact = Contact::where('phone_hash', $phoneHash)->first();

                    if (!$contact) {
                        $contact = Contact::create([
                            'phone' => $request->phone,
                            'name' => $request->name,
                            'email' => $request->email
                        ]);
                    }

                    // Busca grupo ativo com vaga para ESTA categoria
                    $group = WhatsappGroup::where('is_active', true)
                                         ->where('category_id', $category->id)
                                         ->whereColumn('current_members', '<', 'max_members')
                                         ->orderBy('id')
                                         ->first();

                    if (!$group) {
                        // Cria novo grupo no Evolution API
                        $baseUrlName = env('GROUP_NAME', 'RDM');
                        $categoryCount = WhatsappGroup::where('category_id', $category->id)->count() + 1;
                        $groupName = "{$baseUrlName} - {$category->name} #{$categoryCount}";
                        
                        $evolutionGroup = $this->evolution->createGroup($groupName, [$contact->phone]);
                        
                        if (!$evolutionGroup) return null;

                        $waGroupId = $evolutionGroup['id'] ?? $evolutionGroup['jid'] ?? 
                                     $evolutionGroup['response']['id'] ?? $evolutionGroup['response']['jid'] ?? '';
                        
                        if (empty($waGroupId)) return null;

                        // Configurações e link
                        $this->evolution->updateGroupPicture($waGroupId, 'https://www.radardosmarketplaces.com.br/assets/logo-t1N8LHZF.png');
                        $this->evolution->updateGroupSetting($waGroupId, 'announcement');
                        $this->evolution->updateGroupSetting($waGroupId, 'locked');
                        
                        // Atualiza a descrição do grupo com a descrição da categoria
                        if (!empty($category->description)) {
                            $this->evolution->updateGroupDescription($waGroupId, $category->description);
                        }

                        sleep(2);
                        $inviteLink = $this->evolution->getInviteLink($waGroupId);

                        $group = WhatsappGroup::create([
                            'name' => $groupName,
                            'wa_group_id' => $waGroupId,
                            'invite_link' => $inviteLink,
                            'category_id' => $category->id,
                            'current_members' => 1,
                            'max_members' => 1024,
                            'is_active' => true,
                        ]);
                    } else {
                        if (!$group->contacts()->where('contact_id', $contact->id)->exists()) {
                            if ($this->evolution->addParticipant($group->wa_group_id, [$contact->phone])) {
                                $group->increment('current_members');
                            }
                        }
                    }

                    // Vincula
                    if ($group && !$group->contacts()->where('contact_id', $contact->id)->exists()) {
                        $group->contacts()->attach($contact->id);
                    }

                    return $group;
                });

                if ($group) {
                    $joinedGroups[] = [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'group_name' => $group->name,
                        'invite_link' => $group->invite_link
                    ];
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erro no category ID {$categoryId}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'joined_groups' => $joinedGroups
        ]);
    }

    // Para n8n: grupos ativos para envio
    public function activeGroups()
    {
        $groups = WhatsappGroup::with('category')
                               ->where('is_active', true)
                               ->get()
                               ->map(function ($group) {
                                   return [
                                    'id' => $group->id,
                                    'name' => $group->name,
                                    'wa_group_id' => $group->wa_group_id,
                                    'invite_link' => $group->invite_link,
                                    'category_id' => $group->category_id,
                                    'category_name' => $group->category->name ?? 'N/A',
                                    'current_members' => $group->current_members,
                                    'max_members' => $group->max_members,
                                    'is_active' => $group->is_active
                                ];
                               });

        return response()->json($groups);
    }
}
