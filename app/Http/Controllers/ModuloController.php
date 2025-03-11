<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class ModuloController extends Controller
{
    public function index(){
        $modulos = Modulo::all();  
        return view('modulo.listaModulos', compact('modulos'));
    }

    public function create(){
        $modulos = Modulo::all();  
        return view('modulo.crearModulo', compact('modulos'));
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'ModNom' =>'required|string|max:50',
            'ModDesc' =>'nullable|string|max:300',
            'ModEstado' =>'required',
            'ModRuta' =>'nullable|string|max:255',
            'ModPermiso' =>'nullable|string|max:255',
            'ModIcono' =>'nullable|string|max:255',
            'Mod_Padre_Id' =>'nullable|integer'
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
        $modulos = Modulo::all();
        $moduloEdit = Modulo::findOrFail($id);  
        $permisos = Permission::all();

        if (request()->ajax()) {
            return view('modulo.editarModulo', compact('modulos', 'moduloEdit', 'permisos'))->render();
        }

        return view('modulo.editarModulo', compact('modulos', 'moduloEdit', 'permisos'));
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
}
