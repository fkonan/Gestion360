<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class IngresoSalidaConducService
{   
    public function formatoEspecialReporte($resultados){
        try{
            $agrupadosPorConductor = collect($resultados)->groupBy('identificacion');
            $coleccionFinal = [];

            foreach ($agrupadosPorConductor as $identificacion => $eventosConductor) {
                $eventosOrdenados = $eventosConductor->sortBy('fecha_evento')->values();
                $registros = [];
                $ultimoRegistroIndex = null;

                foreach ($eventosOrdenados as $index => $evento) {
                    $eventoNombreNormalizado = strtoupper(trim($evento->evento));

                    $esSalida = $eventoNombreNormalizado === 'SALIDA A DESCANSO';
                    $esReintegro = in_array($eventoNombreNormalizado, [
                        'REINTEGRO DE DESCANSO',
                        'REGRESO DE DESCANSO',
                    ]);

                    $fechaEvento = $evento->fecha_evento;
                    $agenciaEvento = trim($evento->agencia_registra_evento);

                    // Inicializamos un nuevo registro base
                    $registroBase = [
                        'identificacion' => $evento->identificacion,
                        'codigo' => $evento->codigo,
                        'nombre_completo' => $evento->nombre_completo,
                        'vehiculo' => $evento->vehiculo,
                        'nombre_asociado' => $evento->nombre_asociado,
                        'fecha_salida' => null,
                        'fecha_reintegro' => null,
                        'agencia_salida' => null,
                        'agencia_reintegro' => null,
                        'dias_descanso' => null,
                    ];

                    if ($index === 0) {
                        // Primer evento del conductor
                        if ($esSalida) {
                            $registroBase['fecha_salida'] = $fechaEvento;
                            $registroBase['agencia_salida'] = $agenciaEvento;
                        } elseif ($esReintegro) {
                            $registroBase['fecha_reintegro'] = $fechaEvento;
                            $registroBase['agencia_reintegro'] = $agenciaEvento;
                        }
                        $registros[] = $registroBase;
                        $ultimoRegistroIndex = 0;
                    } else {
                        $eventoAnteriorNormalizado = strtoupper(trim($eventosOrdenados[$index - 1]->evento));

                        if ($eventoAnteriorNormalizado !== $eventoNombreNormalizado) {
                            if ($eventoAnteriorNormalizado === 'SALIDA A DESCANSO' && $esReintegro) {
                                // Actualiza el último registro con fecha de reintegro
                                $registros[$ultimoRegistroIndex]['fecha_reintegro'] = $fechaEvento;
                                $registros[$ultimoRegistroIndex]['agencia_reintegro'] = $agenciaEvento;

                                // Calcula los días de descanso (redondeando al entero superior)
                                $salida = $registros[$ultimoRegistroIndex]['fecha_salida'];
                                if ($salida) {
                                    $horas = Carbon::parse($salida)->diffInHours(Carbon::parse($fechaEvento));
                                    $dias = (int) ceil($horas / 24);
                                    $registros[$ultimoRegistroIndex]['dias_descanso'] = $dias;
                                }
                            } else {
                                // Diferente evento, pero no es un reintegro después de salida → crear nuevo
                                if ($eventoNombreNormalizado === 'SALIDA A DESCANSO') {
                                    $registroBase['fecha_salida'] = $fechaEvento;
                                    $registroBase['agencia_salida'] = $agenciaEvento;
                                } elseif ($eventoNombreNormalizado === 'REINTEGRO DE DESCANSO') {
                                    $registroBase['fecha_reintegro'] = $fechaEvento;
                                    $registroBase['agencia_reintegro'] = $agenciaEvento;
                                }
                                $registros[] = $registroBase;
                                $ultimoRegistroIndex = array_key_last($registros);
                            }
                        } else {
                            // Mismo evento anterior y actual → crear nuevo registro
                            if ($esSalida) {
                                $registroBase['fecha_salida'] = $fechaEvento;
                                $registroBase['agencia_salida'] = $agenciaEvento;
                            } elseif ($esReintegro) {
                                $registroBase['fecha_reintegro'] = $fechaEvento;
                                $registroBase['agencia_reintegro'] = $agenciaEvento;
                            }
                            $registros[] = $registroBase;
                            $ultimoRegistroIndex = array_key_last($registros);
                        }
                    }
                }

                // Unimos los registros de este conductor a la colección final
                $coleccionFinal = array_merge($coleccionFinal, $registros);
            }

            return $coleccionFinal;
           
        }catch(Exception $e){
            Log::error('Error al obtener la lista de ingreso salida de conductores: ' . $e->getMessage());
            return toastModal("Error al obtener los resultados","error");
        }
       
    }
}
