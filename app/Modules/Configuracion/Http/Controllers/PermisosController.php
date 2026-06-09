<?php

namespace App\Modules\Configuracion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Configuracion\Models\Permisos;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PermisosController extends Controller
{
    public function index()
    {
        return view('configuracion::permisos.index');
    }

    public function cargarDatos()
    {
        $permisos = Permisos::query()
            ->orderBy('id')
            ->get()
            ->map(function ($permiso) {
                return [
                    'id' => $permiso->id,
                    'nombre_limpio' => $permiso->nombre_limpio ?? $permiso->name,
                    'name' => $permiso->name,
                    'guard_name' => $permiso->guard_name,
                    'updated_at' => $permiso->updated_at ?? 'Sin registro',
                ];
            })
            ->values();

        return $permisos;
    }

    public function edit($id)
    {
        $permiso = Permisos::query()->findOrFail($id);
        $guards = Permisos::query()
            ->select('guard_name')
            ->distinct()
            ->orderBy('guard_name')
            ->pluck('guard_name')
            ->filter()
            ->values();

        if (! $guards->contains($permiso->guard_name)) {
            $guards->push($permiso->guard_name);
        }

        return view('configuracion::permisos.editarPermiso', compact('permiso', 'guards'))->render();
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'nombre_limpio' => 'required|string|max:150',
            'guard_name' => 'required|string|max:80',
        ], [
            'nombre_limpio.required' => 'El nombre limpio es obligatorio.',
            'nombre_limpio.max' => 'El nombre limpio no puede tener mas de 150 caracteres.',
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.max' => 'El nombre del permiso no puede tener más de 150 caracteres.',
            'guard_name.required' => 'El guard del permiso es obligatorio.',
            'guard_name.max' => 'El guard no puede tener más de 80 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = trim((string) $request->name);
        $nombreLimpio = trim((string) $request->nombre_limpio);
        $guardName = trim((string) $request->guard_name);

        $existeDuplicado = Permisos::query()
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->where('id', '!=', $id)
            ->exists();

        if ($existeDuplicado) {
            return response()->json([
                'errors' => [
                    'name' => ['Ya existe un permiso con ese nombre para el guard seleccionado.'],
                ],
            ], 422);
        }

        try {
            $permiso = Permisos::query()->findOrFail($id);
            $permiso->name = $name;
            $permiso->nombre_limpio = $nombreLimpio;
            $permiso->guard_name = $guardName;
            $permiso->save();

            return toastModal('Permiso actualizado exitosamente', 'success', route('gestion-permisos.index'));
        } catch (Exception $e) {
            Log::error('Error al actualizar permiso: '.$e->getMessage(), [
                'permiso_id' => $id,
            ]);

            return toastModal('Error al actualizar el permiso', 'error', route('gestion-permisos.index'));
        }
    }
}
