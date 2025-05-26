<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Departamento;
use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\PersonaDatos;
use App\Models\GESTIONADMIN\TipoDocumento;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
    public function index(){
        return view("personas.listaPersonas");
    }

    public function cambiarEstado($id){
        try{
            $persona = Persona::findOrFail($id);
            $persona->PerEstado = $persona->PerEstado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
            $persona->save();
            return sweetAlertJson("Estado cambiado a {$persona->PerEstado}", "success");
        }catch(Exception $e){
            Log::error('Error al actualizar el estado de la persona: ' . $e->getMessage());
            return sweetAlertJson("Error al actualizar el estado de la persona", "error");
        }
    } 

    public function cargarDatos(Request $request){
        //paginacion
        $limit = $request->get('limit', 25); // Número de registros por página
        $offset = $request->get('offset', 0); // Desde qué registro empezar
        $search = $request->get('search');
        $sort = $request->get('sort', 'PerFechReg');
        $order = $request->get('order', 'desc');

        $personas = Persona::with(['municipioNac.departamento', 'datos']);

        //buscador
        if (!empty($search)) {
            $personas->where(function ($q) use ($search) {
                $q->where('PerFechReg', 'like', "%$search%")
                    ->orWhere('PerNombres', 'like', "%$search%")
                    ->orWhere('PerApellidos', 'like', "%$search%")
                    ->orWhere('PerNumDoc', 'like', "%$search%")
                    ->orWhereHas('municipioNac.departamento', function ($q2) use ($search) {
                        $q2->where('DepNom', 'like', "%$search%");
                    })
                    ->orWhereHas('datos', function ($q3) use ($search) {
                        $q3->where('PerTelefono', 'like', "%$search%");
                    });
            });
        }

         //datos de la pagina 
        $total = $personas->count();
        $rows = $personas->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
         'total' => $total,
         'rows' => $rows
      ]);

    }

    public function create(){
        $departamentos = Departamento::with('municipios')->get();
        $tiposDocumento = TipoDocumento::select('id','nombre')->get();    
        
        return view("personas.crearPersona",compact("departamentos","tiposDocumento"))->render();
    }

    public function store(Request $request){
          
        $validator=Validator::make(
            $request->all(),[
                'PerTipoDoc' => 'required',
                'PerNumDoc' => 'unique:_personas,PerNumDoc|required|string|max:10',
                'PerTelefono' => 'unique:_personas_datos,PerTelefono|required',
                'PerEmail' => 'unique:_personas_datos,PerEmail|required|email',
                'PerApellidos' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
                'PerNombres' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
                'PerGenero' => 'required',
                'PerFecNac' => 'required',
                'PerLugNac' => 'required',
                'PerFecExp' => 'required',  
                'PerLugExp' => 'required',
                'PerGruRh' => 'nullable',
            ],[
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
                'PerTelefono.unique' => 'El número de telefono ya esta registrado',
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

        try{ 
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
            $persona->PerGruRh = $request->PerGruRh;
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
            return sweetAlertJson("Persona creada exitosamente", "success",route('personas.index'));

        }catch(Exception $e){
            DB::rollBack();
            Log::error('Error al crear la persona: ' . $e->getMessage());

            return sweetAlertJson("Error al crear la persona", "error",route('personas.index'));
        } 
    }

    public function show($id){
        $personas = Persona::where("IdPersona",$id)->get();
        return view("personas.listaPersonas",compact("personas"));
    }

    public function edit($id){
        $persona = Persona::findOrFail($id);
        $departamentos = Departamento::with('municipios')->get();
        $tiposDocumento = TipoDocumento::select('id','nombre')->get();   
        
        return view("personas.editarPersona",compact("persona","tiposDocumento","departamentos"))->render();
    }

    public function update(Request $request, $id)
    {   
        try{
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

            if($request->PerEstado == 'Inactivo' && $personaActualizar->IdPersona == $personaLogeada->persona->IdPersona){
                return sweetAlertJson("No puede cambiar a estado INACTIVO a su propio registro", "warning");
            } 

            DB::beginTransaction();
            $personaActualizar->update($request->only(['PerApellidos', 'PerNombres','PerGenero',
                'PerFecNac','PerLugNac','PerFecExp','PerLugExp','PerGruRh','PerEstado'])); 

            $personaDatos->fill($request->only(['PerTelefono', 'PerEmail', 'PerDir','PerBar','PerMunRes']));  
            $personaDatos->PerFecUltAct = now();
            $personaDatos->save();
            DB::commit();

            return sweetAlertJson("Persona modificada exitosamente", "success",route('personas.index'));

        }catch(Exception $e){
            DB::rollBack();
            Log::error('Error al modificar la persona: ' . $e->getMessage());

            return sweetAlertJson("Error al modificar la persona", "error",route('personas.index'));  
        }
    }
}
