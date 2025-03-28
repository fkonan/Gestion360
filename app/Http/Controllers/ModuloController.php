<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class ModuloController extends Controller
{
    public function index(){
        $modulos = Modulo::with('padre')->get();  
        return view('modulos.listaModulos', compact('modulos'));
    }

    public function create(){
        if(!request()->ajax()){
            return $this->index();
        }

        $permisos = Permission::all();
        $modulos = Modulo::with('submodulos')->whereNull('Mod_Padre_Id')->get();

        return view('modulos.crearModulo', compact('modulos','permisos'))->render();
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'ModNom' =>'unique:modulos,ModNom|required|string|max:50',
            'ModDesc' =>'nullable|string|max:300',
            'ModEstado' =>'required',
            'ModRuta' =>'nullable|string|max:50',
            'ModPermiso' =>'nullable|string|max:50',
            'ModIcono' =>'nullable|string|max:10',
            'Mod_Padre_Id' =>'nullable|integer'
        ],[
            'ModNom.unique' => 'El nombre del modulo ya existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $modulo = new Modulo();
        $modulo->ModNom = $request->ModNom;
        $modulo->ModDesc = $request->ModDesc;
        $modulo->ModEstado = $request->ModEstado;
        $modulo->ModRuta = $request->ModRuta;
        $modulo->ModIcono = $request->ModIcono;
        $modulo->Mod_Padre_Id = $request->Mod_Padre_Id;
        $modulo->ModPermiso = $request->ModPermiso;
        $modulo->ModFechReg = now();
        $modulo->ModHorReg = now();
        $modulo->save();

        return response()->json([
            'title' => 'Modulo creado exitosamente',
            'redirect' => route('modulos.index'),
            'type' => 'success', 
        ]); 
    }

    public function edit($id){
        if(!request()->ajax()){
            return $this->index();
        }

        $modulos = Modulo::with('submodulos')->whereNull('Mod_Padre_Id')->get();
        $moduloEdit = $modulos->firstWhere('IdModulo', $id) ?? Modulo::findOrFail($id);  
        $permisos = Permission::select('id', 'name')->get();

        return view('modulos.editarModulo', compact('modulos', 'moduloEdit', 'permisos'))->render();
    }

    public function update(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'ModNom' =>'required|string|max:50',
            'ModDesc' =>'nullable|string|max:300',
            'ModEstado' =>'required',
            'ModPermiso' =>'nullable|string|max:255',
            'ModRuta' =>'nullable|string|max:255',
            'ModIcono' =>'nullable|string|max:255',
            'Mod_Padre_Id' =>'nullable|integer'
        ],[
            'ModNom.unique' => 'El nombre del modulo ya existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        Modulo::findOrFail($id)->update($request->all());

        return response()->json([
            'title' => 'Modulo actualizado exitosamente',
            'redirect' => route('modulos.index'),
            'type' => 'success', 
        ]); 
    }

    //Vista principal del modulo Gestion sistema
    public function getGestionSistema(){
        return view('modulos.gestionSistema');
    }

    public function getGestionEmpleado(){
        return view('modulos.gestionEmpleado');
    }
}


 