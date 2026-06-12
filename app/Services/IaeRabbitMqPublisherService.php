<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IaeRabbitMqPublisherService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('IAE_CLOUD_URL', 'https://iae-sso.virtualfri.id'), '/');
    }

    public function publish(string $token, array $payload): array
    {
        $response = Http::connectTimeout(30)
            ->timeout(60)
            ->withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . '/api/v1/messages/publish', $payload);

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'body' => $response->json(),
            'raw_body' => $response->body(),
        ];
    }
}