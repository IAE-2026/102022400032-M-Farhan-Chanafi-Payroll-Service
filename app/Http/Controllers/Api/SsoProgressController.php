<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IaeCloudSsoService;

class SsoProgressController extends Controller
{
    public function tokenTest(IaeCloudSsoService $ssoService)
    {
        $tokenResponse = $ssoService->getTokenByApiKey();

        if (!$tokenResponse['success']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to request token from IAE Cloud SSO',
                'errors' => $tokenResponse['body'],
                'meta' => [
                    'service_name' => 'Payroll-Service',
                    'api_version' => 'v1',
                    'sso_status_code' => $tokenResponse['status_code'],
                ],
            ], $tokenResponse['status_code']);
        }

        $token = $ssoService->extractToken($tokenResponse['body']);
        $payload = $ssoService->decodeJwtPayload($token);
        $localUser = $ssoService->mapPayloadToLocalRole($payload);

        return response()->json([
            'status' => 'success',
            'message' => 'SSO token retrieved successfully from IAE Cloud',
            'data' => [
                'token_received' => $token !== null,
                'token_preview' => $ssoService->maskToken($token),
                'payload' => $payload,
                'local_user' => $localUser,
            ],
            'meta' => [
                'service_name' => 'Payroll-Service',
                'api_version' => 'v1',
                'sso_status_code' => $tokenResponse['status_code'],
            ],
        ], 200);
    }
}