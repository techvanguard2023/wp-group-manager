<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Lista todos os contatos com seus respectivos grupos.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->has('search')) {
            $search = $request->search;
            // Note: Since fields are encrypted, searching directly is tricky.
            // But let's assume we search by exact phone hash if it looks like a phone.
            // Or just filter in memory for now if small enough.
            // Actually, for now let's just use with('groups') and filtering by group.
        }

        if ($request->has('group_id')) {
            $query->whereHas('groups', function ($q) use ($request) {
                $q->where('wa_group_id', $request->group_id)
                  ->orWhere('id', $request->group_id);
            });
        }

        $contacts = $query->with('groups')
                         ->orderBy('created_at', 'desc')
                         ->paginate($request->get('limit', 15));

        return response()->json($contacts);
    }

    /**
     * Insere um novo contato no banco de dados.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'notes' => 'nullable|string',
            'wa_group_id' => 'nullable|string' // O JID do grupo do WhatsApp
        ]);

        $contact = \App\Models\Contact::create($request->only(['phone', 'name', 'email', 'notes']));

        if ($request->filled('wa_group_id')) {
            $group = \App\Models\WhatsappGroup::where('wa_group_id', $request->wa_group_id)->first();
            
            if ($group) {
                // Relaciona o contato na tabela group_contacts
                if (!$contact->groups()->where('whatsapp_group_id', $group->id)->exists()) {
                    $contact->groups()->attach($group->id, ['added_at' => now()]);
                    $group->increment('current_members');
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $contact->load('groups')
        ], 201);
    }

    /**
     * Busca um contato pelo número de telefone.
     * 
     * @param  string  $phone
     * @return \Illuminate\Http\JsonResponse
     */
    public function findByPhone($phone)
    {
        $phoneHash = hash_hmac('sha256', $phone, config('app.key'));
        
        $contact = Contact::where('phone_hash', $phoneHash)
                         ->with('groups')
                         ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contato não encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $contact
        ]);
    }

    /**
     * Exibe os detalhes de um contato específico.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $contact = Contact::with('groups')->findOrFail($id);
        return response()->json($contact);
    }
}
