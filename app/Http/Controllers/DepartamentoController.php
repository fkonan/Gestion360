<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Exception;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departamentos = Departamento::all(); 
        return view("departamentos.dataTable",compact("departamentos"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("departamentos.formDepartamentos");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {   
        try{
            $validated = $request->validate([
                'IdDepartamento' => 'required|integer|unique:_departamentos,IdDepartamento',
                'DepNom' => 'required|string|max:255',
                'DepNomMin' => 'required|string|max:255'
            ]);

            Departamento::create($validated);

            return redirect()->route('departamentos.index');
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $departamentos = Departamento::where("IdDepartamento",$id)->get();
        return view("departamentos.dataTable",compact("departamentos"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $departamento = Departamento::findOrFail($id);
        return view("departamentos.editForm",compact("departamento"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'DepNom' => 'required|string|max:255',
            'DepNomMin' => 'required|string|max:255'
        ]);

        Departamento::findOrFail($id)->update($validated);

        return redirect()->route('departamentos.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Departamento::findOrFail($id)->delete();
        return redirect()->route('departamentos.index');
    }

    public function getMunici($idDepar){
        $municipios = Departamento::findOrFail($idDepar)->municipios;
        return response()->json($municipios);
    }
}
