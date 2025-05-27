<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Modulo;
use App\Models\GESTIONADMIN\SubModulo;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class SubModuloController extends Controller
{
    public function index(){
        $subModulos = SubModulo::all();

        return view('submodulos.listaSubModulos', compact('subModulos'));
    }

    public function create(){
        $permisos = Permission::all();
        $modulos = Modulo::all();

        return view('submodulos.crearSubModulo', compact('modulos','permisos'))->render();
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'ModNom' =>'unique:_submodulos,SubModNom|required|string|max:50',
            'ModDesc' =>'nullable|string|max:300',
            'ModRuta' =>'nullable|string|max:50',
            'ModIcono' =>'nullable|string|max:20',
            'Mod_Padre_Id' =>'integer'
        ],[
            'ModNom.unique' => 'El nombre del submodulo ya existe.',
            'ModNom.required' => 'El nombre del submodulo es requerido.',
            'ModNom.max' => 'El nombre del submodulo no puede tener más de 50 caracteres.',
            'ModDesc.max' => 'La descripción no puede tener más de 300 caracteres.',
            'ModIcono.max' => 'El icono no puede tener más de 20 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $modulo = new SubModulo();
            $modulo->ModuloId = $request->Mod_Padre_Id;
            $modulo->ModNom = strtoupper($request->ModNom);
            $modulo->ModDesc = $request->ModDesc;
            $modulo->ModEstado = "ACTIVO";
            $modulo->ModRuta = $request->ModRuta;
            $modulo->ModIcono = $request->ModIcono;
            $modulo->ModPermiso = null;
            $modulo->ModFechReg = now();
            $modulo->ModHorReg = now();
            $modulo->save();

            return sweetAlertJson("Submodulo creado exitosamente", "success",route('submodulos.index'));

        }catch(Exception $e){
            Log::error('Error al crear el submodulo: ' . $e->getMessage());
            return sweetAlertJson("Error al crear el submodulo", "error",route('submodulos.index'));
        }
    }

    public function edit($id){
        $moduloEdit = SubModulo::findOrFail($id);  
        $modulos = Modulo::all();
        $permisos = Permission::select('id', 'name')->get();

        return view('submodulos.editarSubModulos', compact('moduloEdit', 'permisos','modulos'))->render();
    }

    public function update(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'SubModNom' =>'required|string|max:50',
            'SubModDes' =>'nullable|string|max:300',
            'SubModuloEstado' =>'required',
            'SubModPermiso' =>'nullable|string|max:255',
            'SubModRuta' =>'nullable|string|max:255',
            'SubModIcono' =>'nullable|string|max:255',
            'ModuloId' =>'integer'
        ],[
            'SubModNom.unique' => 'El nombre del modulo ya existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            SubModulo::findOrFail($id)->update($request->all());
            return sweetAlertJson("SubModulo actualizado exitosamente", "success",route('submodulos.index'));
            
        }catch(Exception $e){
            Log::error('Error al actualizar el submodulo: ' . $e->getMessage());
            return sweetAlertJson("Error al actualizar el submodulo", "error",route('submodulos.index'));
        }
    }

    public function cambiarEstado($id){
        try{
            $submodulo = SubModulo::findOrFail($id);
            $submodulo->SubModuloEstado = $submodulo->SubModuloEstado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
            $submodulo->save();
            return sweetAlertJson("Estado cambiado a {$submodulo->SubModuloEstado}", "success");
        }catch(Exception $e){
            Log::error('Error al actualizar el estado del submodulo: ' . $e->getMessage());
            return sweetAlertJson("Error al actualizar el estado del submodulo", "error");
        }
    }


}


 