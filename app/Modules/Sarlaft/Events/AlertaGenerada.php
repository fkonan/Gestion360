<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Events;

use App\Modules\Sarlaft\Models\Alerta;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertaGenerada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Alerta $alerta,
    ) {}
}
