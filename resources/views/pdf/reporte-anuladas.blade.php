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
    font-size: 8px;
    color: #111;
    background: #fff;
}

table.main { width: 100%; border-collapse: collapse; }

td.hdr-cell { padding: 0; border: none; background: #fff; }
table.hdr-inner {
    width: 100%; border-collapse: collapse;
    border-bottom: 1px solid #e0e8ee; margin-bottom: 5px;
}
table.hdr-inner td { vertical-align: middle; padding: 6px 0 5px; border: none; background: #fff; }
td.hdr-emp    { width: 46%; padding-right: 10px !important; }
td.hdr-center { width: 30%; text-align: center;
                border-left: 1px solid #f0c8c8 !important;
                border-right: 1px solid #f0c8c8 !important;
                padding: 6px 14px 5px !important; }
td.hdr-right  { width: 24%; text-align: right; padding-left: 12px !important; }

.emp-nombre    { font-size: 11px; font-weight: 700; color: #1b3a5c; text-transform: uppercase; line-height: 1.3; }
.emp-comercial { font-size: 8px; color: #009898; font-style: italic; margin-top: 1px; }
.emp-info      { font-size: 7px; color: #555; margin-top: 3px; }
.emp-rtn       { font-size: 7.5px; font-weight: 700; color: #1b3a5c;
                 font-family: 'Courier New', monospace;
                 border: 1px solid #c0d4e8; background: #eef4fb;
                 padding: 1px 5px; margin-top: 3px; }
.rep-titulo    { font-size: 11px; font-weight: 700; color: #cc2222; text-transform: uppercase; letter-spacing: 0.05em; text-align: center; }
.rep-sub       { font-size: 7.5px; color: #666; margin-top: 3px; text-align: center; }
.per-label     { font-size: 6.5px; color: #888; text-transform: uppercase; letter-spacing: 0.06em; }
.per-val       { font-size: 8.5px; font-weight: 700; color: #1b3a5c; margin-top: 2px; line-height: 1.5; }
.per-gen       { font-size: 6.5px; color: #aaa; margin-top: 3px; }

td.legal-cell {
    font-size: 6.5px; color: #777; font-style: italic;
    padding: 4px 6px 5px;
    background: #fff5f5;
    border-left: 2.5px solid #cc2222;
    border-bottom: none;
    line-height: 1.5;
}

thead tr.col-hdr th {
    background: #8b1a1a; color: #fde8e8;
    font-size: 7px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.04em; padding: 5px; white-space: nowrap; border: none;
}

tbody tr td { padding: 3.5px 5px; border-bottom: 1px solid #f5e0e0; font-size: 7.5px; vertical-align: middle; }
tbody tr:nth-child(even) td { background: #fff5f5; }

tfoot tr td { background: #8b1a1a; color: #fff; font-size: 8px; font-weight: 700; padding: 5px; border-top: 2px solid #cc2222; }

.right  { text-align: right; }
.left   { text-align: left; }
.mono   { font-family: 'Courier New', monospace; }
.muted  { color: #888; }
.strike { text-decoration: line-through; color: #aaa; }
</style>
</head>
<body>

<table class="main">

    <thead>
        <tr>
            <td class="hdr-cell" colspan="7">
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
                            <div class="rep-titulo">Facturas Anuladas</div>
                            <div class="rep-sub">Registro de anulaciones &nbsp;·&nbsp; SAR Honduras</div>
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

        <tr>
            <td class="legal-cell" colspan="7">
                Las facturas anuladas deben conservarse en archivo por 5 años (Art. 116 Código Tributario Honduras). Requerido por SAR para declaraciones y auditorías.
                &nbsp;·&nbsp; Total anulado: <strong>L.&nbsp;{{ number_format($facturas->sum('total'), 2) }}</strong>
                &nbsp;·&nbsp; {{ $facturas->count() }} factura{{ $facturas->count() !== 1 ? 's' : '' }}.
            </td>
        </tr>

        <tr class="col-hdr">
            <th class="left"  style="width:14%">No. Factura</th>
            <th class="left"  style="width:8%">Fecha Emisión</th>
            <th class="left"  style="width:11%">Fecha Anulación</th>
            <th class="left"  style="width:11%">RTN</th>
            <th class="left"  style="width:26%">Cliente</th>
            <th class="left"  style="width:22%">Motivo de Anulación</th>
            <th class="right" style="width:8%">Total L.</th>
        </tr>
    </thead>

    <tbody>
    @foreach($facturas as $fac)
        <tr>
            <td class="mono" style="color:#8b1a1a">{{ $fac->numero_completo }}</td>
            <td>{{ $fac->fecha_emision->format('d/m/Y') }}</td>
            <td>{{ $fac->anulada_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td class="mono muted">{{ $fac->rtn_cliente ?? 'CF' }}</td>
            <td>{{ \Illuminate\Support\Str::limit($fac->nombre_cliente, 32) }}</td>
            <td>{{ $fac->motivo_anulacion ?? '—' }}</td>
            <td class="right strike">{{ number_format($fac->total, 2) }}</td>
        </tr>
    @endforeach
    </tbody>

    <tfoot>
        <tr>
            <td colspan="5" class="right" style="font-size:7px; letter-spacing:0.06em;">TOTAL ANULADO DEL PERÍODO</td>
            <td></td>
            <td class="right strike">{{ number_format($facturas->sum('total'), 2) }}</td>
        </tr>
    </tfoot>

</table>

</body>
</html>
