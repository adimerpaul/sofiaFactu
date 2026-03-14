<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapaZonaPoligono extends Model
{
    protected $table = 'mapa_zona_poligonos';

    protected $fillable = [
        'nombre',
        'tipo',
        'color',
        'pedido_zona_id',
        'coordenadas',
        'orden',
        'activo',
    ];

    protected $casts = [
        'tipo' => 'integer',
        'coordenadas' => 'array',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function pedidoZona(): BelongsTo
    {
        return $this->belongsTo(PedidoZona::class, 'pedido_zona_id');
    }
}
