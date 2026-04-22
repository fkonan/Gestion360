<?php

namespace App\Modules\GestionWeb\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CalcularRutaCommand extends Command
{
    protected $signature = 'ruta:calcular
                            {puntos* : Lista de puntos lat,lng separados por espacio. Ej: "4.71,-74.07" "4.90,-74.02"}';

    protected $description = 'Calcula distancia y duración usando Google Directions API según puntos dados';

    protected string $apiKey;

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = 'xx';
    }

    public function handle()
    {
        $stringsPuntos = $this->argument('puntos');

        if (count($stringsPuntos) < 2) {
            $this->error('Debes enviar mínimo dos puntos: origen y destino.');

            return Command::FAILURE;
        }

        // Convertir "4.71,-74.07" → ["lat" => 4.71, "lng" => -74.07]
        $puntos = array_map(function ($pair) {
            [$lat, $lng] = explode(',', $pair);

            return ['lat' => (float) $lat, 'lng' => (float) $lng];
        }, $stringsPuntos);

        // Llamar a la función
        $resultado = $this->calcularRutaGoogle($puntos);

        // Mostrar resultados
        $this->info('Distancia total: '.ceil($resultado['distancia_km']).' km');
        $this->info('Duración total: '.ceil($resultado['duracion_min']).' min');

        return Command::SUCCESS;
    }

    private function calcularRutaGoogle(array $puntos)
    {
        $origin = $puntos[0];
        $destination = end($puntos);

        $waypoints = array_slice($puntos, 1, -1);

        $params = [
            'origin' => "{$origin['lat']},{$origin['lng']}",
            'destination' => "{$destination['lat']},{$destination['lng']}",
            'travelMode' => 'driving',
            'key' => $this->apiKey,
        ];

        // Waypoints
        if (! empty($waypoints)) {
            $wpString = implode('|', array_map(fn ($p) => "{$p['lat']},{$p['lng']}", $waypoints));

            $params['waypoints'] = "optimize:true|$wpString";
        }

        // Llamada HTTP
        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', $params);

        if ($response->failed()) {
            return [
                'distancia_km' => 0,
                'duracion_min' => 0,
            ];
        }

        $data = $response->json();

        if (empty($data['routes'][0]['legs'])) {
            return [
                'distancia_km' => 0,
                'duracion_min' => 0,
            ];
        }

        $legs = $data['routes'][0]['legs'];

        // Sumar las distancias de todos los tramos
        $totalDistanciaMetros = 0;
        $totalDuracionSegundos = 0;

        foreach ($legs as $leg) {
            $totalDistanciaMetros += $leg['distance']['value'] ?? 0;
            $totalDuracionSegundos += $leg['duration']['value'] ?? 0;
        }

        return [
            'distancia_km' => round($totalDistanciaMetros / 1000, 2),
            'duracion_min' => round($totalDuracionSegundos / 60, 2),
            'legs' => $legs,
        ];
    }
}
