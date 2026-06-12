<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IaeCloudSsoService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('IAE_CLOUD_URL', 'https://iae-sso.virtualfri.id'), '/');
    }

    public function getTokenByApiKey(): array{
    $response = Http::connectTimeout(30)
        ->timeout(60)
        ->acceptJson()
        ->post($this->baseUrl . '/api/v1/auth/token', [
            'api_key' => env('IAE_CLOUD_API_KEY'),
        ]);

    return [
        'success' => $response->successful(),
        'status_code' => $response->status(),
        'body' => $response->json(),
    ];
}

    public function extractToken(array $body): ?string
    {
        return $body['access_token']
            ?? $body['token']
            ?? $body['data']['access_token']
            ?? $body['data']['token']
            ?? null;
    }

    public function decodeJwtPayload(?string $jwt): ?array
    {
        if (!$jwt) {
            return null;
        }

        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = $parts[1];

        $payload = str_replace(['-', '_'], ['+', '/'], $payload);

        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);

        $decoded = base64_decode($payload);

        return json_decode($decoded, true);
    }
    
    public function mapPayloadToLocalRole(?array $payload): ?array
{
    if (!$payload) {
        return null;
    }

    $subject = $payload['sub'] ?? null;

    $roleMapping = [
        'KEY-MHS-116' => 'HR_ADMIN',
        'warga19@ktp.iae.id' => 'HR_ADMIN',
    ];

    return [
        'subject' => $subject,
        'local_role' => $roleMapping[$subject] ?? 'EMPLOYEE',
    ];
}

    public function maskToken(?string $jwt): ?string
    {
        if (!$jwt) {
            return null;
        }

        return substr($jwt, 0, 20) . '...';
    }
}