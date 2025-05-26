<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Modulo;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class ModuloController extends Controller
{
    public function index(){
        $modulos = Modulo::all();
        return view('modulos.listaModulos', compact('modulos'));
    }

    public function create(){
        $permisos = Permission::all();
        $modulos = Modulo::with('submodulos')->get();

        return view('modulos.crearModulo', compact('modulos','permisos'))->render();
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'ModNom' =>'unique:_modulos,ModNom|required|string|max:50',
            'ModDes' =>'nullable|string|max:300',
            'ModRuta' =>'nullable|string|max:50',
            'ModIcono' =>'nullable|string|max:20',
        ],[
            'ModNom.unique' => 'El nombre del modulo ya existe.',
            'ModNom.required' => 'El nombre del modulo es requerido.',
            'ModNom.max' => 'El nombre del modulo no puede tener más de 50 caracteres.',
            'ModDes.max' => 'La descripción no puede tener más de 300 caracteres.',
            'ModIcono.max' => 'El icono no puede tener más de 20 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $modulo = new Modulo();
            $modulo->ModNom = $request->ModNom;
            $modulo->ModDesc = $request->ModDes;
            $modulo->ModEstado = "ACTIVO";
            $modulo->ModRuta = $request->ModRuta;
            $modulo->ModIcono = $request->ModIcono;
            $modulo->ModPermiso = null;
            $modulo->ModFechReg = now();
            $modulo->ModHorReg = now();
            $modulo->save();

            return sweetAlertJson("Modulo creado exitosamente","success",route('modulos.index'));

        }catch(Exception $e){
            Log::error('Error al crear el modulo: ' . $e->getMessage());
            return sweetAlertJson("Error al crear el modulo","error",route('modulos.index'));
        }
    }

    public function edit($id){
        $moduloEdit = Modulo::findOrFail($id);  
        $permisos = Permission::select('id', 'name')->get();

        return view('modulos.editarModulo', compact('moduloEdit', 'permisos'))->render();
    }

    public function update(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'ModNom' =>'required|string|max:50',
            'ModDes' =>'nullable|string|max:300',
            'ModuloEstado' =>'required',
            'ModPermiso' =>'nullable|string|max:255',
            'ModRuta' =>'nullable|string|max:255',
            'ModIcono' =>'nullable|string|max:255',
        ],[
            'ModNom.unique' => 'El nombre del modulo ya existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            Modulo::findOrFail($id)->update($request->all());
            return sweetAlertJson("Modulo actualizado exitosamente", "success",route('modulos.index'));
            
        }catch(Exception $e){
            Log::error('Error al actualizar el modulo: ' . $e->getMessage());
            return sweetAlertJson("Error al actualizar el modulo", "error",route('modulos.index'));
        }
    }

    public function cambiarEstado($id){
        try{
            $modulo = Modulo::findOrFail($id);
            $modulo->ModuloEstado = $modulo->ModuloEstado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
            $modulo->save();
            return sweetAlertJson("Estado cambiado a {$modulo->ModuloEstado}", "success");
        }catch(Exception $e){
            Log::error('Error al actualizar el estado del modulo: ' . $e->getMessage());
            return sweetAlertJson("Error al actualizar el estado del modulo", "error");
        }
    }

    //Desplega el menu con las opciones de cada modulo
    public function getGestionSistema(){
        return view('modulos.gestionSistema');
    }

    public function getGestionEmpleado(){
        return view('modulos.gestionEmpleado');
    }

    public function getReportes(){
        return view('modulos.reportes');
    }
}


 