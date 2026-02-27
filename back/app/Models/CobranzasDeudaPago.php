<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CobranzasDeudaPago extends Model
{
    protected $table = 'cobranzas_deuda_pagos';

    protected $fillable = [
        'deuda_id',
        'user_id',
        'monto',
        'fecha_hora',
        'metodo_pago',
        'considerar_en_cobranza',
        'nro_pago',
        'observacion',
        'comprobante_path',
    ];

    protected $casts = [
        'monto' => 'float',
        'fecha_hora' => 'datetime',
        'considerar_en_cobranza' => 'boolean',
    ];

    public function deuda()
    {
        return $this->belongsTo(CobranzasDeuda::class, 'deuda_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

