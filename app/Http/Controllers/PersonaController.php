<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Persona;
use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $personas = Persona::all();
        return view("persona.dataTable",compact("personas"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departamentos = Departamento::all();
        $tiposDocumento = TipoDocumento::all();     
        return view("persona.crearPersona",compact("departamentos","tiposDocumento"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
    try{       
        //reglas de validación
        $validator=Validator::make(
            $request->all(),[
                'PerTipoDoc' => 'required',
                'PerNumDoc' => 'required|string|max:20',
                'PerApellidos' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
                'PerNombres' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
                'PerGenero' => 'required|string|max:15',
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
            ]
            );
            
            //manejo de errores
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

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
            
            return response()->json([
                'message' => 'Persona creada exitosamente',
                'redirect' => route('persona.index')
            ]); 
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        } 
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $personas = Persona::where("IdPersona",$id)->get();
        return view("persona.dataTable",compact("personas"));
    }

    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $persona = Persona::findOrFail($id);
        $departamentos = Departamento::all();
        $tiposDocumento = TipoDocumento::all();   
        
        return view("persona.editarPersona",compact("persona","tiposDocumento","departamentos"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {   
        try{
            $validator = Validator::make($request->all(), [
                'PerApellidos' => 'required|string|max:50',
                'PerNombres' => 'required|string|max:50',
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

            Persona::findOrFail($id)->update($request->all());

            /*
            return response()->json([
                'message' => 'Persona modificada exitosamente',
                'redirect' => route('persona.index')
            ]);*/

            return redirect()->route('persona.index')->with('success','Persona modificada exitosamente');

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
