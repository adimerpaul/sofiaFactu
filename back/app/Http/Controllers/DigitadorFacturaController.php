<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DigitadorFacturaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeDigitador($request);

        $data = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'search' => 'nullable|string|max:120',
            'solo_factura' => 'nullable',
        ]);

        $fechaInicio = $data['fecha_inicio'] ?? now()->toDateString();
        $fechaFin = $data['fecha_fin'] ?? now()->toDateString();
        $soloFactura = $this->parseBoolean($data['solo_factura'] ?? false);
        $search = mb_strtolower(trim((string) ($data['search'] ?? '')));

        $ventas = Venta::query()
            ->with([
                'pedido:id,user_id,cliente_id,fecha,hora,estado,tipo_pago,facturado,tipo_pedido',
                'pedido.user:id,name',
                'pedido.cliente:id,nombre,codcli,ci',
                'user:id,name',
                'cliente:id,nombre,codcli,ci',
                'ventaDetalles:id,venta_id,producto_id,cantidad,precio,nombre',
                'ventaDetalles.producto:id,codigo,nombre,tipo',
            ])
            ->whereNotNull('pedido_id')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereHas('pedido', function (Builder $q) {
                $q->where('tipo_pedido', 'REALIZAR_PEDIDO');
            })
            ->when($soloFactura, fn (Builder $q) => $q->where('facturado', true))
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        if ($search !== '') {
            $ventas = $ventas->filter(function (Venta $venta) use ($search) {
                $haystack = mb_strtolower(implode(' ', [
                    (string) $venta->id,
                    (string) ($venta->pedido_id ?? ''),
                    (string) ($venta->pedido?->cliente?->nombre ?? ''),
                    (string) ($venta->cliente?->nombre ?? ''),
                    (string) ($venta->pedido?->user?->name ?? ''),
                    (string) ($venta->user?->name ?? ''),
                ]));

                return str_contains($haystack, $search);
            })->values();
        }

        $rows = $ventas->map(function (Venta $venta) {
            $productosVenta = $venta->ventaDetalles->map(function ($d) {
                return [
                    'id' => $d->id,
                    'producto_id' => $d->producto_id,
                    'codigo' => $d->producto?->codigo ?: ('#' . $d->producto_id),
                    'nombre' => $d->producto?->nombre ?: ('Producto #' . $d->producto_id),
                    'tipo' => strtoupper((string) ($d->producto?->tipo ?? 'NORMAL')),
                    'cantidad' => (float) $d->cantidad,
                    'precio' => (float) $d->precio,
                    'subtotal' => (float) $d->cantidad * (float) $d->precio,
                ];
            })->values();

            $tipos = $productosVenta->pluck('tipo')->unique()->values();

            return [
                'venta_id' => $venta->id,
                'comanda' => $venta->pedido_id,
                'vendedor' => $venta->pedido?->user?->name,
                'cliente' => $venta->pedido?->cliente?->nombre ?: $venta->cliente?->nombre,
                'tipo' => $tipos,
                'productos' => $productosVenta,
                'fecha' => (string) ($venta->fecha ?? ''),
                'hora' => (string) ($venta->hora ?? ''),
                'pago' => (string) ($venta->tipo_pago ?? ''),
                'facturado' => (bool) $venta->facturado,
                'factura_estado' => (string) ($venta->factura_estado ?? 'SIN_GESTION'),
                'estado' => (string) ($venta->estado ?? ''),
                'total' => (float) ($venta->total ?? 0),
                'pedido_id' => $venta->pedido_id,
                'generado_por' => $venta->user?->name,
                'detalle_edit' => [
                    'tipo_pago' => (string) ($venta->tipo_pago ?? ''),
                    'facturado' => (bool) $venta->facturado,
                    'factura_estado' => (string) ($venta->factura_estado ?? 'SIN_GESTION'),
                    'productos' => $productosVenta,
                ],
            ];
        })->values();

        $pedidosSinVenta = Pedido::query()
            ->with(['user:id,name', 'cliente:id,nombre'])
            ->where('tipo_pedido', 'REALIZAR_PEDIDO')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereNull('venta_id')
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get()
            ->map(function (Pedido $pedido) {
                return [
                    'comanda' => $pedido->id,
                    'fecha' => (string) $pedido->fecha,
                    'hora' => (string) ($pedido->hora ?? ''),
                    'vendedor' => $pedido->user?->name,
                    'cliente' => $pedido->cliente?->nombre,
                    'facturado' => (bool) $pedido->facturado,
                    'estado' => (string) ($pedido->estado ?? ''),
                ];
            })
            ->values();

        $stats = [
            'total_ventas' => $rows->count(),
            'monto_total_ventas' => (float) $rows->sum('total'),
            'ventas_facturadas' => $rows->where('facturado', true)->count(),
            'ventas_no_facturadas' => $rows->where('facturado', false)->count(),
            'pendientes_factura' => $rows->where('factura_estado', 'PENDIENTE')->count(),
            'pedidos_sin_venta' => $pedidosSinVenta->count(),
        ];

        return response()->json([
            'data' => $rows->values(),
            'pedidos_sin_venta' => $pedidosSinVenta,
            'stats' => $stats,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'solo_factura' => $soloFactura,
            ],
        ]);
    }

    public function updatePedido(Request $request, Pedido $pedido)
    {
        $this->authorizeDigitador($request);

        $data = $request->validate([
            'tipo_pago' => 'sometimes|nullable|string|in:Contado,QR,Credito,Boleta anterior',
            'facturado' => 'sometimes|boolean',
            'fecha' => 'sometimes|nullable|date',
            'hora' => 'sometimes|nullable|string|max:50',
        ]);

        $pedido->update($data);

        if ($pedido->venta && array_key_exists('facturado', $data)) {
            $pedido->venta->facturado = (bool) $data['facturado'];
            $pedido->venta->factura_estado = $data['facturado'] ? 'PENDIENTE' : 'SIN_GESTION';
            $pedido->venta->save();
        }

        return response()->json([
            'message' => 'Pedido actualizado',
            'pedido' => $pedido->fresh(['cliente:id,nombre', 'user:id,name']),
        ]);
    }

    private function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }
        $normalized = mb_strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'si', 'sí', 'yes', 'on'], true);
    }

    public function updateVenta(Request $request, Venta $venta)
    {
        $this->authorizeDigitador($request);

        $data = $request->validate([
            'estado' => 'sometimes|nullable|string|in:Activo,Anulada',
            'tipo_pago' => 'sometimes|nullable|string|in:Efectivo,QR,Contado,Credito,Boleta anterior',
            'facturado' => 'sometimes|boolean',
            'factura_estado' => 'sometimes|nullable|string|in:SIN_GESTION,PENDIENTE,FACTURADO',
            'productos' => 'sometimes|array',
            'productos.*.id' => 'required_with:productos|integer',
            'productos.*.cantidad' => 'required_with:productos|numeric|min:0',
            'productos.*.precio' => 'required_with:productos|numeric|min:0',
        ]);

        return DB::transaction(function () use ($venta, $data) {
            $venta->fill(array_intersect_key($data, array_flip(['estado', 'tipo_pago', 'facturado', 'factura_estado'])));
            if ($venta->isDirty('facturado') && !$venta->isDirty('factura_estado')) {
                $venta->factura_estado = $venta->facturado ? 'PENDIENTE' : 'SIN_GESTION';
            }
            $venta->save();

            if (!empty($data['productos'])) {
                $ids = collect($data['productos'])->pluck('id')->map(fn ($v) => (int) $v)->all();
                $detalles = VentaDetalle::query()
                    ->where('venta_id', $venta->id)
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                foreach ($data['productos'] as $item) {
                    $detalle = $detalles->get((int) $item['id']);
                    if (!$detalle) {
                        continue;
                    }

                    $detalle->cantidad = (float) $item['cantidad'];
                    $detalle->precio = (float) $item['precio'];
                    $detalle->save();
                }

                $total = (float) VentaDetalle::query()
                    ->where('venta_id', $venta->id)
                    ->selectRaw('COALESCE(SUM(cantidad * precio), 0) as total')
                    ->value('total');
                $venta->total = round($total, 2);
                $venta->save();
            }

            if ($venta->pedido) {
                $venta->pedido->facturado = (bool) $venta->facturado;
                $venta->pedido->save();
            }

            return response()->json([
                'message' => 'Venta actualizada',
                'venta' => $venta->fresh(['user:id,name', 'cliente:id,nombre', 'ventaDetalles.producto:id,codigo,nombre,tipo']),
            ]);
        });
    }

    public function generarFacturaTodos(Request $request)
    {
        $this->authorizeDigitador($request);

        $data = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $fechaInicio = $data['fecha_inicio'] ?? now()->toDateString();
        $fechaFin = $data['fecha_fin'] ?? now()->toDateString();

        $ventasIds = Pedido::query()
            ->where('tipo_pedido', 'REALIZAR_PEDIDO')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('facturado', true)
            ->whereNotNull('venta_id')
            ->pluck('venta_id')
            ->unique()
            ->values();

        $updated = 0;
        if ($ventasIds->isNotEmpty()) {
            $updated = Venta::query()
                ->whereIn('id', $ventasIds->all())
                ->update(['factura_estado' => 'PENDIENTE']);
        }

        return response()->json([
            'message' => 'Facturacion masiva marcada como pendiente',
            'ventas_marcadas' => $updated,
            'detalle' => 'La generacion real de factura aun esta pendiente de implementacion.',
        ]);
    }

    private function authorizeDigitador(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 401, 'No autenticado');

        $isAdmin = strtoupper((string) ($user->role ?? '')) === 'ADMIN';
        $canDigitador = method_exists($user, 'can') && $user->can('Digitador factura');
        abort_unless($isAdmin || $canDigitador, 403, 'No autorizado');
    }
}
