<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'employee_name',
        'tahun',
        'bulan',
        'gaji_pokok',
        'tunjangan_tetap',
        'jumlah_hadir',
        'jumlah_izin',
        'jumlah_sakit',
        'jumlah_alpha',
        'potongan_absensi',
        'total_gaji',
        'status',
        'soap_receipt_number',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'gaji_pokok' => 'float',
        'tunjangan_tetap' => 'float',
        'jumlah_hadir' => 'integer',
        'jumlah_izin' => 'integer',
        'jumlah_sakit' => 'integer',
        'jumlah_alpha' => 'integer',
        'potongan_absensi' => 'float',
        'total_gaji' => 'float',
        'soap_receipt_number' => 'string',
    ];
}