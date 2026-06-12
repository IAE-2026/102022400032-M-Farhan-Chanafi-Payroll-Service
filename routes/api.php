<?php
use App\Http\Controllers\Api\RabbitMqProgressController;
use App\Http\Controllers\Api\SoapProgressController;
use App\Http\Controllers\Api\SsoProgressController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Middleware\CheckIaeKey;

Route::middleware([CheckIaeKey::class])->prefix('v1')->group(function () {
    Route::get('/payroll-slips', [PayrollController::class, 'index']);
    Route::get('/payroll-slips/{nip}/{tahun}/{bulan}', [PayrollController::class, 'showByPeriod']);
    Route::post('/payroll-runs', [PayrollController::class, 'runPayroll']);
    
    Route::get('/sso/token-test', [SsoProgressController::class, 'tokenTest']);
    Route::post('/soap/audit-test', [SoapProgressController::class, 'auditTest']);
    Route::post('/rabbitmq/publish-test', [RabbitMqProgressController::class, 'publishTest']);
});