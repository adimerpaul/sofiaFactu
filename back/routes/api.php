<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
//app.config.globalProperties.$agencias = ['Challgua','Socavon','Catalina']
Route::post('/login', [App\Http\Controllers\UserController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [App\Http\Controllers\UserController::class, 'logout']);
    Route::get('/me', [App\Http\Controllers\UserController::class, 'me']);


    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy']);
    Route::put('/updatePassword/{user}', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::post('/{user}/avatar', [App\Http\Controllers\UserController::class, 'updateAvatar']);
    Route::get('/usuariosRole', [App\Http\Controllers\UserController::class, 'usuariosRole']);

    Route::get('/permissions', [App\Http\Controllers\PermissionController::class, 'index']);
    Route::get('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'getPermissions']);
    Route::put('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'syncPermissions']);


    Route::get('/productos/{id}/historial-compras-ventas', [App\Http\Controllers\ProductoController::class, 'historialComprasVentas']);

    Route::post('/productos', [App\Http\Controllers\ProductoController::class, 'store']);
    Route::get('/productosAll', [App\Http\Controllers\ProductoController::class, 'productosAll']);
    Route::get('/productos', [App\Http\Controllers\ProductoController::class, 'index']);
    Route::get('/productosStock', [App\Http\Controllers\ProductoController::class, 'productosStock']);
    Route::put('/productos/{producto}', [App\Http\Controllers\ProductoController::class, 'update']);
    Route::delete('/productos/{producto}', [App\Http\Controllers\ProductoController::class, 'destroy']);


    Route::post('/searchCliente', [App\Http\Controllers\ClienteController::class, 'searchCliente']);

    Route::post('/ventas', [App\Http\Controllers\VentaController::class, 'store']);
    Route::get('/ventas', [App\Http\Controllers\VentaController::class, 'index']);
    Route::put('/ventasAnular/{venta}', [App\Http\Controllers\VentaController::class, 'anular']);
    Route::put('/tipoVentasChange/{venta}', [App\Http\Controllers\VentaController::class, 'tipoVentasChange']);

    Route::get('/proveedores', [App\Http\Controllers\ProveedorController::class, 'index']);
    Route::post('/proveedores', [App\Http\Controllers\ProveedorController::class, 'store']);
    Route::put('/proveedores/{proveedor}', [App\Http\Controllers\ProveedorController::class, 'update']);
    Route::delete('/proveedores/{proveedor}', [App\Http\Controllers\ProveedorController::class, 'destroy']);

    Route::get('compras', [App\Http\Controllers\CompraController::class, 'index']);
    Route::put('comprasAnular/{id}', [App\Http\Controllers\CompraController::class, 'anular']);
    Route::post('compras', [App\Http\Controllers\CompraController::class, 'store']);
    Route::get('/productosPorVencer', [App\Http\Controllers\CompraController::class, 'productosPorVencer']);
    Route::get('/productosVencidos', [App\Http\Controllers\CompraController::class, 'productosVencidos']);
    Route::get('/productos/{id}/historial-compras', [App\Http\Controllers\CompraController::class, 'historialCompras']);

    Route::get('/pedidos', [App\Http\Controllers\PedidoController::class, 'index']);
    Route::put('/pedidos/{pedido}', [App\Http\Controllers\PedidoController::class, 'update']);
    Route::post('/pedidos', [App\Http\Controllers\PedidoController::class, 'store']);

    Route::get('/recuperarPedido', [App\Http\Controllers\PedidoController::class, 'recuperarPedido']);

    Route::post('/impuestos/generar-cui', [App\Http\Controllers\ImpuestoController::class, 'generarCUI']);
    Route::post('/impuestos/generar-cufd', [App\Http\Controllers\ImpuestoController::class, 'generarCUFD']);
    Route::get('/impuestos/list-cufd', [App\Http\Controllers\ImpuestoController::class, 'listCUFD']);
    Route::post('/verificarImpuestos/{cuf}', [App\Http\Controllers\ImpuestoController::class, 'verificarImpuestos']);
    Route::post('/eventoSignificativo', [App\Http\Controllers\ImpuestoController::class, 'eventoSignificativo']);
    Route::post('/validarPaquete', [App\Http\Controllers\ImpuestoController::class, 'validarPaquete']);
});

//Route test
Route::get('/test', [App\Http\Controllers\TestController::class, 'index']);
//facturacionOperaciones
Route::get('/facturacionOperaciones', [App\Http\Controllers\ImpuestoController::class, 'facturacionOperaciones']);
