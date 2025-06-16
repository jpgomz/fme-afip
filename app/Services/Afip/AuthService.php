<?php

namespace App\Services\Afip;

use SimpleXMLElement;

class AuthService
{
    protected string $cuit;
    protected string $service;
    protected string $env;
    protected string $envPath;
    protected string $certPath;
    protected string $keyPath;
    protected string $wsaaUrl;
    protected string $taPath;

    public function __construct()
    {
        $this->cuit = env('AFIP_CUIT');
        $this->service = 'wsfe';
        $this->env = env('AFIP_ENV');
        $this->envPath = "app/afip/{$this->env}";
        $this->certPath = storage_path("{$this->envPath}/cert.crt");
        $this->keyPath = storage_path("{$this->envPath}/private.key");
        $this->taPath = storage_path("{$this->envPath}/TA-{$this->service}.xml");
        $this->wsaaUrl = $this->env === 'prod'
            ? env('AFIP_AUTH_URL_PROD')
            : env('AFIP_AUTH_URL_HOMO');
    }

    public function getToken(): array
    {
        if (file_exists($this->taPath)) {
            $ta = simplexml_load_file($this->taPath);
            $exp = strtotime((string)$ta->header->expirationTime);
            if ($exp > time() + 60) {
                return [
                    'token' => (string)$ta->credentials->token,
                    'sign' => (string)$ta->credentials->sign,
                ];
            }
        }

        $tra = $this->createTRA();
        $signedTRA = $this->signTRA($tra);
        $ta = $this->callWSAA($signedTRA);

        file_put_contents($this->taPath, $ta);
        $taXML = simplexml_load_string($ta);

        return [
            'token' => (string)$taXML->credentials->token,
            'sign' => (string)$taXML->credentials->sign,
        ];
    }

    protected function createTRA(): string
    {
        $uniqueId = time();
        $generationTime = date('c', $uniqueId - 60);
        $expirationTime = date('c', $uniqueId + 600);

        $tra = new SimpleXMLElement('<loginTicketRequest version="1.0"/>');
        $header = $tra->addChild('header');
        $header->addChild('uniqueId', $uniqueId);
        $header->addChild('generationTime', $generationTime);
        $header->addChild('expirationTime', $expirationTime);
        $tra->addChild('service', $this->service);

        $traPath = storage_path("{$this->envPath}/TRA-{$this->service}.xml");
        $tra->asXML($traPath);

        return $traPath;
    }

    protected function signTRA(string $traPath): string
    {
        $tmp = storage_path("{$this->envPath}/TRA-signed.tmp");

        $cmd = "openssl cms -sign -in {$traPath} -signer {$this->certPath} -inkey {$this->keyPath} -nodetach -out {$tmp} -outform DER";
        exec($cmd, $output, $ret);
        if ($ret !== 0) {
            throw new \Exception("Error firmando TRA: " . implode("\n", $output));
        }

        return base64_encode(file_get_contents($tmp));
    }

    protected function callWSAA(string $signedTRA): string
    {
        $client = new \SoapClient($this->wsaaUrl, [
            'trace' => 1, 
            'exceptions' => 1
        ]);
        $res = $client->loginCms(['in0' => $signedTRA]);
        return $res->loginCmsReturn;
    }

    public function getCuit(): string
    {
        return $this->cuit;
    }
}
