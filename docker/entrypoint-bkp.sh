#!/bin/sh
set -e

APP_DIR="/var/www/html"
SRC_DIR="/meu-marqueteiro-src"

echo "================================================"
echo " Meu Marqueteiro — Inicializando..."
echo "================================================"

# ── 1. Se ainda não tem Laravel instalado, instalar ───────────
if [ ! -f "$APP_DIR/artisan" ]; then
    echo "➤ Instalando Laravel 12 (primeira vez, ~3 min)..."
    composer create-project laravel/laravel:^12.0 /tmp/laravel-fresh \
        --prefer-dist --no-interaction --quiet
    cp -r /tmp/laravel-fresh/. "$APP_DIR/"
    rm -rf /tmp/laravel-fresh
    echo "  ✓ Laravel instalado"
fi

cd "$APP_DIR"

# ── 2. Copiar nosso código por cima do Laravel base ───────────
echo ""
echo "➤ Aplicando código do Meu Marqueteiro..."

# Copiar apenas os arquivos que existem no nosso source
for dir in app config database resources routes; do
    if [ -d "$SRC_DIR/$dir" ]; then
        cp -r "$SRC_DIR/$dir/." "$APP_DIR/$dir/"
    fi
done

# Arquivos soltos na raiz
for f in composer.json .env.example; do
    if [ -f "$SRC_DIR/$f" ]; then
        cp "$SRC_DIR/$f" "$APP_DIR/$f"
    fi
done

echo "  ✓ Código aplicado"

# ── 3. Instalar dependências extras do composer.json ──────────
echo ""
echo "➤ Instalando dependências PHP..."
composer install --no-interaction --prefer-dist --optimize-autoloader --quiet
echo "  ✓ Dependências instaladas"

# ── 4. Configurar .env ────────────────────────────────────────
if [ ! -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    echo "  ✓ .env criado a partir do .env.example"
fi

# Garantir que as variáveis de ambiente do Docker estejam no .env
if [ -n "$OPENAI_API_KEY" ]; then
    sed -i "s|^OPENAI_API_KEY=.*|OPENAI_API_KEY=$OPENAI_API_KEY|" .env
fi

if [ -n "$ANTHROPIC_API_KEY" ]; then
    sed -i "s|^ANTHROPIC_API_KEY=.*|ANTHROPIC_API_KEY=$ANTHROPIC_API_KEY|" .env
fi

# Corrigir conexão com banco e Redis apontando para os containers Docker
sed -i "s|^DB_HOST=.*|DB_HOST=postgres|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=meu_marqueteiro|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=postgres|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=secret|" .env
sed -i "s|^REDIS_HOST=.*|REDIS_HOST=redis|" .env
sed -i "s|^CACHE_DRIVER=.*|CACHE_DRIVER=redis|" .env
sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env

# ── 5. Gerar chave da aplicação ───────────────────────────────
echo ""
echo "➤ Configurando aplicação..."
php artisan key:generate --force --quiet
echo "  ✓ Chave gerada"

# ── 6. Limpar caches ─────────────────────────────────────────
php artisan config:clear --quiet
php artisan cache:clear --quiet

# ── 7. Aguardar PostgreSQL estar pronto ───────────────────────
echo ""
echo "➤ Aguardando banco de dados..."
TRIES=0
until php artisan db:show --quiet 2>/dev/null; do
    TRIES=$((TRIES+1))
    if [ $TRIES -gt 20 ]; then
        echo "  ✗ Banco não respondeu após 40s. Verifique o container postgres."
        break
    fi
    echo "  ... tentativa $TRIES/20 (aguardando 2s)"
    sleep 2
done
echo "  ✓ Banco conectado"

# ── 8. Migrations + Seed ──────────────────────────────────────
echo ""
echo "➤ Criando tabelas no banco..."
php artisan migrate --force
echo "  ✓ Migrations executadas"

# Seed apenas se a tabela users estiver vazia
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo ""
    echo "➤ Populando dados iniciais..."
    php artisan db:seed --force
    echo "  ✓ Seed concluído"
else
    echo "  ℹ Banco já populado — seed ignorado"
fi

# ── 9. Permissões ────────────────────────────────────────────
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ── 10. Subir servidor ────────────────────────────────────────
echo ""
echo "================================================"
echo " ✅ Sistema pronto! Acesse: http://localhost:8000"
echo "    Admin:   admin@meumarqueteiro.com.br"
echo "    Senha:   Admin@2024!"
echo "================================================"
echo ""

exec php artisan serve --host=0.0.0.0 --port=8000
