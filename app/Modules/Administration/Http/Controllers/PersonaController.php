<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Models\GESTIONADMIN\Departamento;
use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\PersonaDatos;
use App\Models\GESTIONADMIN\TipoDocumento;
use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
  public function index()
  {
    return view("personas.index");
  }

  public function cambiarEstado($id)
  {
    try {
      //Validar que no se cambie a estado inactivo asi mismo
      $persona = Persona::findOrFail($id);
      $personaLogeada = Auth::user();

      if ($persona->IdPersona == $personaLogeada->persona->IdPersona) {
        return response()->json([
          'message' => 'No puede cambiar a estado INACTIVO a su propio registro',
          'type' => 'warning'
        ]);
      }

      // Cambiar el estado de la persona
      $persona->PerEstado = $persona->PerEstado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
      $persona->save();
      return response()->json([
        'message' => 'Estado cambiado a ' . $persona->PerEstado,
        'type' => 'success'
      ]);
    } catch (Exception $e) {
      Log::error('Error al actualizar el estado de la persona: ' . $e->getMessage());
      return response()->json([
        'message' => 'Error al actualizar el estado de la persona',
        'type' => 'danger'
      ]);
    }
  }

  public function cargarDatos(Request $request)
  {
    try {
      // 1. Obtener documentos válidos desde Oracle (solo empleados activos) y se cachean por 5 minutos
      $documentosEmpleados = EmpleadoService::documentosEmpleadosValidos();

      // 2. Paginación y parámetros
      $limit = $request->get('limit', 25);
      $offset = $request->get('offset', 0);
      $search = $request->get('search');
      $order = $request->get('order', 'desc');
      $sort = $request->get('sort');

      // 3. Consulta con relaciones y filtro por documentos válidos
      $personas = Persona::with(['municipioNac.departamento', 'datos'])
        ->whereIn('PerNumDoc', $documentosEmpleados);

      // 4. Ordenamiento
      if ($sort === 'PerFechaHoraReg') {
        $personas = $personas
          ->orderBy('PerFechReg', $order)
          ->orderBy('PerHorReg', $order);
      }

      // 5. Buscador
      if (!empty($search)) {
        $personas->where(function ($q) use ($search) {
          $q->where('PerFechReg', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(PerNombres, ' ', PerApellidos)"), 'like', "%$search%")
            ->orWhere('PerNumDoc', 'like', "%$search%")
            ->orWhereHas('municipioNac.departamento', function ($q2) use ($search) {
              $q2->where('DepNom', 'like', "%$search%");
            })
            ->orWhereHas('datos', function ($q3) use ($search) {
              $q3->where('PerTelefono', 'like', "%$search%")
                ->orWhere('PerEmail', 'like', "%$search%");
            });
        });
      }

      // 6. Total y paginación
      $total = $personas->count();
      $rows = $personas
        ->skip($offset)
        ->take($limit)
        ->get()
        ->map(function ($item) {
          return [
            'PerNumDoc' => $item->PerNumDoc,
            'PerEmail' => $item->datos->PerEmail ?? '',
            'nombreCompleto' => ucfirst(strtolower($item->PerNombres . ' ' . $item->PerApellidos)),
            'PerTelefono' => $item->datos->PerTelefono ?? '',
            'PerEstado' => $item->PerEstado,
            'PerFechaHoraReg' => $item->PerFechReg . ' ' . $item->PerHorReg,
            'IdPersona' => $item->IdPersona,
          ];
        });

      return response()->json([
        'total' => $total,
        'rows' => $rows
      ]);
    } catch (Exception $e) {
      Log::error('Error al cargar los datos de las personas: ' . $e->getMessage());
    }
  }

  public function create()
  {
    $departamentos = Departamento::with('municipios')->get();
    $tiposDocumento = TipoDocumento::select('id', 'nombre')->get();

    return view("personas.crearPersona", compact("departamentos", "tiposDocumento"))->render();
  }

  public function store(Request $request)
  {

    $validator = Validator::make(
      $request->all(),
      [
        'PerTipoDoc' => 'required',
        'PerNumDoc' => 'unique:_personas,PerNumDoc|required|string|max:10',
        'PerTelefono' => 'required',
        'PerEmail' => 'required|email',
        'PerApellidos' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
        'PerNombres' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
        'PerGenero' => 'required',
        'PerFecNac' => 'required',
        'PerLugNac' => 'required',
        'PerFecExp' => 'required',
        'PerLugExp' => 'required',
        'PerGruRh' => 'nullable',
      ],
      [
        'PerFecNac.required' => 'La fecha de nacimiento es requerida',
        'PerFecExp.required' => 'La fecha de expedición es requerida',

        'PerApellidos.required' => 'Los apellidos son requeridos',
        'PerApellidos.regex' => 'Los apellidos solo pueden contener letras y espacios',

        'PerNombres.required' => 'Los nombres son requeridos',
        'PerNombres.regex' => 'Los nombres solo pueden contener letras y espacios',

        'PerTipoDoc.required' => 'El tipo de documento es requerido',
        'PerNumDoc.required' => 'El número de documento es requerido',
        'PerGenero.required' => 'El género es requerido',

        'PerNumDoc.unique' => 'El número de documento ya esta registrado',
        'PerTelefono.required' => 'El teléfono es requerido',

        'PerEmail.required' => 'El correo electrónico es requerido',
        'PerEmail.email' => 'El correo electrónico debe ser válido',
        'PerEmail.unique' => 'El correo ya esta registrado',
      ]
    );

    //manejo de errores
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    DB::beginTransaction();

    try {
      $persona = new Persona();
      $persona->PerTipoDoc = $request->PerTipoDoc;
      $persona->PerNumDoc = $request->PerNumDoc;
      $persona->PerApellidos = $request->PerApellidos;
      $persona->PerNombres = $request->PerNombres;
      $persona->PerGenero = $request->PerGenero;
      $persona->PerFecNac = $request->PerFecNac;
      $persona->PerLugNac = $request->PerLugNac;
      $persona->PerFecExp = $request->PerFecExp;
      $persona->PerLugExp = $request->PerLugExp;
      $persona->PerGruRh = $request->PerGruRh ?? null;
      $persona->PerFechReg = now();
      $persona->PerHorReg = now();
      $persona->save();

      $personaDatos = new PersonaDatos();
      $personaDatos->IdPersona = $persona->IdPersona;
      $personaDatos->PerTelefono = $request->PerTelefono;
      $personaDatos->PerEmail = $request->PerEmail;
      $personaDatos->PerDir = $request->PerDir;
      $personaDatos->PerBar = $request->PerBar;
      $personaDatos->PerMunRes = $request->PerMunRes;
      $personaDatos->PerFecReg = now();
      $personaDatos->PerHorReg = now();
      $personaDatos->PerFecUltAct = now();
      $personaDatos->PerAutTra = "SI";
      $personaDatos->PerComDat = "SI";
      $personaDatos->PerConPol = "SI";
      $personaDatos->PerAutNot = "SI";
      $personaDatos->save();

      DB::commit();
      return toastModal("Persona creada exitosamente", "success", route('personas.index'));
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error al crear la persona: ' . $e->getMessage());

      return toastModal("Error al crear la persona", "error", route('personas.index'));
    }
  }

  public function show($id)
  {
    $personas = Persona::where("IdPersona", $id)->get();
    return view("personas.index", compact("personas"));
  }

  public function edit($id)
  {
    $persona = Persona::findOrFail($id);
    $departamentos = Departamento::with('municipios')->get();
    $tiposDocumento = TipoDocumento::select('id', 'nombre')->get();

    return view("personas.editarPersona", compact("persona", "tiposDocumento", "departamentos"))->render();
  }

  public function update(Request $request, $id)
  {
    try {
      $validator = Validator::make($request->all(), [
        'PerApellidos' => 'required|string|max:50',
        'PerNombres' => 'required|string|max:50',
        'PerEmail' => [
          'required',
          'email',
          Rule::unique('_personas_datos', 'PerEmail')->ignore($id, 'IdPersona')
        ],
        'PerGenero' => 'required|string|max:15',
        'PerFecNac' => 'required',
        'PerLugNac' => 'required',
        'PerFecExp' => 'required',
        'PerLugExp' => 'required',
        'PerGruRh' => 'nullable',
        'PerEstado' => 'required'
      ]);

      if ($validator->fails()) {
        return response()->json([
          'errors' => $validator->errors()
        ], 422);
      }

      //Validar que no se cambie a estado inactivo asi mismo
      $personaActualizar = Persona::findOrFail($id);
      $personaDatos = $personaActualizar->datos;
      $personaLogeada = Auth::user();

      if ($request->PerEstado == 'Inactivo' && $personaActualizar->IdPersona == $personaLogeada->persona->IdPersona) {
        return toastModal("No puede cambiar a estado INACTIVO a su propio registro", "warning");
      }

      DB::beginTransaction();
      $personaActualizar->update($request->only([
        'PerApellidos',
        'PerNombres',
        'PerGenero',
        'PerFecNac',
        'PerLugNac',
        'PerFecExp',
        'PerLugExp',
        'PerGruRh',
        'PerEstado'
      ]));

      $personaDatos->fill($request->only(['PerTelefono', 'PerEmail', 'PerDir', 'PerBar', 'PerMunRes']));
      $personaDatos->PerFecUltAct = now();
      $personaDatos->save();
      DB::commit();

      return toastModal("Persona modificada exitosamente", "success", route('personas.index'));
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error al modificar la persona: ' . $e->getMessage());

      return toastModal("Error al modificar la persona", "error", route('personas.index'));
    }
  }
}
