<?php

namespace App\Services\Afip;

use SoapClient;

class WSFEv1Service
{
    protected AuthService $authService;
    protected SoapClient $client;
    protected string $wsfeUrl;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->wsfeUrl = env('AFIP_ENV') === 'prod'
            ? env('AFIP_WSFE_URL_PROD')
            : env('AFIP_WSFE_URL_HOMO');

        $this->client = new SoapClient($this->wsfeUrl, [
            'trace' => 1,
            'exceptions' => 1,
        ]);
    }

    /**
     * Genera una Factura C (Consumidor Final) según normativa AFIP.
     * 
     * - Destinada a compradores NO registrados (sin CUIT/CUIL).
     * - Valores aceptados:
     *   - DocTipo: 96 (DNI) si se identifica al comprador, o 99 (Consumidor Final).
     *   - DocNro: Número de DNI (sin espacios) si DocTipo=96, o 0 si DocTipo=99.
     * - Requiere configuración previa de CUIT emisor y datos fiscales.
     */
    public function emitirFacturaC($datos)
    {
        $auth = $this->buildAuth();
        $last = $this->client->FECompUltimoAutorizado([
            'Auth' => $auth,
            'PtoVta' => $datos['pto_vta'],
            'CbteTipo' => $datos['cbte_tipo'],
        ]);

        $cbteNro = $last->FECompUltimoAutorizadoResult->CbteNro + 1;
        $impNeto = $datos['importe'];
        $impTrib = 0.00;
        $impTotal = $impNeto + $impTrib;

        $data = [
            'FeCAEReq' => [
                'FeCabReq' => [
                    'CantReg' => 1,
                    'PtoVta' => $datos['pto_vta'],
                    'CbteTipo' => $datos['cbte_tipo'], // 11 = Fsctura C
                ],
                'FeDetReq' => [
                    'FECAEDetRequest' => [
                        'Concepto' => 2, // 1 = Productos, 2 = Servicios, 3 = Ambos
                        'DocTipo' => $datos['doc_tipo'], // 96 = CUIT
                        'DocNro' => $datos['doc_nro'],
                        'CbteDesde' => $cbteNro,
                        'CbteHasta' => $cbteNro,
                        'CbteFch' => date('Ymd'),
                        'FchServDesde' => $datos['desde'],
                        'FchServHasta' => $datos['hasta'],
                        'FchVtoPago' => date('Ymd'),
                        'ImpTotal' => $impTotal,
                        'ImpTotConc' => 0.00,
                        'ImpNeto' => $impNeto,
                        'ImpOpEx' => 0.00,
                        'ImpIVA' => 0.00,
                        'ImpTrib' => $impTrib,
                        'MonId' => 'PES',
                        'MonCotiz' => 1.00,
                        'CondicionIVAReceptorId' => 13   // Consumidor final
                    ]
                ]
            ]
        ];

        try {
            $res = $this->client->FECAESolicitar([
                'Auth' => $auth,
                'FeCAEReq' => $data['FeCAEReq']
            ]);

            $resp = $res->FECAESolicitarResult->FeDetResp->FECAEDetResponse;

            if ($resp->Resultado === "R") {
                if (property_exists($res->FECAESolicitarResult, 'Errors')) {
                    if (is_array($res->FECAESolicitarResult->Errors->Err)) {
                        $messages = array_map(function ($error) {
                            return $error->Msg;
                        }, $res->FECAESolicitarResult->Errors->Err);
                    } else {
                        $messages = [$res->FECAESolicitarResult->Errors->Err->Msg];
                    }
                } elseif (property_exists($resp, 'Observaciones')) {
                    if (is_array($resp->Observaciones->Obs)) {
                        $messages = array_map(function ($obs) {
                            return $obs->Msg;
                        }, $res->Observaciones->Obs);
                    } else {
                        $messages = [$resp->Observaciones->Obs->Msg];
                    }
                }
                return response()->json($messages, 500);
            }

            return [
                'cae' => $resp->CAE,
                'vencimiento' => $resp->CAEFchVto,
                'numero' => $cbteNro,
            ];
        } catch (\SoapFault $e) {
            // Captura errores SOAP (500, conexión, formato XML inválido, etc)
            \Log::error("SOAP Fault: {$e->getMessage()}", ['exception' => $e]);
            \Log::info($data);
            throw new \Exception("Error en la comunicación con AFIP: {$e->getMessage()}", 500);
        
        } catch (\Exception $e) {
            // Cualquier otro error
            \Log::error("Error al emitir comprobante: {$e->getMessage()}", ['exception' => $e]);
            throw $e;
        }
    }

    protected function buildAuth(): array
    {
        $tokenData = $this->authService->getToken();
        return [
            'Token' => $tokenData['token'],
            'Sign' => $tokenData['sign'],
            'Cuit' => $this->authService->getCuit()
        ];
    }
}
