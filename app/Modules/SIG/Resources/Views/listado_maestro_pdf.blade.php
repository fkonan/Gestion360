<!DOCTYPE html>
<html lang="es">
@php
    $formatearTexto = static function ($valor): string {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '';
        }

        $valor = mb_convert_case(mb_strtolower($valor, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        return preg_replace('/\bSig\b/u', 'SIG', $valor) ?? $valor;
    };
@endphp
<head>
    <meta charset="UTF-8">
    <title>Listado maestro SIG</title>
    <style>
        @page {
            size: legal landscape;
            margin: 196px 30px 120px 50px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #111;
        }

        .report-header {
            position: fixed;
            top: -110px;
            left: 0;
            right: 0;
            height: 82px;
        }

        .report-footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 14px;
            font-size: 8px;
            color: #444;
        }

        .report-content {
            padding-top: 0;
        }

        .header-table,
        .meta-table,
        .master-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .master-table {
            table-layout: fixed;
        }

        .header-table td {
            border: 1px solid #3f3f46;
            vertical-align: middle;
        }

        .header-table__logo {
            width: 15%;
            text-align: center;
            padding: 7px 8px;
        }

        .header-table__title {
            width: 66%;
            text-align: center;
            padding: 7px 10px;
        }

        .header-table__meta {
            width: 19%;
            padding: 0;
        }

        .header-logo {
            max-width: 264px;
            max-height: 60px;
            margin-bottom: 4px;
        }

        .header-company {
            font-size: 8px;
            line-height: 1.25;
        }

        .header-title {
            font-size: 14px;
            font-weight: 700;
        }

        .header-divider {
            width: 100%;
            margin: 5px auto 4px;
            border-top: 1px solid #3f3f46;
        }

        .header-subtitle {
            font-size: 13.2px;
            font-weight: 700;
        }

        .header-scope {
            margin-top: 4px;
            font-size: 8px;
            color: #4b5563;
        }

        .meta-table td {
            border-bottom: 1px solid #3f3f46;
            justify-content: center;
            text-align: center;
            padding: 3px 6px;
            font-size: 10px;
        }

        .meta-table tr:last-child td {
            border-bottom: none;
        }

        .meta-label {
            width: 36%;
            font-weight: 700;
        }

        .meta-value {
            width: 64%;
        }

        .master-table th,
        .master-table td {
            border: 1px solid #444;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .master-table thead th {
            background: #e5e7eb;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            line-height: 1.08;
        }

        .master-table tbody td {
            font-size: 12px;
            line-height: 1.1;
        }

        .master-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .master-table tr {
            page-break-inside: avoid;
        }

        .master-table .text-center {
            text-align: center;
        }

        .master-table .text-nowrap {
            white-space: nowrap;
        }

        .master-table .col-num { width: 2%; }
        .master-table .col-categoria { width: 10%; }
        .master-table .col-proceso { width: 11%; }
        .master-table .col-codigo { width: 5%; }
        .master-table .col-nombre { width: 19%; }
        .master-table .col-emision { width: 5%; }
        .master-table .col-fecha { width: 7%; }
        .master-table .col-elaboro { width: 12%; }
        .master-table .col-reviso { width: 12%; }
        .master-table .col-aprobo { width: 6%; }
        .master-table .col-ubicacion { width: 11%; }

        .master-table .cell-compact {
            padding-left: 2px;
            padding-right: 2px;
        }

        .master-table .cell-tight {
            padding-left: 1px;
            padding-right: 1px;
        }

        .master-table .empty-row td {
            padding: 10px 6px;
            text-align: center;
            font-style: italic;
            color: #6b7280;
            background: #fff;
        }

        .footer-table td {
            padding-top: 4px;
            border-top: 1px solid #9ca3af;
        }

        .footer-left {
            text-align: left;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <table class="header-table">
            <tr>
                <td class="header-table__logo">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Copetran" class="header-logo">
                    @endif
                </td>
                <td class="header-table__title">
                    <div class="header-title">GESTION DOCUMENTAL</div>
                    <div class="header-divider"></div>
                    <div class="header-subtitle">LISTADO MAESTRO</div>
                    <!-- <div class="header-scope">{{ $formatearTexto($alcanceListado) }}</div> -->
                </td>
                <td class="header-table__meta">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">{{ $formatearTexto('Fecha:') }}{{ $fechaGeneracion }}</td>
                            <!-- <td class="meta-value">{{ $fechaGeneracion }}</td> -->
                        </tr>
                        <tr>
                            <td class="meta-label">{{ $formatearTexto('Hora:') }}{{ $horaGeneracion }}</td>
                            <!-- <td class="meta-value">{{ $horaGeneracion }}</td> -->
                        </tr>
                        <tr>
                            <td class="meta-label">Tipo: FT-Formato</td>
                            <!-- <td class="meta-value">{{ $totalDocumentos }}</td> -->
                        </tr>
                        <tr>
                            <td class="meta-label">Código: FT-GD-50</td>
                            <!-- <td class="meta-value">{{ $formatearTexto($incluyeInactivos ? 'Activos e inactivos' : 'Activos') }}</td> -->
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="report-footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">{{ $formatearTexto('Sig - Listado Maestro Documental Generado Automáticamente') }}</td>
                <td class="footer-right">{{ $formatearTexto('Copetran - Sistema Integrado De Gestión') }}</td>
            </tr>
        </table>
    </div>

    <div class="report-content">
    <table class="master-table">
        <thead>
            <tr>
                <th class="cell-tight col-num">#</th>
                <th class="col-categoria">{{ $formatearTexto('Categoría') }}</th>
                <th class="col-proceso">{{ $formatearTexto('Proceso') }}</th>
                <th class="cell-compact col-codigo">{{ $formatearTexto('Código') }}</th>
                <th class="col-nombre">{{ $formatearTexto('Nombre') }}</th>
                <th class="cell-tight col-emision">{{ $formatearTexto('Emisión') }}</th>
                <th class="cell-compact col-fecha">{{ $formatearTexto('Fecha De Emisión') }}</th>
                <th class="col-elaboro">{{ $formatearTexto('Elaboró') }}</th>
                <th class="col-reviso">{{ $formatearTexto('Revisó') }}</th>
                <th class="cell-compact col-aprobo">{{ $formatearTexto('Aprobó') }}</th>
                <th class="col-ubicacion">{{ $formatearTexto('Ubicación') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documentos as $documento)
                <tr>
                    <td class="text-center cell-tight col-num">{{ $loop->iteration }}</td>
                    <td class="col-categoria cell-clip">{{ $formatearTexto($documento['categoria'] ?? '') }}</td>
                    <td class="col-proceso cell-clip">{{ $formatearTexto($documento['proceso'] ?? '') }}</td>
                    <td class="text-center cell-compact col-codigo">{{ $documento['codigo'] ?? '' }}</td>
                    <td class="cell-name col-nombre">{{ $formatearTexto($documento['nombre'] ?? '') }}</td>
                    <td class="text-center cell-tight col-emision">{{ $documento['version'] ?? '' }}</td>
                    <td class="text-center cell-compact col-fecha">{{ $documento['fecha_emision'] ?? '' }}</td>
                    <td class="col-elaboro cell-clip">{{ $formatearTexto($documento['elaboro'] ?? '') }}</td>
                    <td class="col-reviso cell-clip">{{ $formatearTexto($documento['reviso'] ?? '') }}</td>
                    <td class="text-center col-aprobo cell-clip">{{ $formatearTexto($documento['aprueba'] ?? '') }}</td>
                    <td class="col-ubicacion cell-clip">{{ $formatearTexto($documento['ubicacion'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="11">
                        {{ $formatearTexto('No Hay Documentos Disponibles Para Generar El Listado Maestro.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
            $pdf->page_text(846, 28, 'Página: {PAGE_NUM} De {PAGE_COUNT}', $font, 8, [0.1, 0.1, 0.1]);
        }
    </script>
</body>
</html>
