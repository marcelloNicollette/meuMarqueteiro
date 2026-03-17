#!/bin/sh
set -e

APP_DIR="/var/www/html"
SRC_DIR="/meu-marqueteiro-src"

echo "================================================"
echo " Meu Marqueteiro — Produção (Nginx + PHP-FPM)"
echo "================================================"

# ── 1. Instalar Laravel base se necessário ────────────────────
if [ ! -f "$APP_DIR/artisan" ]; then
    echo "➤ Instalando Laravel 12 (primeira vez, ~3 min)..."
    composer create-project laravel/laravel:^12.0 "$APP_DIR" \
        --prefer-dist --no-interaction --quiet
    echo "  ✓ Laravel instalado"
fi

cd "$APP_DIR"

# ── 2. Copiar nosso código ────────────────────────────────────
echo "➤ Copiando arquivos do projeto..."
for dir in app config database resources routes bootstrap public; do
    [ -d "$SRC_DIR/$dir" ] && cp -r "$SRC_DIR/$dir/." "$APP_DIR/$dir/"
done
echo "  ✓ Código copiado"

# ── 3. Instalar pacotes ───────────────────────────────────────
echo "➤ Instalando pacotes..."
composer require \
    spatie/laravel-permission \
    spatie/laravel-activitylog \
    openai-php/laravel \
    laravel/sanctum \
    minishlink/web-push \
    --no-interaction 2>&1 || echo "AVISO: verifique pacotes acima"
echo "  ✓ Pacotes OK"

# ── 3b. Registrar middleware role ────────────────────────────
BOOTSTRAP="$APP_DIR/bootstrap/app.php"
if ! grep -q "RoleMiddleware" "$BOOTSTRAP" 2>/dev/null; then
    echo "➤ Registrando middleware role..."
    php /meu-marqueteiro-src/docker/patch-bootstrap.php
fi

# ── 4. Criar controllers stub que ainda não existem ──────────
echo "➤ Criando controllers..."
mkdir -p app/Http/Controllers/Admin
mkdir -p app/Http/Controllers/Mayor

create_stub() {
    local FILEPATH="$APP_DIR/app/Http/Controllers/$1.php"
    local NAMESPACE="$2"
    local CLASSNAME="$3"
    if [ ! -f "$FILEPATH" ]; then
        cat > "$FILEPATH" << PHPEOF
<?php
namespace App\Http\Controllers\\$NAMESPACE;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
class $CLASSNAME extends Controller
{
    public function index() { return response()->json(['status' => 'em desenvolvimento']); }
    public function create() { return response()->json(['status' => 'em desenvolvimento']); }
    public function store(Request \$r) { return response()->json(['status' => 'em desenvolvimento']); }
    public function show(\$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function edit(\$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function update(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function destroy(\$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function upload(Request \$r) { return response()->json(['status' => 'em desenvolvimento']); }
    public function sync(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function generatePost(Request \$r) { return response()->json(['status' => 'em desenvolvimento']); }
    public function interviewPrep(Request \$r) { return response()->json(['status' => 'em desenvolvimento']); }
    public function crisisResponse(Request \$r) { return response()->json(['status' => 'em desenvolvimento']); }
    public function publish(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function askAssistant(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function markRead(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function storeVoice(Request \$r) { return response()->json(['status' => 'em desenvolvimento']); }
    public function uploadDocuments(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function saveVoiceProfile(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function savePoliticalMap(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function complete(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function triggerDataIngestion(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
    public function generate(Request \$r) { return response()->json(['status' => 'em desenvolvimento']); }
}
PHPEOF
    fi
}

create_stub "Admin/MunicipalityController"       "Admin" "MunicipalityController"
create_stub "Admin/OnboardingController"         "Admin" "OnboardingController"
create_stub "Admin/UserController"               "Admin" "UserController"
create_stub "Admin/IntegrationMonitorController" "Admin" "IntegrationMonitorController"
create_stub "Admin/KnowledgeBaseController"      "Admin" "KnowledgeBaseController"
create_stub "Admin/ReportController"             "Admin" "ReportController"
create_stub "Admin/UsageLogController"           "Admin" "UsageLogController"
create_stub "Mayor/DashboardController"          "Mayor" "DashboardController"
create_stub "Mayor/ContentController"            "Mayor" "ContentController"
create_stub "Mayor/CommitmentController"         "Mayor" "CommitmentController"
create_stub "Mayor/FederalProgramController"     "Mayor" "FederalProgramController"
create_stub "Mayor/BriefingController"           "Mayor" "BriefingController"
create_stub "Mayor/DemandController"             "Mayor" "DemandController"
create_stub "Mayor/PushController"               "Mayor" "PushController"
create_stub "Mayor/SituacaoController"           "Mayor" "SituacaoController"
echo "  ✓ Controllers OK"

# ── 5. Garantir rota raiz ─────────────────────────────────────
if ! grep -q "redirect.*login" "$APP_DIR/routes/web.php"; then
    echo "" >> "$APP_DIR/routes/web.php"
    echo "Route::get('/', fn() => redirect()->route('login'));" >> "$APP_DIR/routes/web.php"
fi

# ── 6. Configurar .env ────────────────────────────────────────
echo "➤ Configurando .env..."
cat > "$APP_DIR/.env" << ENVEOF
APP_NAME="Meu Marqueteiro"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${APP_URL:-http://localhost}
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST:-postgres}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-meu_marqueteiro}
DB_USERNAME=${DB_USERNAME:-postgres}
DB_PASSWORD=${DB_PASSWORD:-secret}
CACHE_STORE=redis
SESSION_DRIVER=file
QUEUE_CONNECTION=redis
REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PASSWORD=null
REDIS_PORT=6379
MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST:-mailpit}
MAIL_PORT=${MAIL_PORT:-1025}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@meumarqueteiro.com.br}
MAIL_FROM_NAME="Meu Marqueteiro"
AI_DEFAULT_PROVIDER=anthropic
OPENAI_API_KEY=${OPENAI_API_KEY}
OPENAI_MODEL=${OPENAI_MODEL:-gpt-4o-mini}
OPENAI_EMBEDDING_MODEL=${OPENAI_EMBEDDING_MODEL:-text-embedding-3-small}
ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
ANTHROPIC_MODEL=${ANTHROPIC_MODEL:-claude-sonnet-4-6}
GEMINI_API_KEY=${GEMINI_API_KEY}
VECTOR_DIMENSIONS=1536
VAPID_PUBLIC_KEY=${VAPID_PUBLIC_KEY}
VAPID_PRIVATE_KEY=${VAPID_PRIVATE_KEY}
VAPID_SUBJECT=${VAPID_SUBJECT:-mailto:admin@meumarqueteiro.com.br}
TRANSPARENCIA_API_KEY=${TRANSPARENCIA_API_KEY}
ENVEOF
echo "  ✓ .env OK"

# ── 7. Gerar chave e otimizar para produção ───────────────────
php artisan key:generate --force --quiet
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

# ── 8. Aguardar PostgreSQL ────────────────────────────────────
echo "➤ Aguardando PostgreSQL..."
TRIES=0
until php -r "\$c=pg_connect('host=${DB_HOST:-postgres} port=${DB_PORT:-5432} dbname=${DB_DATABASE:-meu_marqueteiro} user=${DB_USERNAME:-postgres} password=${DB_PASSWORD:-secret}');if(\$c){pg_close(\$c);exit(0);}exit(1);" 2>/dev/null; do
    TRIES=$((TRIES+1))
    [ $TRIES -gt 20 ] && echo "  ✗ Postgres não respondeu." && break
    echo "  ... aguardando ($TRIES/20)"
    sleep 3
done
echo "  ✓ PostgreSQL conectado"

# ── 9. Migrations ────────────────────────────────────────────
echo "➤ Rodando migrations..."
php artisan migrate:fresh --force
echo "  ✓ Migrations OK"

# ── 10. Seed ──────────────────────────────────────────────────
echo "➤ Populando banco..."
php artisan db:seed --force
echo "  ✓ Seed OK"

# ── 11. Permissões ───────────────────────────────────────────
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ── 12. Criar diretório do socket PHP-FPM ────────────────────
mkdir -p /var/run/php
chown www-data:www-data /var/run/php

echo ""
echo "================================================"
echo " ✅ Meu Marqueteiro — Produção OK"
echo "    Nginx + PHP-FPM rodando na porta 80"
echo "================================================"
echo ""

# ── 13. Iniciar Supervisor (Nginx + PHP-FPM + Queue + Scheduler)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
