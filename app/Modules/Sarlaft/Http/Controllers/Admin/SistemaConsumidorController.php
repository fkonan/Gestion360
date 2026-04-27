<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\SistemaConsumidorRequest;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class SistemaConsumidorController extends Controller
{
    public function index(): View
    {
        $sistemas = SistemaConsumidor::latest()->paginate(20);

        return view('sarlaft::admin.sistemas-consumidores.index', compact('sistemas'));
    }

    public function store(SistemaConsumidorRequest $request): RedirectResponse
    {
        SistemaConsumidor::create([
            ...$request->validated(),
            'api_token' => Str::random(64),
            'estado' => 'activo',
        ]);

        return redirect()
            ->route('sarlaft.sistemas-consumidores.index')
            ->with('success', 'Sistema consumidor creado correctamente.');
    }

    public function update(SistemaConsumidorRequest $request, SistemaConsumidor $sistemaConsumidor): RedirectResponse
    {
        $sistemaConsumidor->update($request->validated());

        return redirect()
            ->route('sarlaft.sistemas-consumidores.index')
            ->with('success', 'Sistema consumidor actualizado correctamente.');
    }

    public function destroy(SistemaConsumidor $sistemaConsumidor): RedirectResponse
    {
        $sistemaConsumidor->delete();

        return redirect()
            ->route('sarlaft.sistemas-consumidores.index')
            ->with('success', 'Sistema consumidor eliminado correctamente.');
    }
}
