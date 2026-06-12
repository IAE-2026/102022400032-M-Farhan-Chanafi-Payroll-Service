<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IaeCloudSsoService;
use App\Services\IaeRabbitMqPublisherService;
use Throwable;

class RabbitMqProgressController extends Controller
{
    public function publishTest(
        IaeCloudSsoService $ssoService,
        IaeRabbitMqPublisherService $publisherService
    ) {
        try {
            $tokenResponse = $ssoService->getTokenByApiKey();

            if (!$tokenResponse['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get token from IAE Cloud SSO',
                    'errors' => $tokenResponse['body'],
                    'meta' => [
                        'service_name' => 'Payroll-Service',
                        'api_version' => 'v1',
                        'sso_status_code' => $tokenResponse['status_code'],
                    ],
                ], $tokenResponse['status_code']);
            }

            $token = $ssoService->extractToken($tokenResponse['body']);

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token was not found in SSO response',
                    'errors' => $tokenResponse['body'],
                ], 500);
            }

            $eventPayload = [
        'message' => [
        'event' => 'payroll.processed',
        'service' => 'Payroll-Service',
        'team_id' => env('TEAM_ID', 'TEAM-10'),
        'activity' => 'PayrollRunCreated',
        'description' => 'Progress test RabbitMQ untuk transaksi payroll',
        'endpoint' => 'POST /api/v1/payroll-runs',
        'nip' => 'EMP001',
        'tahun' => 2026,
        'bulan' => 6,
        'total_gaji' => 5800000,
        'status' => 'Selesai',
    ],
];

            $publishResponse = $publisherService->publish($token, $eventPayload);

            if (!$publishResponse['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to publish event to IAE RabbitMQ',
                    'errors' => [
                        'status_code' => $publishResponse['status_code'],
                        'body' => $publishResponse['body'],
                        'raw_body' => $publishResponse['raw_body'],
                    ],
                    'meta' => [
                        'service_name' => 'Payroll-Service',
                        'api_version' => 'v1',
                    ],
                ], $publishResponse['status_code']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'RabbitMQ event published successfully to IAE Cloud',
                'data' => [
                    'event' => 'payroll.processed',
                    'routing_key' => 'payroll.processed',
                    'publish_response' => $publishResponse['body'],
                ],
                'meta' => [
                    'service_name' => 'Payroll-Service',
                    'api_version' => 'v1',
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error while publishing RabbitMQ event',
                'errors' => [
                    'detail' => $e->getMessage(),
                ],
                'meta' => [
                    'service_name' => 'Payroll-Service',
                    'api_version' => 'v1',
                ],
            ], 500);
        }
    }
}