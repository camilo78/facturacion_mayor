<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>

@page {
    margin: 1cm;
}

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 8px;
    color: #111;
    background: #fff;
}

/* ══ TABLA PRINCIPAL ════════════════════════════════════════════════ */
table.main { width: 100%; border-collapse: collapse; }

/* ── Fila de empresa (dentro de <thead>, se repite en cada página) ── */
td.hdr-cell {
    padding: 0;
    border: none;
    background: #fff;
}
table.hdr-inner {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1px solid #e0e8ee;
    padding-bottom: 6px;
    margin-bottom: 5px;
}
table.hdr-inner td { vertical-align: middle; padding: 6px 0 5px; border: none; background: #fff; }
td.hdr-emp    { width: 35%; padding-right: 10px !important; }
td.hdr-center { width: 30%; text-align: center;
                padding: 6px 14px 5px !important; }
td.hdr-right  { width: 35%; text-align: right; padding-left: 12px !important; }

.emp-nombre    { font-size: 12px; font-weight: 700; color: #1b3a5c; text-transform: uppercase; line-height: 1.3; }
.emp-comercial { font-size: 10px; color: #009898; font-style: italic; margin-top: 1px; }
.emp-info      { font-size: 9px; color: #555; margin-top: 3px; }
.emp-rtn       { font-size: 9.5px; font-weight: 700; color: #1b3a5c;
                 font-family: 'Courier New', monospace; margin-top: 4px; }
.rep-titulo    { font-size: 14px; font-weight: 700; color: #009898;
                 text-transform: uppercase; letter-spacing: 0.05em;
                 text-align: center; }
.rep-sub       { font-size: 9.5px; color: #666; margin-top: 3px; text-align: center; }
.per-label     { font-size: 8.5px; color: #888; text-transform: uppercase; letter-spacing: 0.06em; }
.per-val       { font-size: 9.5px; font-weight: 700; color: #1b3a5c; margin-top: 2px; line-height: 1.5; }
.per-gen       { font-size: 8.5px; color: #aaa; margin-top: 3px; }

/* Nota legal (dentro de thead, se repite) */
td.legal-cell {
    font-size: 8.5px; color: #777; font-style: italic;
    padding: 4px 6px 5px;
    border-bottom: none;
    line-height: 1.5;
}

/* ── Cabecera de columnas ── */
thead tr.col-hdr th {
    background: #1b3a5c;
    color: #d0e2f4;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 5px 5px;
    white-space: nowrap;
    border: none;
}

/* ── Separador de mes ── */
tr.mes-sep td {
    background: #eef4fb;
    color: #1b3a5c;
    font-weight: 700;
    font-size: 9.5px;
    padding: 3px 6px;
    border-top: 1px solid #c0d4e8;
    border-bottom: 1px solid #c0d4e8;
}

/* ── Filas de datos ── */
tbody tr td {
    padding: 3.5px 5px;
    border-bottom: 1px solid #e8edf3;
    font-size: 9.5px;
    vertical-align: middle;
}
tbody tr:nth-child(even) td { background: #f5f8fc; }

/* ── Totales ── */
tfoot tr td {
    background: #1b3a5c;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 5px 5px;
    border-top: 2px solid #009898;
}

.right { text-align: right; }
.left  { text-align: left; }
.mono  { font-family: 'Courier New', monospace; }
.navy  { color: #1b3a5c; }
.muted { color: #888; }
.dash  { color: #ccc; }

/* ── Bloque de firmas ── */
table.firmas {
    width: 100%;
    border-collapse: collapse;
    margin-top: 66px;
    page-break-inside: avoid;
}
table.firmas td.firma-cell {
    width: 33.3%;
    text-align: center;
    padding: 0 24px;
    border: none;
    vertical-align: bottom;
}
.firma-linea  {
    border-top: 1px solid #1b3a5c;
    margin-bottom: 5px;
    width: 80%;
    margin-left: auto;
    margin-right: auto;
}
.firma-cargo  { font-size: 8px; font-weight: 700; color: #1b3a5c; text-transform: uppercase; letter-spacing: 0.04em; }
.firma-nombre { font-size: 7.5px; color: #888; margin-top: 2px; font-style: italic; }
</style>
</head>
<body>

<table class="main">

    {{-- ══ THEAD: se repite en cada página automáticamente en DomPDF ══ --}}
    <thead>

        {{-- Fila empresa --}}
        <tr>
            <td class="hdr-cell" colspan="11">
                <table class="hdr-inner">
                    <tr>
                        <td class="hdr-emp">
                            <div class="emp-nombre">{{ $tenant->nombre }}</div>
                            @if($tenant->nombre_comercial && $tenant->nombre_comercial !== $tenant->nombre)
                                <div class="emp-comercial">{{ $tenant->nombre_comercial }}</div>
                            @endif
                            <div class="emp-info">
                                @if($tenant->telefono)Tel: {{ $tenant->telefono }} &nbsp;|&nbsp; @endif{{ $tenant->email }}
                            </div>
                            <div class="emp-rtn">RTN: {{ $tenant->rtn }}</div>
                        </td>
                        <td class="hdr-center">
                            <div class="rep-titulo">Libro de Ventas</div>
                            <div class="rep-sub">Conforme SAR Honduras &nbsp;·&nbsp; Acuerdo 481-2017</div>
                        </td>
                        <td class="hdr-right">
                            <div class="per-label">Período</div>
                            <div class="per-val">
                                {{ \Carbon\Carbon::parse($f['desde'])->format('d/m/Y') }}<br>
                                al &nbsp;{{ \Carbon\Carbon::parse($f['hasta'])->format('d/m/Y') }}
                            </div>
                            <div class="per-gen">Generado: {{ now()->format('d/m/Y H:i') }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Nota legal --}}
        <tr>
            <td class="legal-cell" colspan="11">
                Art. 41 Ley del ISV (Decreto 24-83) y Art. 9 de su Reglamento.
                Documento de registro tributario — conservar 5 años (Art. 116 Código Tributario Honduras).
                @if($facturas->count() > 0)
                &nbsp;·&nbsp; {{ $facturas->count() }} factura{{ $facturas->count() !== 1 ? 's' : '' }}
                &nbsp;·&nbsp; ISV del período: L.&nbsp;{{ number_format($facturas->sum('_isv15') + $facturas->sum('_isv18'), 2) }}
                @endif
            </td>
        </tr>

        {{-- Cabecera de columnas --}}
        <tr class="col-hdr">
            <th class="left"  style="width:14%">No. Factura</th>
            <th class="left"  style="width:7%">Fecha</th>
            <th class="left"  style="width:10%">RTN</th>
            <th class="left"  style="width:15%">Cliente</th>
            <th class="right" style="width:8%">Exento L.</th>
            <th class="right" style="width:9%">Exonerado L.</th>
            <th class="right" style="width:9%">Base 15% L.</th>
            <th class="right" style="width:8%">ISV 15% L.</th>
            <th class="right" style="width:9%">Base 18% L.</th>
            <th class="right" style="width:8%">ISV 18% L.</th>
            <th class="right" style="width:9%">Total L.</th>
        </tr>

    </thead>

    {{-- ══ TBODY ════════════════════════════════════════════════════ --}}
    <tbody>
    @php $mesActual = null; @endphp
    @foreach($facturas as $fac)
        @php $mes = $fac->fecha_emision->format('Y-m'); @endphp
        @if($mes !== $mesActual)
            @php $mesActual = $mes; @endphp
            <tr class="mes-sep">
                <td colspan="11">{{ \Carbon\Carbon::parse($mes.'-01')->locale('es')->isoFormat('MMMM YYYY') }}</td>
            </tr>
        @endif
        <tr>
            <td class="mono navy">{{ $fac->numero_completo }}</td>
            <td>{{ $fac->fecha_emision->format('d/m/Y') }}</td>
            <td class="mono muted">{{ $fac->rtn_cliente ?? 'CF' }}</td>
            <td>{{ \Illuminate\Support\Str::limit($fac->nombre_cliente, 26) }}</td>
            <td class="right">{!! $fac->_exento_puro > 0 ? number_format($fac->_exento_puro, 2) : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_exonerado  > 0 ? number_format($fac->_exonerado, 2)   : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_gravado15  > 0 ? number_format($fac->_gravado15, 2)   : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_isv15      > 0 ? number_format($fac->_isv15, 2)       : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_gravado18  > 0 ? number_format($fac->_gravado18, 2)   : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_isv18      > 0 ? number_format($fac->_isv18, 2)       : '<span class="dash">—</span>' !!}</td>
            <td class="right">{{ number_format($fac->total, 2) }}</td>
        </tr>
    @endforeach
    </tbody>

    {{-- ══ TFOOT: solo aparece al final (no se repite en DomPDF) ══ --}}
    <tfoot>
        <tr>
            <td colspan="4" class="right" style="letter-spacing:0.06em; font-size:9px;">TOTALES DEL PERÍODO</td>
            <td class="right">{{ number_format($facturas->sum('_exento_puro'), 2) }}</td>
            <td class="right">{{ number_format($facturas->sum('_exonerado'),   2) }}</td>
            <td class="right">{{ number_format($facturas->sum('_gravado15'),   2) }}</td>
            <td class="right">{{ number_format($facturas->sum('_isv15'),       2) }}</td>
            <td class="right">{{ number_format($facturas->sum('_gravado18'),   2) }}</td>
            <td class="right">{{ number_format($facturas->sum('_isv18'),       2) }}</td>
            <td class="right">{{ number_format($facturas->sum('total'),        2) }}</td>
        </tr>
    </tfoot>

</table>

{{-- ══ BLOQUE DE FIRMAS ══════════════════════════════════════════════ --}}
<table class="firmas">
    <tr>
        <td class="firma-cell">
            <div class="firma-linea"></div>
            <div class="firma-cargo">Elaborado por</div>
            <div class="firma-nombre">Nombre y sello</div>
        </td>
        <td class="firma-cell">
            <div class="firma-linea"></div>
            <div class="firma-cargo">Revisado por</div>
            <div class="firma-nombre">Nombre y sello</div>
        </td>
        <td class="firma-cell">
            <div class="firma-linea"></div>
            <div class="firma-cargo">Autorizado por / Contador</div>
            <div class="firma-nombre">Nombre, sello y No. Colegiado</div>
        </td>
    </tr>
</table>

</body>
</html>
