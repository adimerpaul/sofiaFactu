<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller {
    function recuperarPedido(Request $request) {
        $pedido = Pedido::with('detalles.producto')
            ->where('id', $request->id)
            ->first();
        return response()->json($pedido);
    }
    public function update(Request $request, Pedido $pedido) {
        DB::beginTransaction();
        try {
            $pedido->update($request->all());
            DB::commit();
            return response()->json($pedido);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function index(Request $request) {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $user = $request->user();

        return Pedido::with(['detalles.producto', 'user', 'cliente'])
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->where('fecha', '>=', $fechaInicio)
                    ->where('fecha', '<=', $fechaFin);
            })
            ->when($request->filled('tipo_pedido'), function ($q) use ($request) {
                $q->where('tipo_pedido', $request->tipo_pedido);
            })
            ->when($request->filled('cliente_id'), function ($q) use ($request) {
                $q->where('cliente_id', $request->cliente_id);
            })
            ->when(($request->solo_mios ?? false) && $user, function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();
    }

    public function store(Request $request) {
        DB::beginTransaction();
        try {
            $user = $request->user();
            $tipoPedido = strtoupper((string)($request->tipo_pedido ?? 'REALIZAR_PEDIDO'));
            $isPedido = $tipoPedido === 'REALIZAR_PEDIDO';

            $rules = [
                'tipo_pedido' => 'nullable|string|in:REALIZAR_PEDIDO,RETORNAR,NO_PEDIDO,GENERAR_RUTA',
                'tipo_pago' => 'nullable|string|in:Contado,QR,Credito,Boleta anterior',
                'cliente_id' => 'nullable|integer|exists:clientes,id',
                'observaciones' => 'nullable|string|max:600',
                'comentario_visita' => 'nullable|string|max:600',
                'productos' => 'nullable|array',
                'productos.*.producto_id' => 'required_with:productos|integer|exists:productos,id',
                'productos.*.cantidad' => 'required_with:productos|numeric|min:0.01',
                'productos.*.precio' => 'required_with:productos|numeric|min:0',
            ];
            $data = $request->validate($rules);

            if ($isPedido && empty($data['productos'])) {
                return response()->json(['message' => 'Debe agregar al menos un producto para realizar pedido'], 422);
            }

            $pedido = Pedido::create([
                'user_id' => $user->id,
                'cliente_id' => $data['cliente_id'] ?? null,
                'fecha' => now()->format('Y-m-d'),
                'hora' => now()->format('H:i:s'),
                'estado' => 'Pendiente',
                'tipo_pago' => $data['tipo_pago'] ?? null,
                'tipo_pedido' => $tipoPedido,
                'total' => 0,
                'observaciones' => $data['observaciones'] ?? null,
                'comentario_visita' => $data['comentario_visita'] ?? null,
            ]);

            $total = 0;
            foreach (($data['productos'] ?? []) as $item) {
                $cantidad = (float)$item['cantidad'];
                $precio = (float)$item['precio'];
                $subtotal = $precio * $cantidad;
                if ($subtotal > 0) {
                    $pedido->detalles()->create([
                        'producto_id' => $item['producto_id'],
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'total' => $subtotal
                    ]);
                    $total += $subtotal;
                }
            }

            $pedido->update(['total' => $total]);

            DB::commit();
            return response()->json($pedido->load(['detalles.producto', 'cliente', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
