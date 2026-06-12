<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IaeSoapAuditService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('IAE_CLOUD_URL', 'https://iae-sso.virtualfri.id'), '/');
    }

    public function sendAudit(string $token, array $payload): array
    {
        $activityName = $payload['activity_name'] ?? 'PayrollRunCreated';

        $logContent = json_encode($payload['log_content'] ?? [], JSON_UNESCAPED_SLASHES);

        $xml = $this->buildSoapEnvelope(
            env('TEAM_ID', 'TEAM-116'),
            $activityName,
            $logContent
        );

        $response = Http::connectTimeout(30)
            ->timeout(60)
            ->withToken($token)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=UTF-8',
                'Accept' => 'application/xml',
            ])
            ->send('POST', $this->baseUrl . '/soap/v1/audit', [
                'body' => $xml,
            ]);

        $body = $response->body();

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'raw_response' => $body,
            'receipt_number' => $this->extractTagValue($body, 'ReceiptNumber'),
            'soap_status' => $this->extractTagValue($body, 'Status'),
        ];
    }

    private function buildSoapEnvelope(string $teamId, string $activityName, string $logContent): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">'
            . '<soap:Body>'
            . '<iae:AuditRequest>'
            . '<iae:TeamID>' . htmlspecialchars($teamId, ENT_XML1, 'UTF-8') . '</iae:TeamID>'
            . '<iae:ActivityName>' . htmlspecialchars($activityName, ENT_XML1, 'UTF-8') . '</iae:ActivityName>'
            . '<iae:LogContent><![CDATA[' . $logContent . ']]></iae:LogContent>'
            . '</iae:AuditRequest>'
            . '</soap:Body>'
            . '</soap:Envelope>';
    }

    private function extractTagValue(string $xml, string $tagName): ?string
    {
        $pattern = '/<' . $tagName . '>(.*?)<\/' . $tagName . '>/';

        if (preg_match($pattern, $xml, $matches)) {
            return $matches[1];
        }

        $patternWithNamespace = '/<[^:]+:' . $tagName . '>(.*?)<\/[^:]+:' . $tagName . '>/';

        if (preg_match($patternWithNamespace, $xml, $matches)) {
            return $matches[1];
        }

        return null;
    }
}