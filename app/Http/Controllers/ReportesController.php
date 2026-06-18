<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesController extends Controller
{
    private function filtros(Request $request): array
    {
        return [
            'tipo'  => in_array($request->get('tipo'), ['libro_ventas', 'isv', 'anuladas'])
                ? $request->get('tipo') : 'libro_ventas',
            'desde' => $request->get('desde', now()->startOfMonth()->format('Y-m-d')),
            'hasta' => $request->get('hasta', now()->endOfMonth()->format('Y-m-d')),
            'estab' => $request->integer('estab') ?: null,
        ];
    }

    private function consulta(array $f, bool $anuladas = false)
    {
        return Factura::with(['detalles', 'puntoEmision.establecimiento'])
            ->whereDate('fecha_emision', '>=', $f['desde'])
            ->whereDate('fecha_emision', '<=', $f['hasta'])
            ->when($f['estab'], fn($q) => $q->whereHas('puntoEmision', fn($q2) =>
                $q2->where('establecimiento_id', $f['estab'])
            ))
            ->where('estado', $anuladas ? 'ANULADA' : 'VIGENTE')
            ->orderBy('fecha_emision')
            ->orderBy('numero_completo');
    }

    private function enriquecer($facturas): void
    {
        $facturas->each(function ($f) {
            $f->_exento    = $f->detalles->where('impuesto_tasa', 0)->sum('subtotal');
            $f->_gravado15 = $f->detalles->where('impuesto_tasa', 15)->sum('subtotal');
            $f->_isv15     = $f->detalles->where('impuesto_tasa', 15)->sum('isv');
            $f->_gravado18 = $f->detalles->where('impuesto_tasa', 18)->sum('subtotal');
            $f->_isv18     = $f->detalles->where('impuesto_tasa', 18)->sum('isv');
            // Si tiene Constancia de Exoneración, su monto "exento" se clasifica como exonerado
            $f->_exonerado   = $f->num_constancia_exonerado ? $f->_exento : 0;
            $f->_exento_puro = $f->num_constancia_exonerado ? 0 : $f->_exento;
        });
    }

    public function pdf(string $tenantId, Request $request)
    {
        $f = $this->filtros($request);
        $facturas = $this->consulta($f, $f['tipo'] === 'anuladas')->get();
        $this->enriquecer($facturas);
        $tenant = tenant();

        $view = match ($f['tipo']) {
            'isv'      => 'pdf.reporte-isv',
            'anuladas' => 'pdf.reporte-anuladas',
            default    => 'pdf.reporte-libro-ventas',
        };

        $pdf = Pdf::loadView($view, compact('facturas', 'tenant', 'f'))
            ->setPaper('legal', 'landscape');

        // Renderizar y añadir numeración de páginas con la API canvas de DomPDF
        // (counter(pages) CSS no es soportado por DomPDF — {PAGE_NUM}/{PAGE_COUNT} sí lo son)
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $w      = $canvas->get_width();
        $h      = $canvas->get_height();
        $font   = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');

        // Con margin:1cm el margen inferior es ~28px; colocamos el footer a 18px del borde
        $canvas->page_text(
            28, $h - 18,
            $tenant->nombre . '  ·  Art. 41 Ley ISV — conservar 5 años (Art. 116 Código Tributario)',
            $font, 6.5, [0.55, 0.55, 0.55]
        );
        $canvas->page_text(
            $w - 120, $h - 18,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $font, 6.5, [0.55, 0.55, 0.55]
        );

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"reporte-{$f['tipo']}-{$f['desde']}-{$f['hasta']}.pdf\"",
        ]);
    }

    public function csv(string $tenantId, Request $request): StreamedResponse
    {
        $f = $this->filtros($request);
        $facturas = $this->consulta($f, $f['tipo'] === 'anuladas')->get();
        $this->enriquecer($facturas);
        $tenant = tenant();

        $titulos = [
            'libro_ventas' => 'Libro de Ventas',
            'isv'          => 'Resumen ISV',
            'anuladas'     => 'Facturas Anuladas',
        ];

        $filename = "reporte-{$f['tipo']}-{$f['desde']}-{$f['hasta']}.csv";

        return response()->streamDownload(function () use ($facturas, $f, $tenant, $titulos) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel

            // ── Encabezado empresa ────────────────────────────────────
            fputcsv($out, [$titulos[$f['tipo']] . ' — ' . ($tenant->nombre ?? '')]);
            fputcsv($out, ['RTN:', $tenant->rtn ?? '']);
            if ($tenant->nombre_comercial && $tenant->nombre_comercial !== $tenant->nombre) {
                fputcsv($out, ['Nombre comercial:', $tenant->nombre_comercial]);
            }
            fputcsv($out, ['Teléfono:', $tenant->telefono ?? '']);
            fputcsv($out, ['Email:', $tenant->email ?? '']);
            fputcsv($out, ['Período:', $f['desde'] . ' al ' . $f['hasta']]);
            fputcsv($out, ['Generado:', now()->format('d/m/Y H:i')]);
            fputcsv($out, []); // línea en blanco

            // ── Datos ─────────────────────────────────────────────────
            if ($f['tipo'] === 'anuladas') {
                fputcsv($out, ['No. Factura', 'Fecha Emisión', 'Fecha Anulación', 'Cliente', 'RTN', 'Motivo Anulación', 'Total L.']);
                foreach ($facturas as $fac) {
                    fputcsv($out, [
                        $fac->numero_completo,
                        $fac->fecha_emision->format('d/m/Y'),
                        $fac->anulada_at?->format('d/m/Y H:i') ?? '',
                        $fac->nombre_cliente,
                        $fac->rtn_cliente ?? 'CF',
                        $fac->motivo_anulacion ?? '',
                        number_format($fac->total, 2, '.', ''),
                    ]);
                }
                fputcsv($out, []); // línea en blanco
                fputcsv($out, ['', '', '', '', '', 'TOTAL ANULADO:', number_format($facturas->sum('total'), 2, '.', '')]);
            } else {
                fputcsv($out, [
                    'No. Factura', 'Fecha', 'Cliente', 'RTN',
                    'Exento L.', 'Exonerado L.',
                    'Gravado 15% L.', 'ISV 15% L.',
                    'Gravado 18% L.', 'ISV 18% L.',
                    'Total L.',
                ]);
                foreach ($facturas as $fac) {
                    fputcsv($out, [
                        $fac->numero_completo,
                        $fac->fecha_emision->format('d/m/Y'),
                        $fac->nombre_cliente,
                        $fac->rtn_cliente ?? 'CF',
                        number_format($fac->_exento_puro, 2, '.', ''),
                        number_format($fac->_exonerado, 2, '.', ''),
                        number_format($fac->_gravado15, 2, '.', ''),
                        number_format($fac->_isv15, 2, '.', ''),
                        number_format($fac->_gravado18, 2, '.', ''),
                        number_format($fac->_isv18, 2, '.', ''),
                        number_format($fac->total, 2, '.', ''),
                    ]);
                }
                // Fila de totales
                fputcsv($out, []); // línea en blanco
                fputcsv($out, [
                    'TOTALES', '', '', '',
                    number_format($facturas->sum('_exento_puro'), 2, '.', ''),
                    number_format($facturas->sum('_exonerado'), 2, '.', ''),
                    number_format($facturas->sum('_gravado15'), 2, '.', ''),
                    number_format($facturas->sum('_isv15'), 2, '.', ''),
                    number_format($facturas->sum('_gravado18'), 2, '.', ''),
                    number_format($facturas->sum('_isv18'), 2, '.', ''),
                    number_format($facturas->sum('total'), 2, '.', ''),
                ]);

                // Resumen ISV-103 (solo para libro_ventas e isv)
                fputcsv($out, []);
                fputcsv($out, ['--- RESUMEN PARA DECLARACIÓN ISV-103 ---']);
                fputcsv($out, ['Casilla 4020 - Ventas Exentas:', number_format($facturas->sum('_exento_puro'), 2, '.', '')]);
                fputcsv($out, ['Casilla 4030 - Ventas Exoneradas:', number_format($facturas->sum('_exonerado'), 2, '.', '')]);
                fputcsv($out, ['Casilla 4000 - Ventas Gravadas 15%:', number_format($facturas->sum('_gravado15'), 2, '.', '')]);
                fputcsv($out, ['Casilla 4001 - ISV 15%:', number_format($facturas->sum('_isv15'), 2, '.', '')]);
                fputcsv($out, ['Casilla 4010 - Ventas Gravadas 18%:', number_format($facturas->sum('_gravado18'), 2, '.', '')]);
                fputcsv($out, ['Casilla 4011 - ISV 18%:', number_format($facturas->sum('_isv18'), 2, '.', '')]);
                fputcsv($out, ['Casilla 4040 - Total Ventas:', number_format($facturas->sum('total'), 2, '.', '')]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
