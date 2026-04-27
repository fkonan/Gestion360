<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\UpdatePoliticaRequest;
use App\Modules\Sarlaft\Services\PoliticaSarlaftService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PoliticaController extends Controller
{
    public function __construct(
        private readonly PoliticaSarlaftService $politicaSarlaftService,
    ) {}

    public function edit(): View
    {
        $politica = $this->politicaSarlaftService->obtener();

        return view('sarlaft::admin.politicas.edit', compact('politica'));
    }

    public function update(UpdatePoliticaRequest $request): RedirectResponse
    {
        $this->politicaSarlaftService->guardar(
            $request->validated(),
            auth()->id() !== null ? (int) auth()->id() : null,
        );

        return redirect()
            ->route('sarlaft.politicas.edit')
            ->with('success', 'Politicas SARLAFT actualizadas correctamente.');
    }
}
