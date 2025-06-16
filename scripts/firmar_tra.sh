#!/bin/bash

# Variables
AMBIENTE=${1:-homo}  # 'prod' o 'homo'
SERVICE=${2:-wsfe}

if [ "$AMBIENTE" = "prod" ]; then
  CERT_PATH="../storage/app/afip/prod/cert.crt"
  KEY_PATH="../storage/app/afip/prod/private.key"
elif [ "$AMBIENTE" = "homo" ]; then
  CERT_PATH="../storage/app/afip/homo/cert.crt"
  KEY_PATH="../storage/app/afip/homo/private.key"
else
  echo "❌ Ambiente inválido. Usá 'prod' o 'homo'."
  exit 1
fi

UNIQUE_ID=$(date +%s)
GENERATION_TIME=$(date --utc --date='-10 minutes' +%Y-%m-%dT%H:%M:%SZ)
EXPIRATION_TIME=$(date --utc --date='+10 minutes' +%Y-%m-%dT%H:%M:%SZ)

# Crear TRA
cat > tra.xml <<EOF
<loginTicketRequest version="1.0">
  <header>
    <uniqueId>${UNIQUE_ID}</uniqueId>
    <generationTime>${GENERATION_TIME}</generationTime>
    <expirationTime>${EXPIRATION_TIME}</expirationTime>
  </header>
  <service>${SERVICE}</service>
</loginTicketRequest>
EOF

echo "✅ TRA.xml generado."

# Firmar TRA
openssl cms -sign -signer "$CERT_PATH" -inkey "$KEY_PATH" -outform DER -nodetach -in tra.xml -out tra.cms
echo "✅ TRA.xml firmado (tra.cms)."

# Base64 del CMS
base64 tra.cms > tra.cms.base64
echo "✅ tra.cms.base64 listo para usar en Postman."
