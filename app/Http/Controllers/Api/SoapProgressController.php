<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IaeCloudSsoService;
use App\Services\IaeSoapAuditService;
use Throwable;

class SoapProgressController extends Controller
{
    public function auditTest(
        IaeCloudSsoService $ssoService,
        IaeSoapAuditService $soapAuditService
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

            $auditPayload = [
                'activity_name' => 'PayrollRunCreated',
                'log_content' => [
                    'service' => 'Payroll-Service',
                    'activity' => 'PayrollRunCreated',
                    'description' => 'Progress test SOAP Audit untuk transaksi kritis payroll',
                    'endpoint' => 'POST /api/v1/payroll-runs',
                    'nip' => 'EMP001',
                    'tahun' => 2026,
                    'bulan' => 6,
                    'total_gaji' => 5800000,
                    'status' => 'Selesai',
                ],
            ];

            $auditResponse = $soapAuditService->sendAudit($token, $auditPayload);

            if (!$auditResponse['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send SOAP audit to IAE Cloud',
                    'errors' => [
                        'status_code' => $auditResponse['status_code'],
                        'raw_response' => $auditResponse['raw_response'],
                    ],
                    'meta' => [
                        'service_name' => 'Payroll-Service',
                        'api_version' => 'v1',
                    ],
                ], $auditResponse['status_code']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'SOAP audit sent successfully to IAE Cloud',
                'data' => [
                    'soap_status' => $auditResponse['soap_status'],
                    'receipt_number' => $auditResponse['receipt_number'],
                    'activity_name' => 'PayrollRunCreated',
                ],
                'meta' => [
                    'service_name' => 'Payroll-Service',
                    'api_version' => 'v1',
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error while sending SOAP audit',
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