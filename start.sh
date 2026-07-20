#!/bin/sh
# Script de démarrage (Railway / Render / Fly)
set -e

echo "=== AutoChain Emmaus — démarrage ==="

# Valeurs par défaut production
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export SESSION_DRIVER="${SESSION_DRIVER:-database}"
export BLOCKCHAIN_MODE="${BLOCKCHAIN_MODE:-simulation}"
export NODE_BINARY="${NODE_BINARY:-node}"
export ALLOW_DEMO_WALLET="${ALLOW_DEMO_WALLET:-false}"

# Render : URL publique automatique
if [ -z "$APP_URL" ] && [ -n "$RENDER_EXTERNAL_URL" ]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
  echo "APP_URL Render : $APP_URL"
fi

# Render / PostgreSQL : DATABASE_URL
if [ -n "$DATABASE_URL" ]; then
  case "$DATABASE_URL" in
    postgres://*|postgresql://*) export DB_CONNECTION=pgsql ;;
    mysql://*) export DB_CONNECTION=mysql ;;
  esac
fi

# Railway : URL publique automatique
if [ -z "$APP_URL" ] && [ -n "$RAILWAY_PUBLIC_DOMAIN" ]; then
  export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
  echo "APP_URL détecté : $APP_URL"
fi

# Railway : variables MySQL natives (si DB_* non définies)
if [ -z "$DB_HOST" ] && [ -n "$MYSQLHOST" ]; then export DB_HOST="$MYSQLHOST"; fi
if [ -z "$DB_PORT" ] && [ -n "$MYSQLPORT" ]; then export DB_PORT="$MYSQLPORT"; fi
if [ -z "$DB_DATABASE" ] && [ -n "$MYSQLDATABASE" ]; then export DB_DATABASE="$MYSQLDATABASE"; fi
if [ -z "$DB_USERNAME" ] && [ -n "$MYSQLUSER" ]; then export DB_USERNAME="$MYSQLUSER"; fi
if [ -z "$DB_PASSWORD" ] && [ -n "$MYSQLPASSWORD" ]; then export DB_PASSWORD="$MYSQLPASSWORD"; fi

# créer le fichier SQLite si on utilise SQLite
if [ "$DB_CONNECTION" = 'sqlite' ]; then
  export DB_DATABASE="${DB_DATABASE:-/tmp/database.sqlite}"
  mkdir -p "$(dirname "$DB_DATABASE")"
  touch "$DB_DATABASE"
fi

# Clé d'application si absente
if [ -z "$APP_KEY" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  echo "APP_KEY générée automatiquement."
fi

# Cookie sécurisé si HTTPS
if [ -z "$SESSION_SECURE_COOKIE" ]; then
  case "$APP_URL" in
    https://*) export SESSION_SECURE_COOKIE=true ;;
    *) export SESSION_SECURE_COOKIE=false ;;
  esac
fi

echo "PORT=${PORT:-8080}"
echo "DB_HOST=${DB_HOST:-non défini}"

attempt=1
max=15
while [ "$attempt" -le "$max" ]; do
  if php artisan migrate --force; then
    echo "Migrations terminées."
    break
  fi

  echo "Migration échouée (tentative $attempt/$max), nouvel essai dans 5s..."
  attempt=$((attempt + 1))
  sleep 5
done

if [ "$attempt" -gt "$max" ]; then
  echo "ERREUR: connexion base de données impossible."
  echo "Vérifiez DATABASE_URL (Render) ou MySQL connecté (Railway)."
  exit 1
fi

php artisan db:seed --force --no-interaction || true
php artisan config:clear
php artisan config:cache

echo "Serveur Laravel sur 0.0.0.0:${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
