<?php

namespace App\Http\Controllers;

use App\Models\CompraDetalle;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuxiliarCamaraController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAuxiliarAccess($request);

        $data = $request->validate([
            'fecha' => 'nullable|date',
            'vendedor_id' => 'nullable|integer|exists:users,id',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'usuario_camion_id' => 'nullable|integer|exists:users,id',
            'pedido_zona_id' => 'nullable|integer|exists:pedido_zonas,id',
            'tipo' => 'nullable|string|in:TODOS,NORMAL,POLLO,RES,CERDO',
            'auxiliar_estado' => 'nullable|string|in:TODOS,PENDIENTE,HECHO,MODIFICADO',
            'search' => 'nullable|string|max:120',
        ]);

        $fecha = $data['fecha'] ?? now()->toDateString();
        $tipo = strtoupper((string) ($data['tipo'] ?? 'TODOS'));
        $auxEstado = strtoupper((string) ($data['auxiliar_estado'] ?? 'TODOS'));
        $search = trim((string) ($data['search'] ?? ''));

        $query = Pedido::query()
            ->with([
                'cliente:id,nombre,direccion,telefono,territorio,codcli,ci',
                'user:id,name',
                'usuarioCamion:id,name,placa',
                'auxiliarUser:id,name',
                'zona:id,nombre,color,orden',
                'venta:id,total,estado',
                'detalles:id,pedido_id,producto_id,cantidad,precio,total,observacion_detalle',
                'detalles.producto:id,codigo,nombre,tipo,imagen',
            ])
            ->where('tipo_pedido', 'REALIZAR_PEDIDO')
            ->where('estado', 'Enviado')
            ->whereDate('fecha', $fecha)
            ->when(!empty($data['vendedor_id']), fn ($q) => $q->where('user_id', (int) $data['vendedor_id']))
            ->when(!empty($data['cliente_id']), fn ($q) => $q->where('cliente_id', (int) $data['cliente_id']))
            ->when(!empty($data['usuario_camion_id']), fn ($q) => $q->where('usuario_camion_id', (int) $data['usuario_camion_id']))
            ->when(!empty($data['pedido_zona_id']), fn ($q) => $q->where('pedido_zona_id', (int) $data['pedido_zona_id']))
            ->when($auxEstado !== 'TODOS', fn ($q) => $q->where('auxiliar_estado', $auxEstado))
            ->when($tipo !== 'TODOS', function ($q) use ($tipo) {
                if ($tipo === 'NORMAL') $q->where('contiene_normal', true);
                if ($tipo === 'POLLO') $q->where('contiene_pollo', true);
                if ($tipo === 'RES') $q->where('contiene_res', true);
                if ($tipo === 'CERDO') $q->where('contiene_cerdo', true);
            })
            ->orderBy('hora')
            ->orderBy('id');

        $items = $query->get();
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $items = $items->filter(function (Pedido $p) use ($needle) {
                $stack = mb_strtolower(implode(' ', [
                    (string) $p->id,
                    $p->cliente?->nombre ?? '',
                    $p->cliente?->codcli ?? '',
                    $p->cliente?->ci ?? '',
                    $p->cliente?->direccion ?? '',
                    $p->user?->name ?? '',
                    $p->usuarioCamion?->name ?? '',
                    $p->zona?->nombre ?? '',
                ]));
                return str_contains($stack, $needle);
            })->values();
        }

        $rows = $items->map(function (Pedido $pedido) {
            return [
                'id' => $pedido->id,
                'fecha' => (string) $pedido->fecha,
                'hora' => (string) ($pedido->hora ?? ''),
                'estado' => $pedido->estado,
                'auxiliar_estado' => $pedido->auxiliar_estado ?? 'PENDIENTE',
                'auxiliar_observacion' => $pedido->auxiliar_observacion,
                'auxiliar_hecho_at' => optional($pedido->auxiliar_hecho_at)->toDateTimeString(),
                'venta_generada' => (bool) $pedido->venta_generada,
                'venta_id' => $pedido->venta_id,
                'total' => (float) $pedido->total,
                'tipo_pago' => $pedido->tipo_pago,
                'contiene_normal' => (bool) $pedido->contiene_normal,
                'contiene_pollo' => (bool) $pedido->contiene_pollo,
                'contiene_res' => (bool) $pedido->contiene_res,
                'contiene_cerdo' => (bool) $pedido->contiene_cerdo,
                'cliente' => [
                    'id' => $pedido->cliente?->id,
                    'nombre' => $pedido->cliente?->nombre,
                    'codcli' => $pedido->cliente?->codcli,
                    'ci' => $pedido->cliente?->ci,
                    'direccion' => $pedido->cliente?->direccion,
                    'telefono' => $pedido->cliente?->telefono,
                    'territorio' => $pedido->cliente?->territorio,
                ],
                'vendedor' => [
                    'id' => $pedido->user?->id,
                    'name' => $pedido->user?->name,
                ],
                'camion' => [
                    'id' => $pedido->usuarioCamion?->id,
                    'name' => $pedido->usuarioCamion?->name,
                    'placa' => $pedido->usuarioCamion?->placa,
                ],
                'zona' => [
                    'id' => $pedido->zona?->id,
                    'nombre' => $pedido->zona?->nombre,
                    'color' => $pedido->zona?->color ?? '#9e9e9e',
                ],
                'detalles' => $pedido->detalles->map(function (PedidoDetalle $detalle) {
                    return [
                        'id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'codigo' => $detalle->producto?->codigo ?: ('#' . $detalle->producto_id),
                        'producto' => $detalle->producto?->nombre,
                        'tipo' => strtoupper((string) ($detalle->producto?->tipo ?? 'NORMAL')),
                        'imagen' => $detalle->producto?->imagen,
                        'cantidad' => (float) $detalle->cantidad,
                        'precio' => (float) $detalle->precio,
                        'total' => (float) $detalle->total,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'fecha' => $fecha,
                'sync_at' => now()->toDateTimeString(),
                'total_pedidos' => $rows->count(),
                'total_bs' => (float) $rows->sum('total'),
                'pendientes' => $rows->where('auxiliar_estado', 'PENDIENTE')->count(),
                'hechos' => $rows->where('auxiliar_estado', 'HECHO')->count(),
                'modificados' => $rows->where('auxiliar_estado', 'MODIFICADO')->count(),
            ],
        ]);
    }

    public function procesar(Request $request, Pedido $pedido)
    {
        $this->authorizeAuxiliarAccess($request);
        if ($pedido->tipo_pedido !== 'REALIZAR_PEDIDO' || $pedido->estado !== 'Enviado') {
            return response()->json(['message' => 'Solo se pueden procesar pedidos enviados'], 422);
        }

        $data = $request->validate([
            'generar_venta' => 'nullable|boolean',
            'auxiliar_observacion' => 'nullable|string|max:600',
            'detalles' => 'nullable|array',
            'detalles.*.id' => 'required_with:detalles|integer|exists:pedido_detalles,id',
            'detalles.*.cantidad' => 'required_with:detalles|numeric|min:0',
        ]);

        $generarVenta = (bool) ($data['generar_venta'] ?? true);
        if ($generarVenta && $pedido->venta_generada) {
            return response()->json(['message' => 'Este pedido ya genero una venta'], 422);
        }

        return DB::transaction(function () use ($pedido, $data, $generarVenta, $request) {
            $updated = false;
            if (!empty($data['detalles'])) {
                $map = collect($data['detalles'])->keyBy('id');
                $detalles = $pedido->detalles()->with('producto:id,tipo')->get();
                foreach ($detalles as $detalle) {
                    if (!$map->has($detalle->id)) {
                        continue;
                    }
                    $newCant = (float) $map[$detalle->id]['cantidad'];
                    if ($newCant < 0) {
                        abort(422, 'Cantidad invalida');
                    }
                    if (abs((float) $detalle->cantidad - $newCant) > 0.0001) {
                        $detalle->cantidad = $newCant;
                        $detalle->total = round($newCant * (float) $detalle->precio, 3);
                        $detalle->save();
                        $updated = true;
                    }
                }

                $pedido->detalles()->where('cantidad', '<=', 0)->delete();
                [$total, $contiene] = $this->recalcularPedido($pedido);
                $pedido->total = $total;
                $pedido->contiene_normal = $contiene['normal'];
                $pedido->contiene_res = $contiene['res'];
                $pedido->contiene_cerdo = $contiene['cerdo'];
                $pedido->contiene_pollo = $contiene['pollo'];
            }

            $pedido->auxiliar_observacion = $data['auxiliar_observacion'] ?? $pedido->auxiliar_observacion;
            $pedido->auxiliar_user_id = $request->user()->id;

            if ($generarVenta) {
                $venta = $this->crearVentaDesdePedido($pedido, $request->user()->id);
                $pedido->venta_generada = true;
                $pedido->venta_id = $venta->id;
                $pedido->auxiliar_estado = $updated ? 'MODIFICADO' : 'HECHO';
                $pedido->auxiliar_hecho_at = now();
            } else {
                $pedido->auxiliar_estado = 'MODIFICADO';
            }

            $pedido->save();

            return response()->json([
                'message' => $generarVenta ? 'Pedido procesado y venta generada' : 'Pedido modificado',
                'pedido' => $pedido->load([
                    'venta:id,total,estado',
                    'detalles.producto:id,codigo,nombre,imagen,tipo',
                    'cliente:id,nombre,codcli,ci',
                    'user:id,name',
                    'usuarioCamion:id,name,placa',
                    'zona:id,nombre,color',
                ]),
            ]);
        });
    }

    public function reportePedidos(Request $request)
    {
        $pedidos = $this->queryForReports($request)->with([
            'cliente:id,nombre,direccion,telefono,codcli,ci,territorio',
            'user:id,name',
            'usuarioCamion:id,name,placa',
            'zona:id,nombre,color',
            'detalles.producto:id,codigo,nombre,imagen,tipo',
        ])->orderBy('hora')->orderBy('id')->get();

        if ($pedidos->isEmpty()) {
            return response()->json(['message' => 'No hay pedidos para los filtros seleccionados'], 404);
        }

        $fecha = (string) ($request->input('fecha') ?: now()->toDateString());
        $pdf = Pdf::loadView('pdf.auxiliar_camara_pedidos', [
            'pedidos' => $pedidos,
            'fecha' => $fecha,
        ])->setPaper('letter');

        return $pdf->download('auxiliar_pedidos_' . str_replace('-', '', $fecha) . '.pdf');
    }

    public function reporteProductosTotales(Request $request)
    {
        $pedidos = $this->queryForReports($request)->with([
            'detalles.producto:id,codigo,nombre,imagen,tipo',
        ])->get();

        if ($pedidos->isEmpty()) {
            return response()->json(['message' => 'No hay pedidos para los filtros seleccionados'], 404);
        }

        $productos = $pedidos
            ->flatMap(fn (Pedido $pedido) => $pedido->detalles)
            ->filter(fn (PedidoDetalle $d) => $d->producto !== null)
            ->groupBy('producto_id')
            ->map(function (Collection $group) {
                $producto = $group->first()->producto;
                $img = trim((string) ($producto->imagen ?? ''));
                $path = public_path($img);
                return [
                    'codigo' => $producto->codigo ?: ('#' . $producto->id),
                    'nombre' => $producto->nombre,
                    'tipo' => strtoupper((string) ($producto->tipo ?? 'NORMAL')),
                    'cantidad_total' => (float) $group->sum('cantidad'),
                    'importe_total' => (float) $group->sum('total'),
                    'imagen_path' => ($img !== '' && file_exists($path)) ? $path : null,
                ];
            })
            ->sortBy('nombre')
            ->values();

        $fecha = (string) ($request->input('fecha') ?: now()->toDateString());
        $pdf = Pdf::loadView('pdf.auxiliar_camara_productos_totales', [
            'fecha' => $fecha,
            'productos' => $productos,
            'cantidadTotal' => (float) $productos->sum('cantidad_total'),
            'importeTotal' => (float) $productos->sum('importe_total'),
        ])->setPaper('letter');

        return $pdf->download('auxiliar_productos_' . str_replace('-', '', $fecha) . '.pdf');
    }

    private function queryForReports(Request $request)
    {
        $this->authorizeAuxiliarAccess($request);
        $data = $request->validate([
            'fecha' => 'nullable|date',
            'vendedor_id' => 'nullable|integer|exists:users,id',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'usuario_camion_id' => 'nullable|integer|exists:users,id',
            'pedido_zona_id' => 'nullable|integer|exists:pedido_zonas,id',
            'tipo' => 'nullable|string|in:TODOS,NORMAL,POLLO,RES,CERDO',
        ]);

        $fecha = $data['fecha'] ?? now()->toDateString();
        $tipo = strtoupper((string) ($data['tipo'] ?? 'TODOS'));

        return Pedido::query()
            ->where('tipo_pedido', 'REALIZAR_PEDIDO')
            ->where('estado', 'Enviado')
            ->whereDate('fecha', $fecha)
            ->when(!empty($data['vendedor_id']), fn ($q) => $q->where('user_id', (int) $data['vendedor_id']))
            ->when(!empty($data['cliente_id']), fn ($q) => $q->where('cliente_id', (int) $data['cliente_id']))
            ->when(!empty($data['usuario_camion_id']), fn ($q) => $q->where('usuario_camion_id', (int) $data['usuario_camion_id']))
            ->when(!empty($data['pedido_zona_id']), fn ($q) => $q->where('pedido_zona_id', (int) $data['pedido_zona_id']))
            ->when($tipo !== 'TODOS', function ($q) use ($tipo) {
                if ($tipo === 'NORMAL') $q->where('contiene_normal', true);
                if ($tipo === 'POLLO') $q->where('contiene_pollo', true);
                if ($tipo === 'RES') $q->where('contiene_res', true);
                if ($tipo === 'CERDO') $q->where('contiene_cerdo', true);
            });
    }

    private function recalcularPedido(Pedido $pedido): array
    {
        $detalles = $pedido->detalles()->with('producto:id,tipo')->get();
        $contiene = ['normal' => false, 'res' => false, 'cerdo' => false, 'pollo' => false];
        $total = 0.0;
        foreach ($detalles as $d) {
            $total += (float) $d->total;
            $tipo = strtoupper((string) ($d->producto?->tipo ?? 'NORMAL'));
            if ($tipo === 'RES') $contiene['res'] = true;
            elseif ($tipo === 'CERDO') $contiene['cerdo'] = true;
            elseif ($tipo === 'POLLO') $contiene['pollo'] = true;
            else $contiene['normal'] = true;
        }
        return [$total, $contiene];
    }

    private function crearVentaDesdePedido(Pedido $pedido, int $auxiliarUserId): Venta
    {
        $pedido->loadMissing(['detalles.producto:id,nombre', 'user:id,agencia', 'cliente:id,nombre,ci']);
        $detalles = $pedido->detalles;
        if ($detalles->isEmpty()) {
            abort(422, 'Pedido sin productos para generar venta');
        }

        $venta = Venta::create([
            'user_id' => $pedido->user_id ?: $auxiliarUserId,
            'pedido_id' => $pedido->id,
            'cliente_id' => $pedido->cliente_id,
            'fecha' => now()->toDateString(),
            'hora' => now()->format('H:i:s'),
            'ci' => (string) ($pedido->cliente?->ci ?? ''),
            'nombre' => (string) ($pedido->cliente?->nombre ?? ''),
            'estado' => 'Activo',
            'tipo_comprobante' => 'NOTA',
            'tipo_pago' => $pedido->tipo_pago ?: 'Efectivo',
            'agencia' => $pedido->user?->agencia,
            'total' => 0,
            'online' => false,
        ]);

        $total = 0.0;
        foreach ($detalles as $detalle) {
            $cantidad = (float) $detalle->cantidad;
            if ($cantidad <= 0) continue;
            $precio = (float) $detalle->precio;
            $nombre = (string) ($detalle->producto?->nombre ?? 'Producto');
            $total += $this->consumirStockFifo(
                ventaId: (int) $venta->id,
                productoId: (int) $detalle->producto_id,
                cantidad: $cantidad,
                precio: $precio,
                nombreProducto: $nombre
            );
        }

        $venta->total = round($total, 3);
        $venta->save();

        return $venta;
    }

    private function consumirStockFifo(int $ventaId, int $productoId, float $cantidad, float $precio, string $nombreProducto): float
    {
        $restante = $cantidad;
        $sum = 0.0;

        $lotes = CompraDetalle::query()
            ->where('producto_id', $productoId)
            ->where('estado', 'Activo')
            ->whereNull('deleted_at')
            ->where('cantidad_venta', '>', 0)
            ->orderByRaw("CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END, fecha_vencimiento ASC")
            ->lockForUpdate()
            ->get(['id', 'cantidad_venta', 'lote', 'fecha_vencimiento']);

        foreach ($lotes as $lote) {
            if ($restante <= 0) break;
            $disp = (float) $lote->cantidad_venta;
            if ($disp <= 0) continue;

            $take = min($disp, $restante);
            if ($take <= 0) continue;

            VentaDetalle::create([
                'venta_id' => $ventaId,
                'producto_id' => $productoId,
                'cantidad' => $take,
                'precio' => $precio,
                'nombre' => $nombreProducto,
                'lote' => $lote->lote,
                'fecha_vencimiento' => $lote->fecha_vencimiento,
                'compra_detalle_id' => $lote->id,
            ]);

            $lote->cantidad_venta = $disp - $take;
            $lote->save();
            $restante -= $take;
            $sum += ($take * $precio);
        }

        if ($restante > 0.0001) {
            abort(422, 'Stock insuficiente para generar venta (FIFO)');
        }

        return $sum;
    }

    private function authorizeAuxiliarAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 401, 'No autenticado');

        $isAdmin = strtoupper((string) ($user->role ?? '')) === 'ADMIN';
        $canAuxiliar = method_exists($user, 'can') && $user->can('Auxiliar de camara');
        abort_unless($isAdmin || $canAuxiliar, 403, 'No autorizado');
    }
}

