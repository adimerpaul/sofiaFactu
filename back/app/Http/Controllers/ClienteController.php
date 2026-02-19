<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->input('search', ''));
        $perPage = (int)$request->input('per_page', 10);

        return Cliente::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('nombre', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%")
                        ->orWhere('codcli', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $payload = $this->preparePayload($request, $data, null);

        $cliente = Cliente::create($payload);

        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente)
    {
        return $cliente;
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $this->validateData($request, true);
        $payload = $this->preparePayload($request, $data, $cliente);

        $cliente->update($payload);

        return $cliente->fresh();
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->json(['success' => true]);
    }

    public function searchCliente(Request $request)
    {
        $ci = $request->nit;
        return Cliente::where('ci', $ci)->first();
    }

    private function validateData(Request $request, bool $isUpdate = false): array
    {
        $sometimes = $isUpdate ? 'sometimes' : 'nullable';

        $rules = [
            'nombre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:150'],
            'ci' => [$sometimes, 'string', 'max:50'],
            'telefono' => [$sometimes, 'string', 'max:100'],
            'direccion' => [$sometimes, 'string', 'max:255'],
            'complemento' => [$sometimes, 'string', 'max:20'],
            'codigoTipoDocumentoIdentidad' => [$sometimes, 'string', 'max:20'],
            'email' => [$sometimes, 'email', 'max:255'],

            'id_externo' => [$sometimes, 'string', 'max:15'],
            'cod_ciudad' => [$sometimes, 'string', 'max:4'],
            'cod_nacio' => [$sometimes, 'string', 'max:4'],
            'cod_car' => [$sometimes, 'integer'],
            'est_civ' => [$sometimes, 'string', 'max:50'],
            'edad' => [$sometimes, 'string', 'max:3'],
            'empresa' => [$sometimes, 'string', 'max:150'],
            'categoria' => [$sometimes, 'integer'],
            'imp_pieza' => [$sometimes, 'numeric', 'min:0'],
            'ci_vend' => [$sometimes, 'string', 'max:15'],
            'list_blanck' => [$sometimes, 'boolean'],
            'motivo_list_black' => [$sometimes, 'string', 'max:90'],
            'list_black' => [$sometimes, 'boolean'],
            'tipo_paciente' => [$sometimes, 'string', 'max:90'],
            'supra_canal' => [$sometimes, 'string', 'max:5'],
            'canal' => [$sometimes, 'string', 'max:80'],
            'subcanal' => [$sometimes, 'string', 'max:20'],
            'zona' => [$sometimes, 'string', 'max:20'],
            'latitud' => [$sometimes, 'numeric', 'between:-90,90'],
            'longitud' => [$sometimes, 'numeric', 'between:-180,180'],
            'transporte' => [$sometimes, 'string', 'max:60'],
            'territorio' => [$sometimes, 'string', 'max:10'],
            'codcli' => [$sometimes, 'integer'],
            'clinew' => [$sometimes, 'string', 'max:3'],
            'venta_estado' => [$sometimes, 'string', 'max:100'],
            'complto' => [$sometimes, 'string', 'max:5'],
            'tipodocu' => [$sometimes, 'integer'],
            'lu' => [$sometimes, 'boolean'],
            'ma' => [$sometimes, 'boolean'],
            'mi' => [$sometimes, 'boolean'],
            'ju' => [$sometimes, 'boolean'],
            'vi' => [$sometimes, 'boolean'],
            'sa' => [$sometimes, 'boolean'],
            'do' => [$sometimes, 'boolean'],
            'correcli' => [$sometimes, 'string', 'max:50'],
            'canmayni' => [$sometimes, 'boolean'],
            'baja' => [$sometimes, 'boolean'],
            'profecion' => [$sometimes, 'string', 'max:60'],
            'waths' => [$sometimes, 'boolean'],
            'ctas_activo' => [$sometimes, 'boolean'],
            'ctas_mont' => [$sometimes, 'numeric', 'min:0'],
            'ctas_dias' => [$sometimes, 'integer'],
            'sexo' => [$sometimes, 'string', 'max:20'],
            'noesempre' => [$sometimes, 'boolean'],
            'tarjeta' => [$sometimes, 'string', 'max:20'],

            'fotos' => [$sometimes, 'array', 'max:3'],
            'fotos.*' => ['image', 'max:5120'],
            'remove_fotos' => [$sometimes, 'array'],
            'remove_fotos.*' => ['string'],
        ];

        return $request->validate($rules);
    }

    private function preparePayload(Request $request, array $data, ?Cliente $cliente): array
    {
        $existing = $cliente?->fotos ?? [];
        if (!is_array($existing)) {
            $existing = [];
        }

        $remove = $data['remove_fotos'] ?? [];
        if (!empty($remove)) {
            $existing = array_values(array_filter($existing, function ($path) use ($remove) {
                return !in_array($path, $remove, true);
            }));

            foreach ($remove as $path) {
                if (is_string($path) && str_starts_with($path, 'uploads/clientes/')) {
                    $full = public_path($path);
                    if (File::exists($full)) {
                        File::delete($full);
                    }
                }
            }
        }

        if ($request->hasFile('fotos')) {
            File::ensureDirectoryExists(public_path('uploads/clientes'));
            foreach ($request->file('fotos') as $file) {
                if (count($existing) >= 3) {
                    break;
                }
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $name = uniqid('cli_', true) . '.' . $ext;
                $file->move(public_path('uploads/clientes'), $name);
                $existing[] = 'uploads/clientes/' . $name;
            }
        }

        $payload = $data;
        unset($payload['fotos'], $payload['remove_fotos']);
        $payload['fotos'] = array_values($existing);

        if (!isset($payload['venta_estado']) || empty($payload['venta_estado'])) {
            $payload['venta_estado'] = 'ACTIVO';
        }

        return $payload;
    }
}
