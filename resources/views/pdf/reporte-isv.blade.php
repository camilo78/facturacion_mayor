<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

@page {
    margin: 1cm;
}

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 8.5px;
    color: #111;
    background: #fff;
}

/* ══ HEADER EMPRESA (tabla única, no fixed) ═════════════════════════ */
table.hdr-outer {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1px solid #e0e8ee;
    margin-bottom: 12px;
}
table.hdr-outer td { vertical-align: middle; padding: 7px 0 6px; border: none; background: #fff; }
td.hdr-emp    { width: 46%; padding-right: 10px !important; }
td.hdr-center { width: 30%; text-align: center;
                border-left: 1px solid #b8d8d8 !important;
                border-right: 1px solid #b8d8d8 !important;
                padding: 6px 14px !important; }
td.hdr-right  { width: 24%; text-align: right; padding-left: 12px !important; }

.emp-nombre    { font-size: 11px; font-weight: 700; color: #1b3a5c; text-transform: uppercase; line-height: 1.3; }
.emp-comercial { font-size: 8px; color: #009898; font-style: italic; margin-top: 1px; }
.emp-info      { font-size: 7px; color: #555; margin-top: 3px; }
.emp-rtn       { font-size: 7.5px; font-weight: 700; color: #1b3a5c;
                 font-family: 'Courier New', monospace;
                 border: 1px solid #c0d4e8; background: #eef4fb;
                 padding: 1px 5px; margin-top: 3px; }
.rep-titulo    { font-size: 12px; font-weight: 700; color: #009898; text-transform: uppercase; letter-spacing: 0.05em; text-align: center; }
.rep-sub       { font-size: 7.5px; color: #666; margin-top: 3px; text-align: center; }
.per-label     { font-size: 6.5px; color: #888; text-transform: uppercase; letter-spacing: 0.06em; }
.per-val       { font-size: 8.5px; font-weight: 700; color: #1b3a5c; margin-top: 2px; line-height: 1.5; }
.per-gen       { font-size: 6.5px; color: #aaa; margin-top: 3px; }

/* ══ TÍTULO DE SECCIÓN ══════════════════════════════════════════════ */
.section-title {
    font-size: 8px; font-weight: 700; color: #1b3a5c;
    text-transform: uppercase; letter-spacing: 0.05em;
    border-left: 3px solid #009898; padding-left: 6px;
    margin-bottom: 8px; margin-top: 14px;
}

/* ══ CASILLAS ISV-103 ════════════════════════════════════════════════ */
table.casillas { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
table.casillas td {
    width: 25%; border: 1px solid #dde4ed;
    padding: 7px 10px; vertical-align: top;
}
.cas-num  { font-size: 6.5px; color: #888; text-transform: uppercase; letter-spacing: 0.04em; }
.cas-desc { font-size: 8.5px; font-weight: 700; color: #1b3a5c; margin-top: 2px; }
.cas-val  { font-size: 14px; font-weight: 700; color: #111; margin-top: 4px; text-align: right; }
.cas-sub  { font-size: 7px; color: #777; text-align: right; margin-top: 1px; }
.bg-exento { background: #f0faf5; }
.bg-grav15 { background: #eef4fb; }
.bg-grav18 { background: #fdf6e8; }
.bg-total  { background: #1b3a5c; }
.bg-total .cas-num  { color: #7fdfdf; }
.bg-total .cas-desc { color: #e0eaf5; }
.bg-total .cas-val  { color: #fff; font-size: 16px; }
.bg-total .cas-sub  { color: #7fdfdf; }

/* ══ TABLA DETALLE ════════════════════════════════════════════════════ */
table.main { width: 100%; border-collapse: collapse; }

td.hdr-cell { padding: 0; border: none; background: #fff; }
table.hdr-inner { width:100%; border-collapse:collapse; border-bottom:1px solid #e0e8ee; margin-bottom:4px; }
table.hdr-inner td { vertical-align:middle; padding:5px 0 4px; border:none; background:#fff; }
td.hi-emp    { width:46%; padding-right:10px !important; }
td.hi-center { width:30%; text-align:center; border-left:1px solid #b8d8d8 !important; border-right:1px solid #b8d8d8 !important; padding:5px 14px 4px !important; }
td.hi-right  { width:24%; text-align:right; padding-left:12px !important; }

thead tr.col-hdr th {
    background: #1b3a5c; color: #d0e2f4;
    font-size: 7px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.04em; padding: 5px; white-space: nowrap; border: none;
}
tbody tr td { padding: 3px 5px; border-bottom: 1px solid #e8edf3; font-size: 7.5px; }
tbody tr:nth-child(even) td { background: #f5f8fc; }
tfoot tr td { background: #1b3a5c; color: #fff; font-size: 8px; font-weight: 700; padding: 5px; border-top: 2px solid #009898; }

.right { text-align: right; }
.left  { text-align: left; }
.mono  { font-family: 'Courier New', monospace; }
.dash  { color: #ccc; }
</style>
</head>
<body>

@php
    $totExento    = $facturas->sum('_exento_puro');
    $totExonerado = $facturas->sum('_exonerado');
    $totGrav15    = $facturas->sum('_gravado15');
    $totIsv15     = $facturas->sum('_isv15');
    $totGrav18    = $facturas->sum('_gravado18');
    $totIsv18     = $facturas->sum('_isv18');
    $totTotal     = $facturas->sum('total');
    $totIsv       = $totIsv15 + $totIsv18;
@endphp

{{-- ══ HEADER EMPRESA (solo página 1) ══════════════════════════════ --}}
<table class="hdr-outer">
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
            <div class="rep-titulo">Resumen ISV</div>
            <div class="rep-sub">Declaración ISV-103 &nbsp;·&nbsp; SAR Honduras</div>
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

{{-- ══ CASILLAS ISV-103 ══════════════════════════════════════════════ --}}
<div class="section-title" style="margin-top:0">Datos para Declaración ISV-103 — SAR Honduras</div>

<table class="casillas">
    <tr>
        <td class="bg-exento">
            <div class="cas-num">Casilla 4020</div>
            <div class="cas-desc">Ventas Exentas</div>
            <div class="cas-val">L.&nbsp;{{ number_format($totExento, 2) }}</div>
        </td>
        <td class="bg-exento">
            <div class="cas-num">Casilla 4030</div>
            <div class="cas-desc">Ventas Exoneradas</div>
            <div class="cas-val">L.&nbsp;{{ number_format($totExonerado, 2) }}</div>
        </td>
        <td class="bg-grav15">
            <div class="cas-num">Casilla 4000</div>
            <div class="cas-desc">Ventas Gravadas 15%</div>
            <div class="cas-val">L.&nbsp;{{ number_format($totGrav15, 2) }}</div>
        </td>
        <td class="bg-grav15">
            <div class="cas-num">Casilla 4001</div>
            <div class="cas-desc">ISV 15% Ventas</div>
            <div class="cas-val" style="color:#009898">L.&nbsp;{{ number_format($totIsv15, 2) }}</div>
        </td>
    </tr>
    <tr>
        <td class="bg-grav18">
            <div class="cas-num">Casilla 4010</div>
            <div class="cas-desc">Ventas Gravadas 18%</div>
            <div class="cas-val">L.&nbsp;{{ number_format($totGrav18, 2) }}</div>
        </td>
        <td class="bg-grav18">
            <div class="cas-num">Casilla 4011</div>
            <div class="cas-desc">ISV 18% Ventas</div>
            <div class="cas-val" style="color:#c07000">L.&nbsp;{{ number_format($totIsv18, 2) }}</div>
        </td>
        <td>
            <div class="cas-num">ISV Total del Período</div>
            <div class="cas-desc">ISV 15% + ISV 18%</div>
            <div class="cas-val" style="color:#009898">L.&nbsp;{{ number_format($totIsv, 2) }}</div>
            <div class="cas-sub">{{ $facturas->count() }} factura{{ $facturas->count() !== 1 ? 's' : '' }}</div>
        </td>
        <td class="bg-total">
            <div class="cas-num">Casilla 4040</div>
            <div class="cas-desc">Total Ventas del Período</div>
            <div class="cas-val">L.&nbsp;{{ number_format($totTotal, 2) }}</div>
        </td>
    </tr>
</table>

{{-- ══ TABLA DETALLE ══════════════════════════════════════════════════ --}}
<div class="section-title">Detalle de Facturas del Período</div>

<table class="main">
    <thead>
        <tr>
            <td class="hdr-cell" colspan="10">
                <table class="hdr-inner">
                    <tr>
                        <td class="hi-emp">
                            <div class="emp-nombre">{{ $tenant->nombre }}</div>
                            <div class="emp-rtn">RTN: {{ $tenant->rtn }}</div>
                        </td>
                        <td class="hi-center">
                            <div class="rep-titulo" style="font-size:10px">Resumen ISV — Detalle</div>
                        </td>
                        <td class="hi-right">
                            <div class="per-label">Período</div>
                            <div class="per-val">
                                {{ \Carbon\Carbon::parse($f['desde'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($f['hasta'])->format('d/m/Y') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="col-hdr">
            <th class="left"  style="width:14%">No. Factura</th>
            <th class="left"  style="width:7%">Fecha</th>
            <th class="left"  style="width:17%">Cliente</th>
            <th class="right" style="width:8%">Exento</th>
            <th class="right" style="width:9%">Exonerado</th>
            <th class="right" style="width:9%">Base 15%</th>
            <th class="right" style="width:8%">ISV 15%</th>
            <th class="right" style="width:9%">Base 18%</th>
            <th class="right" style="width:8%">ISV 18%</th>
            <th class="right" style="width:9%">Total L.</th>
        </tr>
    </thead>
    <tbody>
    @foreach($facturas as $fac)
        <tr>
            <td class="mono" style="color:#1b3a5c">{{ $fac->numero_completo }}</td>
            <td>{{ $fac->fecha_emision->format('d/m/Y') }}</td>
            <td>{{ \Illuminate\Support\Str::limit($fac->nombre_cliente, 24) }}</td>
            <td class="right">{!! $fac->_exento_puro > 0 ? number_format($fac->_exento_puro, 2) : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_exonerado  > 0 ? number_format($fac->_exonerado, 2)   : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_gravado15  > 0 ? number_format($fac->_gravado15, 2)   : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_isv15      > 0 ? number_format($fac->_isv15, 2)       : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_gravado18  > 0 ? number_format($fac->_gravado18, 2)   : '<span class="dash">—</span>' !!}</td>
            <td class="right">{!! $fac->_isv18      > 0 ? number_format($fac->_isv18, 2)       : '<span class="dash">—</span>' !!}</td>
            <td class="right" style="font-weight:600">{{ number_format($fac->total, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="right">TOTALES</td>
            <td class="right">{{ number_format($totExento,    2) }}</td>
            <td class="right">{{ number_format($totExonerado, 2) }}</td>
            <td class="right">{{ number_format($totGrav15,    2) }}</td>
            <td class="right">{{ number_format($totIsv15,     2) }}</td>
            <td class="right">{{ number_format($totGrav18,    2) }}</td>
            <td class="right">{{ number_format($totIsv18,     2) }}</td>
            <td class="right">{{ number_format($totTotal,     2) }}</td>
        </tr>
    </tfoot>
</table>

</body>
</html>
