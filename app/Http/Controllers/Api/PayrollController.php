<?php

namespace App\Http\Controllers\Api;

use App\Services\IaeCloudSsoService;
use App\Services\IaeSoapAuditService;
use App\Services\IaeRabbitMqPublisherService;
use App\Http\Controllers\Controller;
use App\Models\PayrollSlip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 *     title="Payroll Service API",
 *     version="1.0.0",
 *     description="Dokumentasi API untuk Payroll Service pada tugas IAE"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="iaeKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-IAE-KEY"
 * )
 */
class PayrollController extends Controller
{
    
    private function successResponse($message, $data = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => [
                'service_name' => 'Payroll-Service',
                'api_version' => 'v1'
            ]
        ], $code);
    }

    private function errorResponse($message, $errors = null, $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
/**
 * @OA\Get(
 *     path="/api/v1/payroll-slips",
 *     summary="Menampilkan seluruh slip gaji",
 *     tags={"Payroll"},
 *     security={{"iaeKey":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Payroll slips retrieved successfully"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function index()
    {
        $payrollSlips = PayrollSlip::all();

        return $this->successResponse(
            'Payroll slips retrieved successfully',
            $payrollSlips
        );
    }
/**
 * @OA\Get(
 *     path="/api/v1/payroll-slips/{nip}/{tahun}/{bulan}",
 *     summary="Menampilkan detail slip gaji berdasarkan NIP, tahun, dan bulan",
 *     tags={"Payroll"},
 *     security={{"iaeKey":{}}},
 *     @OA\Parameter(
 *         name="nip",
 *         in="path",
 *         required=true,
 *         description="NIP karyawan",
 *         @OA\Schema(type="string", example="EMP001")
 *     ),
 *     @OA\Parameter(
 *         name="tahun",
 *         in="path",
 *         required=true,
 *         description="Tahun penggajian",
 *         @OA\Schema(type="integer", example=2026)
 *     ),
 *     @OA\Parameter(
 *         name="bulan",
 *         in="path",
 *         required=true,
 *         description="Bulan penggajian",
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payroll slip retrieved successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Payroll slip not found"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function showByPeriod($nip, $tahun, $bulan)
    {
        $payrollSlip = PayrollSlip::where('nip', $nip)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        if (!$payrollSlip) {
            return $this->errorResponse('Payroll slip not found', null, 404);
        }

        return $this->successResponse(
            'Payroll slip retrieved successfully',
            $payrollSlip
        );
    }
/**
 * @OA\Post(
 *     path="/api/v1/payroll-runs",
 *     summary="Menjalankan proses payroll bulanan",
 *     tags={"Payroll"},
 *     security={{"iaeKey":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nip","employee_name","tahun","bulan","gaji_pokok","tunjangan_tetap","jumlah_hadir","jumlah_izin","jumlah_sakit","jumlah_alpha"},
 *             @OA\Property(property="nip", type="string", example="EMP001"),
 *             @OA\Property(property="employee_name", type="string", example="Farhan Chanafi"),
 *             @OA\Property(property="tahun", type="integer", example=2026),
 *             @OA\Property(property="bulan", type="integer", example=5),
 *             @OA\Property(property="gaji_pokok", type="number", example=5000000),
 *             @OA\Property(property="tunjangan_tetap", type="number", example=1000000),
 *             @OA\Property(property="jumlah_hadir", type="integer", example=20),
 *             @OA\Property(property="jumlah_izin", type="integer", example=1),
 *             @OA\Property(property="jumlah_sakit", type="integer", example=1),
 *             @OA\Property(property="jumlah_alpha", type="integer", example=2)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Payroll processed successfully"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function runPayroll(
    Request $request,
    IaeCloudSsoService $ssoService,
    IaeSoapAuditService $soapAuditService,
    IaeRabbitMqPublisherService $publisherService
) {
    $validator = Validator::make($request->all(), [
        'nip' => 'required|string',
        'employee_name' => 'required|string',
        'tahun' => 'required|integer',
        'bulan' => 'required|integer|min:1|max:12',
        'gaji_pokok' => 'required|numeric|min:0',
        'tunjangan_tetap' => 'required|numeric|min:0',
        'jumlah_hadir' => 'required|integer|min:0',
        'jumlah_izin' => 'required|integer|min:0',
        'jumlah_sakit' => 'required|integer|min:0',
        'jumlah_alpha' => 'required|integer|min:0',
    ]);

    if ($validator->fails()) {
        return $this->errorResponse(
            'Validation failed',
            $validator->errors(),
            422
        );
    }

    // 1. Ambil token dari Cloud SSO Dosen
    $tokenResponse = $ssoService->getTokenByApiKey();

    if (!$tokenResponse['success']) {
        return $this->errorResponse(
            'Failed to get token from IAE Cloud SSO',
            $tokenResponse['body'],
            $tokenResponse['status_code']
        );
    }

    $token = $ssoService->extractToken($tokenResponse['body']);

    if (!$token) {
        return $this->errorResponse(
            'Token was not found in SSO response',
            $tokenResponse['body'],
            500
        );
    }

    // 2. Decode JWT dan mapping ke role lokal
    $payload = $ssoService->decodeJwtPayload($token);
    $localUser = $ssoService->mapPayloadToLocalRole($payload);

    if (($localUser['local_role'] ?? null) !== 'HR_ADMIN') {
        return $this->errorResponse(
            'User does not have permission to run payroll',
            $localUser,
            403
        );
    }

    // 3. Proses perhitungan payroll
    $potonganPerAlpha = 100000;
    $potonganAbsensi = $request->jumlah_alpha * $potonganPerAlpha;

    $totalGaji = $request->gaji_pokok
        + $request->tunjangan_tetap
        - $potonganAbsensi;

    $payrollSlip = PayrollSlip::updateOrCreate(
        [
            'nip' => $request->nip,
            'tahun' => $request->tahun,
            'bulan' => $request->bulan,
        ],
        [
            'employee_name' => $request->employee_name,
            'gaji_pokok' => $request->gaji_pokok,
            'tunjangan_tetap' => $request->tunjangan_tetap,
            'jumlah_hadir' => $request->jumlah_hadir,
            'jumlah_izin' => $request->jumlah_izin,
            'jumlah_sakit' => $request->jumlah_sakit,
            'jumlah_alpha' => $request->jumlah_alpha,
            'potongan_absensi' => $potonganAbsensi,
            'total_gaji' => $totalGaji,
            'status' => 'Selesai',
        ]
    );

    // 4. Kirim SOAP Audit
    $auditPayload = [
        'activity_name' => 'PayrollRunCreated',
        'log_content' => [
            'service' => 'Payroll-Service',
            'activity' => 'PayrollRunCreated',
            'subject' => $localUser['subject'] ?? null,
            'local_role' => $localUser['local_role'] ?? null,
            'endpoint' => 'POST /api/v1/payroll-runs',
            'nip' => $payrollSlip->nip,
            'tahun' => $payrollSlip->tahun,
            'bulan' => $payrollSlip->bulan,
            'total_gaji' => $payrollSlip->total_gaji,
            'status' => $payrollSlip->status,
        ],
    ];

    $auditResponse = $soapAuditService->sendAudit($token, $auditPayload);

    if (!$auditResponse['success']) {
        return $this->errorResponse(
            'Failed to send SOAP audit to IAE Cloud',
            [
                'status_code' => $auditResponse['status_code'],
                'raw_response' => $auditResponse['raw_response'],
            ],
            $auditResponse['status_code']
        );
    }

    // 5. Simpan ReceiptNumber dari SOAP ke database
    $payrollSlip->soap_receipt_number = $auditResponse['receipt_number'];
    $payrollSlip->save();

    // 6. Publish event ke RabbitMQ
    $eventPayload = [
        'message' => [
            'event' => 'payroll.processed',
            'service' => 'Payroll-Service',
            'team_id' => env('TEAM_ID', 'TEAM-10'),
            'subject' => $localUser['subject'] ?? null,
            'local_role' => $localUser['local_role'] ?? null,
            'activity' => 'PayrollRunCreated',
            'endpoint' => 'POST /api/v1/payroll-runs',
            'nip' => $payrollSlip->nip,
            'tahun' => $payrollSlip->tahun,
            'bulan' => $payrollSlip->bulan,
            'total_gaji' => $payrollSlip->total_gaji,
            'status' => $payrollSlip->status,
            'soap_receipt_number' => $auditResponse['receipt_number'],
        ],
    ];

    $publishResponse = $publisherService->publish($token, $eventPayload);

    if (!$publishResponse['success']) {
        return $this->errorResponse(
            'Failed to publish payroll event to IAE RabbitMQ',
            [
                'status_code' => $publishResponse['status_code'],
                'body' => $publishResponse['body'],
            ],
            $publishResponse['status_code']
        );
    }

    $payrollSlip->refresh();

    $responseData = $payrollSlip->toArray();
    $responseData['cloud_integration'] = [
        'sso_subject' => $localUser['subject'] ?? null,
        'local_role' => $localUser['local_role'] ?? null,
        'soap_status' => $auditResponse['soap_status'],
        'soap_receipt_number' => $auditResponse['receipt_number'],
        'rabbitmq_status' => $publishResponse['body']['status'] ?? null,
        'rabbitmq_exchange' => $publishResponse['body']['exchange'] ?? null,
    ];

    return $this->successResponse(
        'Payroll processed successfully with SSO, SOAP Audit, and RabbitMQ',
        $responseData,
        201
    );
}
}