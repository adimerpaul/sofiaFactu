<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = [
        'venta_id',
        'pedido_id',
        'cliente_id',
        'user_id',
        'tipo_pago',
        'metodo_pago',
        'monto',
        'fecha_hora',
        'observacion',
        'considerar_en_cobranza',
        'nro_pago',
        'comprobante_path',
        'latitud',
        'longitud',
    ];

    protected $casts = [
        'monto' => 'float',
        'fecha_hora' => 'datetime',
        'considerar_en_cobranza' => 'boolean',
        'latitud' => 'float',
        'longitud' => 'float',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
