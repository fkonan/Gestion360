<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Persona;
use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonaController extends Controller
{
    public function index()
    {
        $personas = Persona::all();
        return view("persona.listaPersonas",compact("personas"));
    }

    public function create()
    {
        $personas = Persona::all();
        if(!request()->ajax()){
            return view("persona.listaPersonas",compact("personas"));
        }

        $departamentos = Departamento::all();
        $tiposDocumento = TipoDocumento::all();    
        
        return view("persona.crearPersona",compact("departamentos","tiposDocumento"))->render();
    }

    public function store(Request $request){
    try{       
        //reglas de validación
        $validator=Validator::make(
            $request->all(),[
                'PerTipoDoc' => 'required',
                'PerNumDoc' => 'unique:_personas,PerNumDoc|required|string|max:10',
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
                'redirect' => route('admin.personas'),
                'type' => 'success', 
                'title' => 'Persona creada exitosamente'
            ]); 
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        } 
    }

    public function show($id)
    {
        $personas = Persona::where("IdPersona",$id)->get();
        return view("persona.listaPersonas",compact("personas"));
    }

    public function edit($id)
    {
        $personas = Persona::all();
        if(!request()->ajax()){
            return view("persona.listaPersonas",compact("personas"));
        }

        $persona = Persona::findOrFail($id);
        $departamentos = Departamento::all();
        $tiposDocumento = TipoDocumento::all();   
        
        return view("persona.editarPersona",compact("persona","tiposDocumento","departamentos"))->render();
    }

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

            return response()->json([
                'redirect' => route('admin.personas'),
                'type' => 'success', 
                'title' => 'Persona modificada exitosamente'
            ]); 

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
