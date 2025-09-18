@extends('layouts.comprobanteFirma')

@section('content')
@if($politicaId == $ID_POLITICA_MENORES)
<div class="politica-item">
  <div class="politica-titulo">POLÍTICA VIAJE CON MENORES</div>
  <div class="politica-detalle">
    <p><strong>Aplicación:</strong> Menores de 0 a 18 años requieren documentación específica según edad:</p>
    <ul>
      <li><strong>0-7 años:</strong> Registro Civil de Nacimiento</li>
      <li><strong>7-17 años:</strong> Tarjeta de Identidad, NUIP o pasaporte</li>
    </ul>
    <p><strong>Restricciones de asientos:</strong> No pueden ocupar asientos 1, 2, 3, 4 (fila del conductor) ni asientos 13, 14, 15, 16 (primera fila segundo piso en Doble Piso).</p>
    <p><strong>Supervisión:</strong> Menores de 0-12 años requieren acompañante adulto. A partir de 13 años pueden viajar solos con autorización (Formato FT-FS-16).</p>
    <div class="highlight"><strong>Descuentos aplicables:</strong> 50% para menores de 2-12 años en temporada baja</div>
  </div>
</div>
@endif

@if($politicaId == $ID_POLITICA_MASCOTAS)
<div class="politica-item">
  <div class="politica-titulo">POLÍTICA TRANSPORTE DE MASCOTAS</div>
  <div class="politica-detalle">
    <p><strong>Mascotas permitidas:</strong> Solo perros y gatos domésticos, de asistencia o soporte emocional.</p>
    <p><strong>Restricciones de tamaño:</strong> Máximo 28 cm de alto (excepto animales de asistencia/soporte emocional).</p>
    <p><strong>Requisitos del guacal:</strong> Dimensiones 31x44x34 cm, capacidad máxima 10 kg, con ventilación adecuada.</p>
    <p><strong>Documentación requerida:</strong></p>
    <ul>
      <li>Carné de vacunación (copia)</li>
      <li>Formato FT-FS-15 diligenciado</li>
      <li>Certificado especial para animales de asistencia/soporte emocional</li>
    </ul>
    <div class="highlight">Servicio gratuito - Responsabilidad total del propietario por daños</div>
  </div>
</div>
@endif

@if($politicaId == $ID_POLITICA_EQUIPAJE)
<div class="politica-item">
  <div class="politica-titulo">POLÍTICA DE EQUIPAJE</div>
  <div class="politica-detalle">
    <p><strong>Equipaje de mano:</strong> 1 pieza máximo, 10 kg, dimensiones 40x35x25 cm.</p>
    <p><strong>Equipaje de bodega por categoría:</strong></p>
    <ul>
      <li><strong>Doble Piso/Preferenciales:</strong> 2 piezas, 25 kg c/u, 90x50x30 cm</li>
      <li><strong>Buseton/Sprinter/Traffic:</strong> 1 pieza, 25 kg, 90x50x30 cm</li>
    </ul>
    <p><strong>Elementos prohibidos en bodega:</strong> Documentos, dinero, joyas, electrónicos, medicamentos, artículos frágiles o de valor.</p>
    <p><strong>Totalmente prohibidos:</strong> Armas, municiones, sustancias peligrosas, materiales inflamables, cadáveres.</p>
    <div class="important-note">
      <strong>Importante:</strong> Declarar valor si excede límite indemnizable (12 SMLDV). Conservar ficho numerado para reclamar equipaje.
    </div>
    <div class="highlight"><strong>Límite total:</strong> 85 kg entre equipaje de mano y bodega por pasajero</div>
  </div>
</div>
@endif
@endsection
