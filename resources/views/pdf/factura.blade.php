@php
use Illuminate\Support\Facades\Storage;
$colorP  = $tenant->color_primario   ?? '#1b3a5c';
$colorS  = $tenant->color_secundario ?? '#009898';
$logoB64 = null;
if ($tenant->logo && Storage::disk('central_public')->exists($tenant->logo)) {
    $logoData = Storage::disk('central_public')->get($tenant->logo);
    $ext      = strtolower(pathinfo($tenant->logo, PATHINFO_EXTENSION));
    $mimeMap  = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                 'gif' => 'image/gif',  'webp'  => 'image/webp'];
    $logoMime = $mimeMap[$ext] ?? 'image/png';
    $logoB64  = "data:{$logoMime};base64," . base64_encode($logoData);
}
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 9.5px;
    color: #111;
    background: #fff;
}

.page { padding: 22px 28px 18px; }

/* ─── Utilidades ─── */
.teal  { color: {{ $colorS }}; }
.navy  { color: {{ $colorP }}; }
.mono  { font-family: 'Courier New', monospace; }
.right { text-align: right; }
.center{ text-align: center; }

/* ─── Encabezado ─── */
.header {
    display: table;
    width: 100%;
    border-bottom: 3px solid {{ $colorS }};
    padding-bottom: 10px;
    margin-bottom: 8px;
}
.header-logo  {
    display: table-cell;
    width: 18%;
    vertical-align: middle;
    padding-right: 10px;
}
.header-emp   {
    display: table-cell;
    width: 44%;
    vertical-align: top;
    border-left: 1px solid #e5e7eb;
    padding-left: 12px;
    padding-right: 10px;
}
.header-right {
    display: table-cell;
    width: 38%;
    vertical-align: top;
    text-align: right;
    border-left: 1px solid #e5e7eb;
    padding-left: 12px;
}
/* Sin logo: empresa ocupa el espacio del logo */
.header-emp-full {
    display: table-cell;
    width: 62%;
    vertical-align: top;
    padding-right: 10px;
}
.empresa-logo {
    width: 100%;
    height: auto;
    display: block;
}

.empresa-nombre {
    font-size: 15px;
    font-weight: 700;
    color: {{ $colorP }};
    text-transform: uppercase;
    line-height: 1.2;
}
.empresa-comercial { font-size: 10px; color: {{ $colorS }}; font-weight: 600; margin-top: 2px; }
.empresa-datos { font-size: 8.5px; color: #444; margin-top: 5px; line-height: 1.4; }
.empresa-datos .lbl { color: {{ $colorP }}; font-weight: 700; }

.doc-tipo {
    font-size: 22px;
    font-weight: 900;
    color: {{ $colorS }};
    text-transform: uppercase;
    letter-spacing: 2px;
}
.doc-numero {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    font-weight: 700;
    color: {{ $colorP }};
    margin-top: 3px;
}
.cai-block {
    margin-top: 7px;
    font-size: 8px;
    line-height: 1.5;
    text-align: right;
}
.cai-block .cai-lbl {
    font-weight: 700;
    color: {{ $colorS }};
    text-transform: uppercase;
    font-size: 7px;
    letter-spacing: 0.5px;
}
.cai-block .cai-val {
    font-family: 'Courier New', monospace;
    color: {{ $colorP }};
    font-weight: 700;
    font-size: 8px;
}

/* ─── Fila cliente + datos factura ─── */
.info-row {
    display: table;
    width: 100%;
    margin-bottom: 8px;
    border: 1px solid #e5e7eb;
}
.info-cell {
    display: table-cell;
    padding: 6px 10px;
    vertical-align: top;
    width: 50%;
}
.info-cell + .info-cell { border-left: 1px solid #e5e7eb; }
.info-label {
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: {{ $colorS }};
    margin-bottom: 3px;
}
.info-value { font-size: 9.5px; color: #111; line-height: 1.4; }
.info-value strong { color: {{ $colorP }}; }
.info-field { font-size: 9px; color: #111; line-height: 1.5; }
.info-field .lbl { color: {{ $colorS }}; font-weight: 700; }

/* ─── Tabla de ítems ─── */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }

.items-table thead tr { background: {{ $colorS }}; }
.items-table thead th {
    padding: 5px 7px;
    font-size: 7.5px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 700;
    color: #fff;
    text-align: left;
    white-space: nowrap;
}
.items-table thead th.right  { text-align: right; }
.items-table thead th.center { text-align: center; }

.items-table tbody tr { border-bottom: 1px solid #e5e7eb; }
.items-table tbody tr:nth-child(even) { background: #f9fffe; }
.items-table tbody td {
    padding: 4px 7px;
    font-size: 9px;
    color: #222;
    vertical-align: top;
}
.items-table tbody td.right {
    text-align: right;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
}
.items-table tbody td.center { text-align: center; }

/* ─── Van y viene ─── */
.van-viene {
    display: table;
    width: 100%;
    margin-top: 6px;
    padding-top: 5px;
    border-top: 1px dashed #d1d5db;
    font-size: 7.5px;
    color: #9ca3af;
}
.van-viene .izq { display: table-cell; font-style: italic; }
.van-viene .der {
    display: table-cell;
    text-align: right;
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #6b7280;
}

/* ─── Área inferior ─── */
.bottom-area {
    display: table;
    width: 100%;
    margin-bottom: 10px;
    page-break-inside: avoid;
}
.bottom-left  { display: table-cell; width: 52%; vertical-align: top; padding-right: 14px; }
.bottom-right { display: table-cell; width: 48%; vertical-align: top; }

.letras-box {
    border: 1px solid {{ $colorS }};
    padding: 7px 10px;
    font-size: 8.5px;
    margin-bottom: 8px;
    background: #f9fffe;
    page-break-inside: avoid;
}
.letras-label {
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: {{ $colorS }};
    margin-bottom: 2px;
}
.letras-value { font-weight: 700; color: {{ $colorP }}; line-height: 1.5; }
.letras-ref {
    margin-top: 5px;
    font-size: 8px;
    color: #555;
    border-top: 1px dashed #ccc;
    padding-top: 4px;
}

.sar-fields { border: 1px solid #e5e7eb; font-size: 8.5px; }
.sar-field {
    padding: 4px 10px;
    color: #444;
    border-bottom: 1px solid #e5e7eb;
    line-height: 1.4;
}
.sar-field:last-child { border-bottom: none; }
.sar-field-lbl { color: {{ $colorP }}; font-weight: 700; }

.totals-box { border: 1px solid #e5e7eb; }
.totals-row {
    display: table;
    width: 100%;
    padding: 4px 9px;
    font-size: 9px;
    border-bottom: 1px solid #f0f0f0;
}
.totals-row:last-child { border-bottom: none; }
.totals-row .lbl { display: table-cell; color: #555; }
.totals-row .val {
    display: table-cell;
    text-align: right;
    font-family: 'Courier New', monospace;
    color: #222;
    white-space: nowrap;
}
.totals-row.muted .lbl,
.totals-row.muted .val { color: #aaa; }
.totals-row.discount .lbl,
.totals-row.discount .val { color: #dc2626; }
.totals-sep { border-top: 1px solid #e5e7eb; }
.totals-grand {
    display: table;
    width: 100%;
    background: {{ $colorP }};
    color: #fff;
    padding: 7px 9px;
}
.totals-grand .lbl {
    display: table-cell;
    font-weight: 700;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.totals-grand .val {
    display: table-cell;
    text-align: right;
    font-family: 'Courier New', monospace;
    font-weight: 700;
    font-size: 13px;
    white-space: nowrap;
}

/* ─── Anulada ─── */
.anulada-watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%) rotate(-35deg);
    font-size: 80px;
    font-weight: 900;
    color: rgba(220,38,38,0.10);
    text-transform: uppercase;
    letter-spacing: 8px;
    pointer-events: none;
    z-index: 0;
}
.anulada-banner {
    background: #fef2f2;
    border: 1.5px solid #fca5a5;
    padding: 6px 10px;
    font-size: 8.5px;
    color: #b91c1c;
    font-weight: 700;
    margin-bottom: 8px;
    page-break-inside: avoid;
}

/* ─── Footer ─── */
.footer { border-top: 2px solid {{ $colorS }}; margin-top: 10px; padding-top: 6px; page-break-inside: avoid; }
.footer-copies { display: table; width: 100%; margin-bottom: 5px; }
.footer-copy {
    display: table-cell;
    text-align: center;
    font-size: 8px;
    font-weight: 700;
    color: {{ $colorP }};
    padding: 4px 0;
    border: 1px solid {{ $colorP }};
}
.footer-copy + .footer-copy { border-left: none; }
.footer-meta { display: table; width: 100%; }
.footer-left  { display: table-cell; font-size: 7.5px; color: #666; vertical-align: middle; }
.footer-right { display: table-cell; text-align: right; font-size: 7.5px; color: #666; vertical-align: middle; }
.footer-legal { font-size: 7px; color: {{ $colorS }}; font-weight: 600; }
</style>
</head>
<body>
<?php
/* ── Número en letras (Honduras) ── */
function _w(int $n): string {
    if ($n === 0) return '';
    $u = ['','UN','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE',
          'DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS',
          'DIECISIETE','DIECIOCHO','DIECINUEVE','VEINTE'];
    $dec = ['','','VEINTI','TREINTA','CUARENTA','CINCUENTA',
            'SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $cen = ['','CIEN','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS',
            'SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
    if ($n <= 20) return $u[$n];
    if ($n < 30)  return 'VEINTI'.$u[$n-20];
    if ($n < 100) { $t=intdiv($n,10);$r=$n%10; return $r?$dec[$t].' Y '.$u[$r]:$dec[$t]; }
    if ($n < 1000) {
        $h=intdiv($n,100);$r=$n%100;
        $hw=($h===1&&$r)?'CIENTO':$cen[$h];
        return $r?$hw.' '._w($r):$hw;
    }
    if ($n < 2000) { $r=$n%1000; return $r?'MIL '._w($r):'MIL'; }
    if ($n < 1000000) { $m=intdiv($n,1000);$r=$n%1000; return _w($m).' MIL'.($r?' '._w($r):''); }
    $m=intdiv($n,1000000);$r=$n%1000000;
    $mw=$m===1?'UN MILLÓN':_w($m).' MILLONES';
    return $mw.($r?' '._w($r):'');
}
function montoALetras(float $monto): string {
    $entero   = (int) $monto;
    $centavos = (int) round(($monto - $entero) * 100);
    $letras   = $entero === 0 ? 'CERO' : _w($entero);
    return $letras.' LEMPIRAS CON '.str_pad($centavos, 2, '0', STR_PAD_LEFT).'/100';
}

$establecimiento = $factura->puntoEmision->establecimiento ?? null;

$exento    = 0.0; $exonerado = 0.0;
$gravado15 = 0.0; $isv15     = 0.0;
$gravado18 = 0.0; $isv18     = 0.0;
foreach ($factura->detalles as $d) {
    $tasa = (float) $d->impuesto_tasa;
    if ($tasa == 0)        { $exento    += (float) $d->subtotal; }
    elseif ($tasa >= 17.5) { $gravado18 += (float) $d->subtotal; $isv18 += (float) $d->isv; }
    else                   { $gravado15 += (float) $d->subtotal; $isv15 += (float) $d->isv; }
}

$cai     = $factura->caiAutorizacion;
$prefijo = $factura->establecimiento_codigo.'-'
         . $factura->punto_emision_codigo.'-'
         . $factura->tipo_documento.'-';
$rangoIni = $cai ? $prefijo.str_pad($cai->rango_inicial, 8, '0', STR_PAD_LEFT) : '—';
$rangoFin = $cai ? $prefijo.str_pad($cai->rango_final,   8, '0', STR_PAD_LEFT) : '—';
$lugarEmision = $establecimiento?->nombre ?? ($establecimiento?->direccion ?? 'Honduras');

/* ── Paginación manual ────────────────────────────────────────────────────
   $porPaginaUnica   : máx. items cuando todo cabe en 1 página (con totales)
   $porPaginaPrimera : máx. items en página 1 de facturas multi-página
   $porPaginaMedia   : máx. items en páginas intermedias
   $porPaginaFinal   : máx. items en la última página junto con los totales

   La última página siempre lleva los totales. Si los items restantes son
   mayores a $porPaginaFinal, se abre una nueva página intermedia primero.
   Si al final no quedan items (restantes=0), la última página muestra solo
   el bloque de totales con el encabezado — lo que luce como "hoja resumen".
   ─────────────────────────────────────────────────────────────────────── */
$porPaginaUnica   = 19;
$porPaginaPrimera = 30;
$porPaginaMedia   = 32;
$porPaginaFinal   = 20;

$detalles = $factura->detalles->values();
$nItems   = $detalles->count();
$paginas  = [];

if ($nItems <= $porPaginaUnica) {
    // Una sola página: items + totales
    $paginas[] = $detalles;
} else {
    $resto = $detalles;

    // Página 1: items sin totales
    $paginas[] = $resto->take($porPaginaPrimera);
    $resto     = $resto->slice($porPaginaPrimera)->values();

    // Páginas intermedias mientras queden más items de los que caben con totales
    while ($resto->count() > $porPaginaFinal) {
        $paginas[] = $resto->take($porPaginaMedia);
        $resto     = $resto->slice($porPaginaMedia)->values();
    }

    // Última página: 0–$porPaginaFinal items + totales
    $paginas[] = $resto;
}
$totalPaginas = count($paginas);
?>

@if($factura->estado === 'ANULADA')
<div class="anulada-watermark">ANULADA</div>
@endif

@foreach($paginas as $nPag => $chunk)
@if($nPag > 0)
<div style="page-break-before: always"></div>
@endif

<div class="page">

    {{-- ══ ENCABEZADO (se repite manualmente en cada página) ═══════════ --}}
    <div class="header">

        {{-- Columna 1: Logo (solo si existe) --}}
        @if($logoB64)
        <div class="header-logo">
            <img src="{{ $logoB64 }}" class="empresa-logo" alt="{{ $tenant->nombre }}">
        </div>
        @endif

        {{-- Columna 2: Info empresa --}}
        <div class="{{ $logoB64 ? 'header-emp' : 'header-emp-full' }}">
            <div class="empresa-nombre">{{ $tenant->nombre }}</div>
            @if($tenant->nombre_comercial && $tenant->nombre_comercial !== $tenant->nombre)
            <div class="empresa-comercial">{{ $tenant->nombre_comercial }}</div>
            @endif
            <div class="empresa-datos">
                @if($establecimiento?->direccion){{ $establecimiento->direccion }}<br>@endif
                Honduras<br>
                @if($establecimiento?->telefono)
                    <span class="lbl">Tel:</span> {{ $establecimiento->telefono }}
                @elseif($tenant->telefono)
                    <span class="lbl">Tel:</span> {{ $tenant->telefono }}
                @endif
                @if($tenant->email) &nbsp;/ <span class="lbl">Email:</span> {{ $tenant->email }}@endif
                <br><span class="lbl">RTN:</span> {{ $tenant->rtn ?? 'N/A' }}
            </div>
        </div>

        {{-- Columna 3: FACTURA + número + CAI --}}
        <div class="header-right">
            <div class="doc-tipo">Factura</div>
            <div class="doc-numero">{{ $factura->numero_completo }}</div>
            <div class="cai-block">
                <div><span class="cai-lbl">CAI:</span> <span class="cai-val">{{ $factura->cai }}</span></div>
                <div><span class="cai-lbl">Rango autorizado</span></div>
                <div><span class="cai-val">{{ $rangoIni }}</span></div>
                <div><span class="cai-val">al {{ $rangoFin }}</span></div>
                <div>
                    <span class="cai-lbl">Fecha límite de emisión:</span>
                    <span class="cai-val">{{ $cai?->fecha_limite_emision->format('d/m/Y') ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ ANULADA (solo en primera página) ══════════════════════════════ --}}
    @if($nPag === 0 && $factura->estado === 'ANULADA')
    <div class="anulada-banner">
        FACTURA ANULADA
        @if($factura->motivo_anulacion) &mdash; {{ $factura->motivo_anulacion }} @endif
        @if($factura->anulada_at) &nbsp;({{ $factura->anulada_at->format('d/m/Y H:i') }}) @endif
    </div>
    @endif

    {{-- ══ DATOS CLIENTE (solo en primera página) ════════════════════════ --}}
    @if($nPag === 0)
    <div class="info-row">
        <div class="info-cell">
            <div class="info-label">Cliente</div>
            <div class="info-value">
                <strong>{{ $factura->nombre_cliente }}</strong><br>
                @if(!empty($factura->direccion_cliente)){{ $factura->direccion_cliente }}<br>@endif
                @if($factura->rtn_cliente)
                    <span style="color:{{ $colorS }};font-weight:700">RTN:</span> {{ $factura->rtn_cliente }}
                @endif
            </div>
        </div>
        <div class="info-cell">
            <div class="info-label">Datos de la factura</div>
            <div class="info-field">
                <span class="lbl">Fecha de factura:</span> {{ $factura->fecha_emision->format('d/m/Y') }}<br>
                <span class="lbl">Hora:</span> {{ $factura->fecha_emision->format('H:i') }}<br>
                <span class="lbl">Término de pago:</span> {{ ucfirst($factura->tipo_pago) }}<br>
                <span class="lbl">Lugar de emisión:</span> {{ $lugarEmision }}
            </div>
        </div>
    </div>
    @endif

    {{-- ══ TABLA DE ÍTEMS (solo si hay items en este chunk) ═══════════════ --}}
    @if($chunk->isNotEmpty())
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:40%">Descripción</th>
                <th class="right" style="width:13%">Cantidad</th>
                <th class="right" style="width:12%">Precio unit.</th>
                <th class="right" style="width:10%">Descto</th>
                <th class="center" style="width:11%">Impuesto</th>
                <th class="right" style="width:14%">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $d)
            <tr>
                <td>{{ $d->descripcion }}</td>
                <td class="right">
                    {{ rtrim(rtrim(number_format($d->cantidad, 3), '0'), '.') }}
                    <span style="font-size:7.5px;color:#888">{{ $d->unidad_medida }}</span>
                </td>
                <td class="right">{{ number_format($d->precio_unitario, 2) }}</td>
                <td class="right">
                    @if($d->descuento > 0) L {{ number_format($d->descuento, 2) }}
                    @else <span style="color:#ccc">L 0.00</span>
                    @endif
                </td>
                <td class="center" style="font-size:8px">
                    @if($d->impuesto_tasa > 0) ISV {{ number_format($d->impuesto_tasa, 0) }}%
                    @else <span style="color:{{ $colorS }};font-weight:700">Exento</span>
                    @endif
                </td>
                <td class="right" style="font-weight:700;color:{{ $colorP }}">L {{ number_format($d->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif {{-- /chunk->isNotEmpty --}}

    {{-- ══ VAN Y VIENE (páginas intermedias) / TOTALES + FOOTER (última) ══ --}}
    @if($nPag < $totalPaginas - 1)

        {{-- Página intermedia: leyenda y numeración --}}
        <div class="van-viene">
            <span class="izq">Van y viene&hellip; &nbsp; {{ $factura->numero_completo }}</span>
            <span class="der">Página {{ $nPag + 1 }} de {{ $totalPaginas }}</span>
        </div>

    @else

        {{-- Última página: totales + footer --}}
        <div class="bottom-area">
            <div class="bottom-left">
                <div class="letras-box">
                    <div class="letras-label">Son:</div>
                    <div class="letras-value">{{ montoALetras($factura->total) }}</div>
                    <div class="letras-ref">
                        Referencia para su pago:
                        <strong style="color:{{ $colorP }};font-family:'Courier New',monospace">{{ $factura->numero_completo }}</strong>
                    </div>
                </div>
                <div class="sar-fields">
                    <div class="sar-field">
                        <span class="sar-field-lbl">No. Orden de compra exenta:</span><br>
                        {!! $factura->orden_compra_exenta ? e($factura->orden_compra_exenta) : '&nbsp;' !!}
                    </div>
                    <div class="sar-field">
                        <span class="sar-field-lbl">No. Constancia de Registro Exonerado:</span><br>
                        {!! $factura->num_constancia_exonerado ? e($factura->num_constancia_exonerado) : '&nbsp;' !!}
                    </div>
                    <div class="sar-field">
                        <span class="sar-field-lbl">No. Registro SAG:</span><br>
                        {!! $factura->num_registro_sag ? e($factura->num_registro_sag) : '&nbsp;' !!}
                    </div>
                </div>
            </div>
            <div class="bottom-right">
                <div class="totals-box">
                    @if($factura->descuento > 0)
                    <div class="totals-row discount">
                        <span class="lbl">Rebajas y descuentos otorgados</span>
                        <span class="val">L {{ number_format($factura->descuento, 2) }}</span>
                    </div>
                    @else
                    <div class="totals-row muted">
                        <span class="lbl">Rebajas y descuentos otorgados</span>
                        <span class="val">0.00</span>
                    </div>
                    @endif
                    <div class="totals-sep"></div>
                    <div class="totals-row {{ $exento == 0 ? 'muted' : '' }}">
                        <span class="lbl">Importe Exento</span>
                        <span class="val">L {{ number_format($exento, 2) }}</span>
                    </div>
                    <div class="totals-row muted">
                        <span class="lbl">Importe Exonerado</span>
                        <span class="val">L {{ number_format($exonerado, 2) }}</span>
                    </div>
                    <div class="totals-row {{ $gravado15 == 0 ? 'muted' : '' }}">
                        <span class="lbl">Importe Gravado al 15%</span>
                        <span class="val">L {{ number_format($gravado15, 2) }}</span>
                    </div>
                    <div class="totals-row {{ $isv15 == 0 ? 'muted' : '' }}">
                        <span class="lbl">ISV 15%</span>
                        <span class="val">L {{ number_format($isv15, 2) }}</span>
                    </div>
                    <div class="totals-row muted">
                        <span class="lbl">Importe Gravado al 18%</span>
                        <span class="val">L {{ number_format($gravado18, 2) }}</span>
                    </div>
                    <div class="totals-row muted">
                        <span class="lbl">ISV 18%</span>
                        <span class="val">L {{ number_format($isv18, 2) }}</span>
                    </div>
                </div>
                <div class="totals-grand">
                    <span class="lbl">Total Factura</span>
                    <span class="val">L {{ number_format($factura->total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-copies">
                <div class="footer-copy">Original: Cliente</div>
                <div class="footer-copy">Copia: Obligado Tributario Emisor</div>
                <div class="footer-copy">Copia 2: Archivo</div>
            </div>
            <div class="footer-meta">
                <div class="footer-left">
                    <span class="footer-legal">Documento fiscal válido &mdash; Servicio de Administración de Rentas (SAR) Honduras</span><br>
                    <span style="color:#999">Lugar de emisión: {{ $lugarEmision }}</span>
                </div>
                <div class="footer-right">
                    <span style="color:#999">{{ now()->format('d/m/Y H:i') }}</span>
                    @if($totalPaginas > 1)
                    <br><span style="color:{{ $colorP }};font-weight:700">Página {{ $totalPaginas }} de {{ $totalPaginas }}</span>
                    @endif
                </div>
            </div>
        </div>

    @endif

</div>{{-- /page --}}
@endforeach

</body>
</html>
