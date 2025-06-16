<?php

namespace App\Http\Controllers;

use App\Services\Afip\WSFEv1Service;
use Illuminate\Http\Request;

class AfipController extends Controller
{
    public function emitirFactura(Request $request, WSFEv1Service $afip)
    {
        $datos = [
            'pto_vta'   => 1,
            'cbte_tipo' => 15, // Recibo C
            'doc_tipo'  => 96, // DNI
            'doc_nro'   => $request->input('dni'), // DNI alumno
            'importe'   => $request->input('importe'),
            'desde'     => $request->input('desde'),
            'hasta'     => $request->input('hasta')
        ];
        $resultado = $afip->emitirFacturaC($datos);
        return response()->json($resultado);
    }
}
