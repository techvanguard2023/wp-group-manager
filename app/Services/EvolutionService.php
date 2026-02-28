<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('EVO_URL_API'), '/');
        $this->apiKey = env('EVO_API_KEY');
        $this->instance = env('EVO_INSTANCE_NAME');
    }

    public function createGroup(string $name, array $participants = [])
    {
        $url = "{$this->baseUrl}/group/create/{$this->instance}";
        
        // Limpa os números dos participantes
        $cleanParticipants = array_map(function($phone) {
            return preg_replace('/\D/', '', $phone);
        }, $participants);

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, [
                'subject' => $name,
                'participants' => $cleanParticipants
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Evolution API error (createGroup) - Status: {$response->status()} - URL: {$url} - Response: " . $response->body());
            
            // Tentativa automática com /v2 se falhar com 404 e não tiver /v2 no baseUrl
            if ($response->status() === 404 && strpos($this->baseUrl, '/v2') === false) {
                $v2Url = "{$this->baseUrl}/v2/group/create/{$this->instance}";
                Log::info("Tentando criar grupo via V2: {$v2Url}");
                
                $v2Response = Http::withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])->post($v2Url, [
                    'subject' => $name,
                    'participants' => $participants
                ]);

                if ($v2Response->successful()) {
                    return $v2Response->json();
                }
                
                Log::error("Evolution API V2 error (createGroup) - Status: {$v2Response->status()} - Response: " . $v2Response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Evolution API exception (createGroup) - URL: {$url} - Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Adiciona um participante a um grupo.
     */
    public function addParticipant(string $groupId, string $phone)
    {
        // Remove caracteres não numéricos do telefone para o WhatsApp JID
        $cleanPhone = preg_replace('/\D/', '', $phone);
        $url = "{$this->baseUrl}/group/updateParticipant/{$this->instance}";
        
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, [
                'groupJid' => $groupId,
                'action' => 'add',
                'participants' => [$cleanPhone]
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Evolution API error (addParticipant) - Status: {$response->status()} - Response: " . $response->body());
            
            // Tentativa V2
            if ($response->status() === 404 && strpos($this->baseUrl, '/v2') === false) {
                $v2Url = "{$this->baseUrl}/v2/group/updateParticipant/{$this->instance}";
                Log::info("Tentando adicionar participante via V2: {$v2Url}");
                
                $v2Response = Http::withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])->post($v2Url, [
                    'groupJid' => $groupId,
                    'action' => 'add',
                    'participants' => [$cleanPhone]
                ]);

                if ($v2Response->successful()) {
                    return $v2Response->json();
                }
                Log::error("Evolution API V2 error (addParticipant) - Status: {$v2Response->status()} - Response: " . $v2Response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Evolution API exception (addParticipant): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém o link de convite de um grupo.
     */
    public function getInviteLink(string $groupId)
    {
        $url = "{$this->baseUrl}/group/inviteCode/{$this->instance}";
        
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey
            ])->get($url, [
                'groupJid' => $groupId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $code = $data['inviteCode'] ?? $data['invite_code'] ?? 
                        $data['response']['inviteCode'] ?? $data['response']['invite_code'] ?? null;
                
                if (!$code) {
                    Log::warning('Código de convite não encontrado na resposta: ' . json_encode($data));
                }
                
                return $code ? "https://chat.whatsapp.com/{$code}" : null;
            }

            Log::error("Evolution API error (getInviteLink) - Status: {$response->status()} - Response: " . $response->body());

            // Tentativa V2
            if ($response->status() === 404 && strpos($this->baseUrl, '/v2') === false) {
                $v2Url = "{$this->baseUrl}/v2/group/inviteCode/{$this->instance}";
                Log::info("Tentando obter link via V2: {$v2Url}");
                
                $v2Response = Http::withHeaders([
                    'apikey' => $this->apiKey
                ])->get($v2Url, [
                    'groupJid' => $groupId
                ]);

                if ($v2Response->successful()) {
                    $data = $v2Response->json();
                    $code = $data['inviteCode'] ?? $data['invite_code'] ?? null;
                    return $code ? "https://chat.whatsapp.com/{$code}" : null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Evolution API exception (getInviteLink): " . $e->getMessage());
            return null;
        }
    }
}
