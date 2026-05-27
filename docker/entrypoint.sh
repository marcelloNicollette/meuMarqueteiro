#!/bin/sh
set -e

APP_DIR="/var/www/html"

echo "================================================"
echo " Meu Marqueteiro — Inicializando..."
echo "================================================"

# ── 1. Validar projeto montado ────────────────────────────────
if [ ! -f "$APP_DIR/artisan" ]; then
    echo "✗ Projeto Laravel não  encontrado em $APP_DIR"
    echo "  Verifique se o bind mount do docker-compose está ativo."
    exit 1
fi

cd "$APP_DIR"

# ── 2. Instalar dependências PHP na primeira execução ─────────
if [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    echo "➤ Instalando dependências do Composer..."
    composer install --prefer-dist --no-interaction
    echo "  ✓ Dependências instaladas"
fi

# ── 3. Limpar caches da aplicação ─────────────────────────────
php artisan config:clear --quiet || true
php artisan route:clear --quiet || true
php artisan view:clear --quiet || true

# ── 4. Aguardar PostgreSQL ────────────────────────────────────
echo "➤ Aguardando PostgreSQL..."
TRIES=0
until php -r "\$c=pg_connect('host=postgres port=5432 dbname=meu_marqueteiro user=postgres password=secret');if(\$c){pg_close(\$c);exit(0);}exit(1);" 2>/dev/null; do
    TRIES=$((TRIES+1))
    [ $TRIES -gt 20 ] && echo "  ✗ Postgres não  respondeu." && break
    echo "  ... aguardando ($TRIES/20)"
    sleep 3
done
echo "  ✓ PostgreSQL conectado"

# ── 5. Migrations sem resetar os dados ───────────────────────
echo "➤ Rodando migrations..."
php artisan migrate --force
echo "  ✓ Migrations OK"

# ── 6. Seed opcional para primeira carga ─────────────────────
if [ "${APP_AUTO_SEED:-false}" = "true" ]; then
    echo "➤ Populando banco..."
    php artisan db:seed --force
    echo "  ✓ Seed OK"
fi

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "================================================"
echo " ✅ Acesse: http://localhost:8000"
echo "    Login:  admin@meumarqueteiro.com.br"
echo "    Senha:  Admin@2024!"
echo "================================================"
echo ""

exec php artisan serve --host=0.0.0.0 --port=8000
