<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AfipCertGenerate extends Command
{
    protected $signature = 'afip:gen-cert';
    protected $description = 'Genera private.key y request.csr para AFIP';

    public function handle()
    {
        $env = env('AFIP_ENV');
        $cuit = env('AFIP_CUIT');

        if (!preg_match('/^\d{11}$/', $cuit)) {
            $this->error('CUIT inválido. Debe tener 11 dígitos.');
            return 1;
        }

        $afipDir = storage_path("app/afip/{$env}");
        if (!File::exists($afipDir)) {
            File::makeDirectory($afipDir, 0755, true);
        }

        $privateKey = "{$afipDir}/private.key";
        $csr = "{$afipDir}/request.csr";

        $this->info('🔐 Generando clave privada...');
        exec("openssl genrsa -out {$privateKey} 2048", $out1, $ret1);
        if ($ret1 !== 0) {
            $this->error("Error generando clave privada.");
            return 1;
        }

        $this->info('📝 Generando solicitud de certificado (CSR)...');
        exec("openssl req -new -key {$privateKey} -subj \"/CN={$cuit}\" -out {$csr}", $out2, $ret2);
        if ($ret2 !== 0) {
            $this->error("Error generando CSR.");
            return 1;
        }

        $this->info("\n✅ ¡CSR generado correctamente!");
        $this->info("👉 Copiá el siguiente contenido y pegalo en:");
        $this->line("🔗 https://auth.afip.gob.ar/contribuyente_/certDigital.htm");
        $this->info("⬇️  Luego descargá el archivo .crt y colocálo en: `storage/app/afip/cert.crt`");

        $this->line("\n📋 Contenido del CSR:");
        $this->line(file_get_contents($csr));

        $this->warn("\n🕐 Esperando que coloques el archivo 'cert.crt' en el directorio...");

        while (!File::exists("{$afipDir}/cert.crt")) {
            sleep(2);
        }

        $this->info("🎉 ¡Certificado detectado! Listo para usar con AFIP.");
        return 0;
    }
}
