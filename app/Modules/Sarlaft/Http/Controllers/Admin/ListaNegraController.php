<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\ListaNegraRequest;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ListaNegraController extends Controller
{
    public function index(): View
    {
        $registros = ListaNegraInterna::with('creadoPor')
            ->latest()
            ->paginate(20);

        return view('sarlaft::admin.lista-negra.index', compact('registros'));
    }

    public function create(): View
    {
        return view('sarlaft::admin.lista-negra.create');
    }

    public function store(ListaNegraRequest $request): RedirectResponse
    {
        ListaNegraInterna::create([
            ...$request->validated(),
            'creado_por' => (int) auth()->id(),
        ]);

        return redirect()
            ->route('sarlaft.lista-negra.index')
            ->with('success', 'Registro agregado a la lista negra.');
    }

    public function show(ListaNegraInterna $listaNegra): View
    {
        $listaNegra->load('creadoPor');

        return view('sarlaft::admin.lista-negra.show', compact('listaNegra'));
    }

    public function edit(ListaNegraInterna $listaNegra): View
    {
        return view('sarlaft::admin.lista-negra.edit', compact('listaNegra'));
    }

    public function update(ListaNegraRequest $request, ListaNegraInterna $listaNegra): RedirectResponse
    {
        $listaNegra->update($request->validated());

        return redirect()
            ->route('sarlaft.lista-negra.show', $listaNegra)
            ->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(ListaNegraInterna $listaNegra): RedirectResponse
    {
        $listaNegra->delete();

        return redirect()
            ->route('sarlaft.lista-negra.index')
            ->with('success', 'Registro eliminado de la lista negra.');
    }
}
