<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CobranzasDeuda extends Model
{
    protected $table = 'cobranzas_deudas';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'nombre_cliente',
        'ci_nit',
        'telefono',
        'direccion',
        'monto_total',
        'tolerancia_centavos',
        'fecha',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'monto_total' => 'float',
        'tolerancia_centavos' => 'float',
        'fecha' => 'date:Y-m-d',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pagos()
    {
        return $this->hasMany(CobranzasDeudaPago::class, 'deuda_id');
    }
}

