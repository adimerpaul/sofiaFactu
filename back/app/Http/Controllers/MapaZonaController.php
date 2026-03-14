<?php

namespace App\Http\Controllers;

use App\Models\MapaZonaPoligono;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MapaZonaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeMapaZonaAccess($request);

        $poligonos = MapaZonaPoligono::query()
            ->with(['pedidoZona:id,nombre,color,orden,activo'])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->map(fn (MapaZonaPoligono $poligono) => $this->formatPoligono($poligono))
            ->values();

        return response()->json([
            'poligonos' => $poligonos,
        ]);
    }

    public function storePoligono(Request $request)
    {
        $this->authorizeMapaZonaAccess($request);

        $data = $this->validatePoligono($request);

        $poligono = MapaZonaPoligono::create($this->mapPoligonoData($data));

        return $this->formatPoligono($poligono->load(['pedidoZona:id,nombre,color,orden,activo']));
    }

    public function updatePoligono(Request $request, MapaZonaPoligono $mapaZonaPoligono)
    {
        $this->authorizeMapaZonaAccess($request);

        $data = $this->validatePoligono($request, $mapaZonaPoligono->id);

        $mapaZonaPoligono->update($this->mapPoligonoData($data));

        return $this->formatPoligono($mapaZonaPoligono->fresh()->load(['pedidoZona:id,nombre,color,orden,activo']));
    }

    public function destroyPoligono(Request $request, MapaZonaPoligono $mapaZonaPoligono)
    {
        $this->authorizeMapaZonaAccess($request);

        $mapaZonaPoligono->delete();

        return response()->json(['message' => 'Poligono eliminado']);
    }

    private function validatePoligono(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('mapa_zona_poligonos', 'nombre')->ignore($ignoreId)],
            'tipo' => 'required|integer|in:3,4,5',
            'color' => 'required|string|max:20',
            'pedido_zona_id' => 'nullable|integer|exists:pedido_zonas,id',
            'orden' => 'nullable|integer|min:0|max:99999',
            'activo' => 'nullable|boolean',
            'coordenadas' => 'required|array|min:3',
            'coordenadas.*.lat' => 'required|numeric|between:-90,90',
            'coordenadas.*.lng' => 'required|numeric|between:-180,180',
        ]);
    }

    private function mapPoligonoData(array $data): array
    {
        return [
            'nombre' => trim($data['nombre']),
            'tipo' => (int) $data['tipo'],
            'color' => trim($data['color']),
            'pedido_zona_id' => !empty($data['pedido_zona_id']) ? (int) $data['pedido_zona_id'] : null,
            'orden' => (int) ($data['orden'] ?? 0),
            'activo' => (bool) ($data['activo'] ?? true),
            'coordenadas' => collect($data['coordenadas'])
                ->map(fn (array $point) => [
                    'lat' => round((float) $point['lat'], 6),
                    'lng' => round((float) $point['lng'], 6),
                ])
                ->values()
                ->all(),
        ];
    }

    private function formatPoligono(MapaZonaPoligono $poligono): array
    {
        $pedidoZona = $poligono->pedidoZona;

        return [
            'id' => $poligono->id,
            'nombre' => $poligono->nombre,
            'tipo' => (int) $poligono->tipo,
            'color' => $poligono->color ?: $pedidoZona?->color ?? '#607d8b',
            'pedido_zona_id' => $poligono->pedido_zona_id,
            'orden' => $poligono->orden,
            'activo' => (bool) $poligono->activo,
            'coordenadas' => $poligono->coordenadas ?: [],
            'pedido_zona' => $pedidoZona ? [
                'id' => $pedidoZona->id,
                'nombre' => $pedidoZona->nombre,
                'color' => $pedidoZona->color,
                'orden' => $pedidoZona->orden,
                'activo' => (bool) $pedidoZona->activo,
            ] : null,
        ];
    }

    private function authorizeMapaZonaAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 401, 'No autenticado');

        $isAdmin = strtoupper((string) ($user->role ?? '')) === 'ADMIN';
        $canMapaZona = method_exists($user, 'can') && $user->can('Mapa zona');
        abort_unless($isAdmin || $canMapaZona, 403, 'No autorizado');
    }
}
