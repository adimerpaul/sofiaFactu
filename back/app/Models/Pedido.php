<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model {
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'fecha',
        'hora',
        'estado',
        'tipo_pago',
        'tipo_pedido',
        'total',
        'observaciones',
        'comentario_visita'
    ];

    public function detalles() {
        return $this->hasMany(PedidoDetalle::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function cliente() {
        return $this->belongsTo(Cliente::class);
    }
    protected $appends = ['textDetalle'];
    public function getTextDetalleAttribute() {
        return $this->detalles->map(function ($detalle) {
            return $detalle->producto->nombre . ' (' . $detalle->cantidad . ')';
        })->implode(', ');
    }
}
