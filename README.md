# 📄 Generación de Certificados Digitales para AFIP (Laravel)

Este instructivo te guía paso a paso para generar la clave privada y el certificado necesario para autenticarte ante los servicios web de AFIP desde Laravel, **sin usar SDKs**.

---

## 📁 Estructura esperada

Todos los archivos se guardarán en:

```
storage/app/afip/
├── private.key   ← Clave privada
├── request.csr   ← Solicitud de certificado (CSR)
└── cert.crt      ← Certificado digital (lo descarga AFIP)
```

---

## 🛠️ Paso 1: Generar clave privada y CSR

Desde el proyecto Laravel, ejecutá el siguiente comando Artisan:

```bash
php artisan afip:gen-cert 20111111112
```

📌 Reemplazá `20111111112` por tu número de **CUIT** (sin guiones ni puntos).

Este comando:
- Genera `private.key` y `request.csr`
- Muestra en pantalla el contenido del CSR para que lo copies
- Te guía para subirlo a AFIP
- Espera que coloques el archivo `cert.crt` descargado

---

## 🌐 Paso 2: Subir CSR en el sitio de AFIP

1. Ingresá a [https://www.afip.gob.ar](https://www.afip.gob.ar)
2. Iniciá sesión con tu CUIT y Clave Fiscal
3. Accedé al servicio:  
   **Administración de Certificados Digitales**  
   > Si no está disponible, agregalo desde "Administrador de Relaciones de Clave Fiscal"

4. Seleccioná el alias del sistema webservice (o crealo)
5. Hacé clic en **"Solicitar Certificado"**
6. Pegá el contenido del archivo `request.csr`
7. Confirmá y descargá el archivo `.crt`

---

## 📥 Paso 3: Guardar el archivo `.crt`

Colocá el archivo descargado como:

```
storage/app/afip/cert.crt
```

Una vez que lo pongas ahí, el comando `afip:gen-cert` continuará y validará que el certificado está disponible.

---

## 🧪 Verificación manual (opcional)

Si querés verificar la validez del certificado:

```bash
openssl x509 -in storage/app/afip/cert.crt -noout -dates
```

Esto mostrará la fecha de inicio y expiración del certificado.

---

## 📌 Consideraciones

- ⚠️ **No subas** `private.key` ni `cert.crt` a tu repositorio. Agregalos a `.gitignore`.
- Los certificados tienen vencimiento. Podés renovarlos siguiendo este mismo proceso.
- Si usás múltiples entornos (homologación / producción), generá certificados distintos para cada uno.
- Este proceso solo genera las credenciales necesarias. Para emitir comprobantes, necesitás implementar la lógica WSAA + WSFE (login, ticket, factura, CAE).

---

## 🚀 Referencias

- [AFIP - Servicios Web](https://www.afip.gob.ar/ws/)
- [AFIP - Manuales técnicos](https://www.afip.gob.ar/ws/documentacion/)
- [AFIP - Certificados Digitales](https://www.afip.gob.ar/certificados/)
