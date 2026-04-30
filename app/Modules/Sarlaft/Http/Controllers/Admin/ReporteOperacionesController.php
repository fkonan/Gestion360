<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Services\ReporteOperacionesService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReporteOperacionesController extends Controller
{
    public function __construct(
        private readonly ReporteOperacionesService $reporteOperacionesService,
    ) {}

    public function index(Request $request): View
    {
        return view(
            'sarlaft::admin.reportes.operaciones.index',
            $this->reporteOperacionesService->construirReporte($request->query())
        );
    }
}
