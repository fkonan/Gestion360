<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Events;

use App\Modules\Sarlaft\Models\Consulta;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultaRealizada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Consulta $consulta,
    ) {}
}
