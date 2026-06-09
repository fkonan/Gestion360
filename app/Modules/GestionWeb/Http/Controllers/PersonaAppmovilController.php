<?php

namespace App\Modules\GestionWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\PersonaDatos;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PersonaAppmovilController extends Controller
{
    public function index()
    {
        return view('gestionweb::appmovil.personas.index');
    }

    public function cargarDatos(Request $request)
    {
        try {
            $limit = (int) $request->get('limit', 25);
            $offset = (int) $request->get('offset', 0);
            $search = trim((string) $request->get('search', ''));
            $order = strtolower((string) $request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $sort = (string) $request->get('sort', 'fechaRegistro');

            $personas = Persona::with(['usuario', 'datos'])
                ->whereHas('usuario');

            if ($sort === 'fechaRegistro') {
                $personas = $personas
                    ->orderBy('PerFechReg', $order)
                    ->orderBy('PerHorReg', $order);
            } else {
                $personas = $personas->orderBy('IdPersona', 'desc');
            }

            if ($search !== '') {
                $likeSearch = "%{$search}%";

                $personas->where(function ($q) use ($likeSearch) {
                    $q->where('PerNumDoc', 'like', $likeSearch)
                        ->orWhereRaw("CONCAT(PerNombres, ' ', PerApellidos) like ?", [$likeSearch])
                        ->orWhere('PerEstado', 'like', $likeSearch)
                        ->orWhereHas('datos', function ($q2) use ($likeSearch) {
                            $q2->where('PerTelefono', 'like', $likeSearch)
                                ->orWhere('PerEmail', 'like', $likeSearch);
                        })
                        ->orWhereHas('usuario', function ($q3) use ($likeSearch) {
                            $q3->where('UsuarioEstado', 'like', $likeSearch)
                                ->orWhere('Verificado', 'like', $likeSearch);
                        });
                });
            }

            $total = $personas->count();

            $rows = $personas
                ->skip($offset)
                ->take($limit)
                ->get()
                ->map(function ($persona) {
                    return [
                        'IdPersona' => (int) $persona->IdPersona,
                        'IdUsuario' => (int) optional($persona->usuario)->IdUsuario,
                        'PerNumDoc' => (string) $persona->PerNumDoc,
                        'nombreCompleto' => ucfirst(strtolower(trim((string) $persona->PerNombres.' '.$persona->PerApellidos))),
                        'PerTelefono' => (string) ($persona->datos?->PerTelefono ?? ''),
                        'PerEmail' => (string) ($persona->datos?->PerEmail ?? ''),
                        'PerEstado' => (string) ($persona->PerEstado ?? ''),
                        'UsuarioEstado' => (string) ($persona->usuario?->UsuarioEstado ?? ''),
                        'Verificado' => (string) ($persona->usuario?->Verificado ?? ''),
                        'fechaRegistro' => $this->buildFechaRegistro($persona),
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (Exception $e) {
            Log::error('Error al cargar los datos de personas appmovil: '.$e->getMessage());

            return response()->json([
                'total' => 0,
                'rows' => [],
            ]);
        }
    }

    public function edit($id)
    {
        $persona = Persona::with(['datos', 'usuario', 'tipoDocumento'])->whereHas('usuario')->findOrFail($id);

        return view('gestionweb::appmovil.personas.editarPersona', compact('persona'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'PerApellidos' => 'required|string|max:50',
            'PerNombres' => 'required|string|max:50',
            'PerGenero' => 'required|string|max:15',
            'PerFecNac' => 'required',
            'PerFecExp' => 'required',
            'PerGruRh' => 'nullable|string|max:10',
            'PerEstado' => 'required|in:ACTIVO,INACTIVO',
            'PerTelefono' => 'required|string|max:20',
            'PerEmail' => [
                'required',
                'email',
                Rule::unique('_personas_datos', 'PerEmail')->ignore($id, 'IdPersona'),
            ],
            'PerDir' => 'nullable|string|max:150',
            'PerBar' => 'nullable|string|max:80',
            'UsuarioEstado' => 'required|in:ACTIVO,INACTIVO,SUSPENDIDO',
            'Verificado' => 'required|in:TRUE,FALSE',
            'Password' => 'nullable|string|min:8',
        ], [
            'PerEstado.in' => 'El estado de la persona es invalido.',
            'UsuarioEstado.in' => 'El estado del usuario es invalido.',
            'Verificado.in' => 'El valor de verificado es invalido.',
            'Password.min' => 'La contrasena debe tener minimo 8 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $persona = Persona::with(['datos', 'usuario'])->whereHas('usuario')->findOrFail($id);
        $usuario = $persona->usuario;
        $usuarioAuth = Auth::user();

        if (
            $request->PerEstado === 'INACTIVO'
            && $usuarioAuth
            && $usuarioAuth->persona
            && (int) $usuarioAuth->persona->IdPersona === (int) $persona->IdPersona
        ) {
            return toastModal('No puede cambiar a INACTIVO su propio registro de persona.', 'warning');
        }

        if (
            $request->UsuarioEstado !== 'ACTIVO'
            && $usuarioAuth
            && (int) $usuarioAuth->IdUsuario === (int) $usuario->IdUsuario
        ) {
            return toastModal('No puede cambiar a estado inactivo/suspendido su propio usuario.', 'warning');
        }

        DB::connection('mysql-gestion-admin')->beginTransaction();

        try {
            $persona->fill($request->only([
                'PerApellidos',
                'PerNombres',
                'PerGenero',
                'PerFecNac',
                'PerFecExp',
                'PerGruRh',
                'PerEstado',
            ]));
            $persona->save();

            $personaDatos = $persona->datos ?? new PersonaDatos(['IdPersona' => $persona->IdPersona]);
            $personaDatos->fill($request->only(['PerTelefono', 'PerEmail', 'PerDir', 'PerBar']));
            $personaDatos->PerFecUltAct = now();
            $personaDatos->PerFecReg = $personaDatos->PerFecReg ?? now();
            $personaDatos->PerHorReg = $personaDatos->PerHorReg ?? now();
            $personaDatos->PerAutTra = $personaDatos->PerAutTra ?? 'SI';
            $personaDatos->PerComDat = $personaDatos->PerComDat ?? 'SI';
            $personaDatos->PerConPol = $personaDatos->PerConPol ?? 'SI';
            $personaDatos->PerAutNot = $personaDatos->PerAutNot ?? 'SI';
            $personaDatos->save();

            $usuario->UsuarioEstado = $request->UsuarioEstado;
            $usuario->Verificado = $request->Verificado;

            if ($request->filled('Password')) {
                $usuario->Password = Hash::make((string) $request->Password);
            }

            $usuario->save();

            DB::connection('mysql-gestion-admin')->commit();

            return toastModal('Datos de persona actualizados correctamente.', 'success', route('personas-appmovil.index'));
        } catch (Exception $e) {
            DB::connection('mysql-gestion-admin')->rollBack();
            Log::error('Error al actualizar persona appmovil: '.$e->getMessage());

            return toastModal('Error al actualizar los datos de la persona.', 'error', route('personas-appmovil.index'));
        }
    }

    private function buildFechaRegistro(Persona $persona): string
    {
        $fecha = trim((string) $persona->PerFechReg);
        $hora = trim((string) $persona->PerHorReg);

        if ($fecha === '') {
            return '';
        }

        try {
            if ($hora !== '') {
                return Carbon::parse($fecha.' '.$hora)->format('Y-m-d H:i:s');
            }

            return Carbon::parse($fecha)->format('Y-m-d');
        } catch (Exception $e) {
            return trim($fecha.' '.$hora);
        }
    }
}
