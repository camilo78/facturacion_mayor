<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaPdfController extends Controller
{
    public function show(string $tenantId, int $id)
    {
        $factura = Factura::with([
            'detalles',
            'puntoEmision.establecimiento',
            'caiAutorizacion',
        ])->findOrFail($id);

        $tenant = tenant();

        $pdf = Pdf::loadView('pdf.factura', compact('factura', 'tenant'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("factura-{$factura->numero_completo}.pdf");
    }
}
