<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Error;
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
        $validated = $request->validate([
            'IdDepartamento' => 'required|integer',
            'DepNom' => 'required|string|max:255',
            'DepNomMin' => 'required|string|max:255'
        ]);

        Departamento::create([
            'IdDepartamento' => $validated['IdDepartamento'],
            'DepNom' => $validated['DepNom'],
            'DepNomMin' => $validated['DepNomMin']
        ]);

        return redirect()->route('departamentos.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $departamentos = Departamento::where("IdDepartamento",$id)->first();
        return view("departamentos.dataTable",compact("departamentos"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $departamento = Departamento::where("IdDepartamento",$id)->first();
        return view("departamentos.editForm",compact("departamento"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try{
            $validated = $request->validate([
                'DepNom' => 'required|string|max:255',
                'DepNomMin' => 'required|string|max:255'
            ]);
    
            $id = $request->input('IdDepartamento');
    
            $departamento = Departamento::where("IdDepartamento",$id)->first();
    
            $departamento->update([
                'DepNom' => $validated['DepNom'],
                'DepNomMin' => $validated['DepNomMin']
            ]);
    
            return redirect()->route('departamentos.index');
        } catch (\Exception $e) {
            
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Departamento::where("IdDepartamento",$id)->delete();
        return redirect()->route('departamentos.index');
    }
}
