#!/bin/bash

# Script de backup automático para n8n
# Uso: ./backup-n8n.sh

# Variables
BACKUP_DIR="$HOME/n8n-backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="n8n-backup-$DATE"

# Crear directorio de backup si no existe
mkdir -p "$BACKUP_DIR"

echo "🔄 Iniciando backup de n8n..."

# Método 1: Backup usando comando n8n (si está disponible)
if command -v n8n &> /dev/null; then
    echo "📦 Exportando workflows..."
    n8n export:workflow --backup --output="$BACKUP_DIR/$BACKUP_NAME-workflows.json"
    
    echo "🔑 Exportando credenciales..."
    n8n export:credentials --backup --output="$BACKUP_DIR/$BACKUP_NAME-credentials.json"
fi

# Método 2: Backup manual de archivos
echo "📁 Copiando archivos de configuración..."
if [ -d "$HOME/.n8n" ]; then
    cp -r "$HOME/.n8n" "$BACKUP_DIR/$BACKUP_NAME-files/"
fi

# Comprimir backup
echo "🗜️ Comprimiendo backup..."
cd "$BACKUP_DIR"
tar -czf "$BACKUP_NAME.tar.gz" "$BACKUP_NAME"*
rm -rf "$BACKUP_NAME"*

# Limpiar backups antiguos (mantener solo los últimos 10)
echo "🧹 Limpiando backups antiguos..."
ls -t *.tar.gz | tail -n +11 | xargs -r rm

echo "✅ Backup completado: $BACKUP_DIR/$BACKUP_NAME.tar.gz"

# Mostrar información del backup
ls -lh "$BACKUP_DIR/$BACKUP_NAME.tar.gz"