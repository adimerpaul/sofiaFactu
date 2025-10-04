<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Spatie\Permission\Models\Permission;

class UserController extends Controller{
    function usuariosRole(){
        $psicologos = User::where('role', 'Psicologo')->get();
        $abogados = User::where('role', 'Abogado')->get();
        $sociales = User::where('role', 'Social')->get();
        return response()->json([
            'psicologos' => $psicologos,
            'abogados' => $abogados,
            'sociales' => $sociales,
        ]);
    }
    public function updateAvatar(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $path = public_path('images/' . $filename);

            // Crear instancia del gestor de imágenes
            $manager = new ImageManager(new Driver()); // O new Imagick\Driver()

            // Redimensionar y comprimir
            $manager->read($file->getPathname())
                ->resize(300, 300) // o no pongas resize si no quieres cambiar tamaño
                ->toJpeg(70)       // calidad 70%
                ->save($path);

            $user->avatar = $filename;
            $user->save();

            return response()->json(['message' => 'Avatar actualizado', 'avatar' => $filename]);
        }

        return response()->json(['message' => 'No se ha enviado un archivo'], 400);
    }
    function login(Request $request){
        $credentials = $request->only('username', 'password');
        $user = User::where('username', $credentials['username'])->with('permissions:id,name')->first();
        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
    function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Token eliminado',
        ]);
    }
    function me(Request $request){
        $user = $request->user();
        $user->load('permissions:id,name');
//        return response()->json($user);
        return [
            'user' => User::find($user->id),
            'permissions' => $user->permissions
        ];
    }
    function index(){
        return User::where('id', '!=', 0)
            ->with('permissions:id,name')
            ->orderBy('id', 'desc')
            ->get();
    }
    function update(Request $request, $id){
        $user = User::find($id);
        $user->update($request->except('password'));
        error_log('User' . json_encode($user));
        return $user;
    }
    function updatePassword(Request $request, $id){
        $user = User::find($id);
        $user->update([
            'password' => bcrypt($request->password),
        ]);
        return $user;
    }
    function store(Request $request){
        $validatedData = $request->validate([
            'username' => 'required|unique:users',
            'password' => 'required',
            'name' => 'required',
//            'email' => 'required|email|unique:users',
        ]);
        $user = User::create($request->all());
        return $user;
    }
    function destroy($id){
        return User::destroy($id);
    }
    public function getPermissions($userId)
    {
        $user = User::findOrFail($userId);
        // devuelve IDs de permisos del usuario
        return $user->permissions()->pluck('id');
    }

    public function syncPermissions(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $user = User::findOrFail($userId);
        $perms = Permission::whereIn('id', $request->permissions ?? [])->get();
        $user->syncPermissions($perms);

        return response()->json([
            'message' => 'Permisos actualizados',
            'permissions' => $user->permissions()->pluck('name'),
        ]);
    }
}
