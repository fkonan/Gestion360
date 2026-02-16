@extends('emails.layout')

@section('content')
  <h2 style="margin: 0 0 16px; color: #0f172a;">Nueva factura radicada</h2>

  <p style="margin: 0 0 10px;">
    Se ha radicado una factura que fue distribuida a su área y está pendiente de aprobación.
  </p>

  <table width="100%" cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; margin-top: 10px;">
    <tr>
      <td style="border: 1px solid #d1d5db; width: 180px;"><strong>Número de factura</strong></td>
      <td style="border: 1px solid #d1d5db;">{{ $radicacion->num_factura }}</td>
    </tr>
    <tr>
      <td style="border: 1px solid #d1d5db;"><strong>Número de contrato</strong></td>
      <td style="border: 1px solid #d1d5db;">{{ $radicacion->num_contrato ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="border: 1px solid #d1d5db;"><strong>Proveedor</strong></td>
      <td style="border: 1px solid #d1d5db;">{{ $radicacion->proveedor?->nombre_completo ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td style="border: 1px solid #d1d5db;"><strong>Fecha de radicación</strong></td>
      <td style="border: 1px solid #d1d5db;">{{ optional($radicacion->fecha_radicacion)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td style="border: 1px solid #d1d5db;"><strong>Fecha de vencimiento</strong></td>
      <td style="border: 1px solid #d1d5db;">{{ optional($radicacion->fecha_vencimiento)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td style="border: 1px solid #d1d5db;"><strong>Valor</strong></td>
      <td style="border: 1px solid #d1d5db;">${{ number_format((float) $radicacion->valor, 2, '.', ',') }}</td>
    </tr>
    <tr>
      <td style="border: 1px solid #d1d5db;"><strong>Descripción</strong></td>
      <td style="border: 1px solid #d1d5db;">{{ $radicacion->descripcion ?: 'N/A' }}</td>
    </tr>
  </table>

  <p style="margin: 14px 0 0;">
    Ingrese al módulo <a href="{{ route('radfact.radicaciones.index') }}" style="color: #2563eb; text-decoration: none;">Radicación de Facturas</a> para gestionar la aprobación correspondiente.
  </p>
@endsection
