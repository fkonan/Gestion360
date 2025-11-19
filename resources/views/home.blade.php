@extends('layouts.dashboard')

@section('title','Home')

@section('content')
<div class="mapa-procesos-identico">
  <div class="anillo-externo">
    <div class="anillo-rojo"></div>
    <div class="anillo-azul-oscuro"></div>

    <div class="texto-lateral necesidades">
      Las Partes Interesadas
      <br>
      Necesidades de
    </div>
    <div class="texto-lateral satisfaccion">
      Satisfacción
      <br>
      Partes Interesadas
    </div>

    <div class="texto-flujo control">
      MAPA DE PROCESOS
      <br>
      CONTROL DE CALIDAD
    </div>
    <div class="texto-flujo mejora">
      MEJORAMIENTO CONTINUO
    </div>
  </div>

  <div class="circulo-central">
    <div class="seccion-gerencial">
      <div class="triangulo-up"></div>
      <h3>PROCESOS GERENCIALES</h3>
      <div class="procesos-fila">
        <span class="proceso azul-claro">PLANIFICACIÓN</span>
        <span class="proceso azul-claro">SEGUIMIENTO, EVALUACIÓN Y MEJORA</span>
        <span class="proceso azul-claro">HSE</span>
        <span class="proceso azul-claro">GESTIÓN DE RECURSOS</span>
      </div>
    </div>

    <div class="seccion-misional-apoyo">
      <div class="procesos-misionales">
        <h3>PROCESOS MISIONALES</h3>
        <div class="flujo-horizontal">
          <div class="paso comercial">COMERCIAL</div>
          <div class="flecha-flujo"></div>
          <div class="paso formalizacion">FORMALIZACIÓN DEL SERVICIO</div>
          <div class="flecha-flujo"></div>
          <div class="paso transito">TRÁNSITO Y TRANSPORTE</div>
        </div>
      </div>
    </div>

    <div class="seccion-apoyo">
      <div class="procesos-fila">
        <span class="proceso azul-oscuro">GESTIÓN DOCUMENTAL</span>
        <span class="proceso azul-oscuro">SEGURIDAD</span>
        <span class="proceso azul-oscuro">GESTIÓN HUMANA</span>
        <span class="proceso azul-oscuro">MANT. E INFRAESTRUCTURA</span>
        <span class="proceso azul-oscuro">SOPORTE LEGAL</span>
      </div>
      <h3>PROCESOS DE APOYO</h3>
      <div class="triangulo-down"></div>
    </div>
  </div>
</div>
@endsection


@pushOnce('css')
<style>
/* Variables de color para fácil mantenimiento */
:root {
    --color-rojo-anillo: #c00;
    --color-azul-anillo: #00f;
    --color-azul-mision: #3a69a2;
    --color-azul-apoyo: #1a4e95;
    --color-gris-claro: #f0f0f0;
    --color-gris-flujo: #a9a9a9;
    --tamaño-mapa: 600px;
    --ancho-anillo: 40px;
}

/* --- Contenedor Principal del Mapa --- */
.mapa-procesos-identico {
  position: absolute;
  top: 50%;
  left: 50%;
  width: var(--tamaño-mapa);
  height: var(--tamaño-mapa);
  transform: translate(-50%, -50%);
}

/* --- Anillo Exterior (Flujo de Mejora) --- */
.anillo-externo {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    box-sizing: border-box;
    /* Borde principal: crea el fondo visual para los segmentos */
    border: var(--ancho-anillo) solid transparent;
    border-color: var(--color-rojo-anillo) var(--color-azul-anillo) var(--color-azul-anillo) var(--color-rojo-anillo);
}

/* Capas de Color para la división clara (opcional si el borde funciona) */
.anillo-rojo, .anillo-azul-oscuro {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    box-sizing: border-box;
}

/* Se podrían usar pseudoelementos en .anillo-externo para crear las flechas,
   pero es más simple confiar en la ilusión de los textos */

/* Textos Laterales (Necesidades / Satisfacción) */
.texto-lateral {
    position: absolute;
    top: 50%;
    font-weight: bold;
    color: white;
    text-align: center;
    font-size: 1.1em;
    padding: 10px 5px;
    width: 200px; /* Ancho suficiente para el texto */
    box-sizing: border-box;
}

.necesidades {
    left: calc(0px - var(--ancho-anillo) - 10px); /* Posicionar fuera del borde */
    transform: translateY(-50%) rotate(270deg);
    /* El color de fondo simula el segmento rojo que lo contiene */
    background-color: var(--color-rojo-anillo);
}

.satisfaccion {
    right: calc(0px - var(--ancho-anillo) - 10px);
    transform: translateY(-50%) rotate(90deg);
    /* El color de fondo simula el segmento azul que lo contiene */
    background-color: var(--color-azul-anillo);
}

/* Textos de Flujo Curvo (Control / Mejora) */
.texto-flujo {
    position: absolute;
    width: 100%;
    text-align: center;
    font-weight: bold;
    color: #333;
    letter-spacing: 1px;
}

.control {
    top: -50px;
}

.mejora {
    bottom: -50px;
}

/* --- Círculo Central con Procesos --- */
.circulo-central {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 450px;
    height: 450px;
    border-radius: 50%;
    background-color: white;
    box-shadow: 0 0 0 2px #ccc; /* Simular borde interior */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    padding: 40px 0;
    box-sizing: border-box;
}

h3 {
    margin: 0;
    text-align: center;
    font-size: 0.9em;
    font-weight: 900;
    color: var(--color-azul-apoyo);
}

/* Triángulos de Conexión (Estilo del logo o de las puntas) */
.triangulo-up, .triangulo-down {
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
}

.triangulo-up {
    border-bottom: 15px solid var(--color-rojo-anillo);
    margin-bottom: 5px;
}

.triangulo-down {
    border-top: 15px solid var(--color-rojo-anillo);
    margin-top: 5px;
}

/* Filas de Procesos (Gerenciales y Apoyo) */
.seccion-gerencial, .seccion-apoyo {
    width: 95%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.procesos-fila {
    display: flex;
    justify-content: space-around;
    width: 100%;
    padding: 10px 0;
    box-sizing: border-box;
}

.proceso {
    padding: 5px 8px;
    border-radius: 3px;
    font-size: 0.65em;
    font-weight: bold;
    color: white;
    flex: 1;
    margin: 0 5px;
    text-align: center;
    line-height: 1.2;
    min-width: 0;
}

.azul-claro {
    background-color: var(--color-azul-mision); /* Color más claro para gerenciales */
    border: 1px solid #000;
}

.azul-oscuro {
    background-color: var(--color-azul-apoyo); /* Color más oscuro para apoyo */
    border: 1px solid #000;
}

/* --- Procesos Misionales (Centro) --- */
.seccion-misional-apoyo {
    width: 90%;
}

.procesos-misionales {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.flujo-horizontal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 10px 0;
    box-sizing: border-box;
}

.paso {
    padding: 10px 5px;
    border-radius: 3px;
    font-size: 0.7em;
    font-weight: bold;
    color: white;
    flex: 1;
    margin: 0 3px;
    text-align: center;
    min-width: 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    border: 1px solid #333;
}

.comercial { background-color: #8cadda; }
.formalizacion { background-color: #659fd8; }
.transito { background-color: #3e73b2; }

/* Flechas de Conexión Misional */
.flecha-flujo {
    width: 15px;
    height: 6px;
    background-color: var(--color-gris-flujo);
    position: relative;
    border-radius: 2px;
}

.flecha-flujo::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 0;
    height: 0;
    border-top: 5px solid transparent;
    border-bottom: 5px solid transparent;
    border-left: 5px solid var(--color-gris-flujo);
}

/* Estilo para el contenedor del mapa de procesos principal (que no está en el centro) */
.mapa-procesos-identico > .anillo-externo {
    background: none;
    border-width: 0;
}

/* Flujo Misional - Contenedor rectangular gris */
.procesos-misionales {
    background-color: var(--color-gris-flujo);
    padding: 10px 5px;
    border-radius: 5px;
    box-shadow: inset 0 0 5px rgba(0,0,0,0.3);
}

/* Escondemos el título misional que debe estar en el borde del rectángulo gris */
.procesos-misionales h3 {
    display: none;
}
</style>
@endpushOnce
