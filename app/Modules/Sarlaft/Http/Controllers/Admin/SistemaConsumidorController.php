<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\SistemaConsumidorRequest;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SistemaConsumidorController extends Controller
{
    /** Scope por defecto para sistemas en modo consulta. */
    private const SCOPE_CONSULTA_DEFECTO = 'sarlaft.listas.consultar';

    public function index(): View
    {
        $sistemas = SistemaConsumidor::latest()->paginate(20);

        return view('sarlaft::admin.sistemas-consumidores.index', compact('sistemas'));
    }

    public function store(SistemaConsumidorRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $secretPlano = null;

        // En modo consulta (JWT) se genera un client_secret aleatorio, se guarda
        // hasheado y se muestra UNA sola vez al admin. Si no se indicaron scopes,
        // se aplica el scope por defecto de consulta de listas.
        if (($datos['modo_integracion'] ?? null) === 'consulta') {
            if (trim((string) ($datos['scopes'] ?? '')) === '') {
                $datos['scopes'] = self::SCOPE_CONSULTA_DEFECTO;
            }
            $secretPlano = Str::random(48);
            $datos['client_secret'] = Hash::make($secretPlano);
        }

        $sistema = SistemaConsumidor::create([
            ...$datos,
            'api_token' => Str::random(64),
            'estado' => 'activo',
        ]);

        $redirect = redirect()
            ->route('sarlaft.sistemas-consumidores.index')
            ->with('success', 'Sistema consumidor creado correctamente.');

        return $this->conSecretSiAplica($redirect, $sistema, $secretPlano);
    }

    public function update(SistemaConsumidorRequest $request, SistemaConsumidor $sistemaConsumidor): RedirectResponse
    {
        $datos = $request->validated();

        // El client_secret no se edita por el formulario normal: se regenera con
        // su accion dedicada. Se descarta cualquier valor entrante.
        unset($datos['client_secret']);

        // Modo consulta sin scopes: aplicar el default para no dejarlo vacio.
        $modoFinal = $datos['modo_integracion'] ?? $sistemaConsumidor->modo_integracion;
        if ($modoFinal === 'consulta' && trim((string) ($datos['scopes'] ?? '')) === '') {
            $datos['scopes'] = self::SCOPE_CONSULTA_DEFECTO;
        }

        $sistemaConsumidor->update($datos);

        return redirect()
            ->route('sarlaft.sistemas-consumidores.index')
            ->with('success', 'Sistema consumidor actualizado correctamente.');
    }

    /**
     * Regenera el client_secret de un sistema en modo consulta. Invalida el
     * secret anterior y muestra el nuevo una sola vez.
     */
    public function regenerarSecret(SistemaConsumidor $sistemaConsumidor): RedirectResponse
    {
        // Solo los sistemas en modo consulta (JWT) usan client_secret.
        if ($sistemaConsumidor->modo_integracion !== 'consulta') {
            return redirect()
                ->route('sarlaft.sistemas-consumidores.index')
                ->with('warning', 'Solo los sistemas en modo Consulta (JWT) usan client secret.');
        }

        $secretPlano = Str::random(48);
        $sistemaConsumidor->update([
            'client_secret' => Hash::make($secretPlano),
        ]);

        $redirect = redirect()
            ->route('sarlaft.sistemas-consumidores.index')
            ->with('success', 'Client secret regenerado. El secret anterior dejo de ser valido.');

        return $this->conSecretSiAplica($redirect, $sistemaConsumidor, $secretPlano);
    }

    public function destroy(SistemaConsumidor $sistemaConsumidor): RedirectResponse
    {
        $sistemaConsumidor->delete();

        return redirect()
            ->route('sarlaft.sistemas-consumidores.index')
            ->with('success', 'Sistema consumidor eliminado correctamente.');
    }

    /**
     * Adjunta a la respuesta el secret en texto plano (una sola vez) para que el
     * admin lo copie y lo entregue a la empresa por canal seguro.
     */
    private function conSecretSiAplica(RedirectResponse $redirect, SistemaConsumidor $sistema, ?string $secretPlano): RedirectResponse
    {
        if ($secretPlano === null) {
            return $redirect;
        }

        return $redirect
            ->with('nuevo_client_id', $sistema->codigo)
            ->with('nuevo_client_secret', $secretPlano);
    }
}
