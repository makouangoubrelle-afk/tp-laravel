#!/bin/sh
# Script de démarrage (Railway / Render / Fly)
set -e

echo "=== AutoChain Emmaus — démarrage ==="
echo "PORT=${PORT:-8080}"

attempt=1
max=12
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
  echo "ERREUR: impossible de migrer la base. Vérifiez DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD."
  exit 1
fi

php artisan db:seed --force --no-interaction || true
php artisan config:cache || true

echo "Serveur Laravel sur 0.0.0.0:${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
