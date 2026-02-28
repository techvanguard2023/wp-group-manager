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
    public function index()
    {
        $contacts = Contact::with('groups')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($contacts);
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
