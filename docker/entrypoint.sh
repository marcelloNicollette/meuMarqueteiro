#!/bin/sh
set -e

APP_DIR="/var/www/html"
SRC_DIR="/meu-marqueteiro-src"

echo "================================================"
echo " Meu Marqueteiro — Inicializando..."
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
# Copiar arquivos da raiz do projeto
for f in .env.example; do
    [ -f "$SRC_DIR/$f" ] && cp "$SRC_DIR/$f" "$APP_DIR/$f"
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
    --no-interaction 2>&1 || echo "AVISO: falha em algum pacote — verifique acima"
echo "  ✓ Pacotes OK"

# ── 3b. Registrar middleware role do Spatie no bootstrap/app.php ──
BOOTSTRAP="$APP_DIR/bootstrap/app.php"
if ! grep -q "RoleMiddleware" "$BOOTSTRAP" 2>/dev/null; then
    echo "➤ Registrando middleware role..."
    cat > /tmp/patch_bootstrap.php << 'PHPEOF'
<?php
$file = '/var/www/html/bootstrap/app.php';
$content = file_get_contents($file);

$middlewareAlias = <<<'MW'
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
MW;

if (strpos($content, 'RoleMiddleware') === false) {
    // Inserir antes do ->withExceptions
    $content = str_replace(
        '->withExceptions(function (Exceptions $exceptions) {',
        $middlewareAlias . '    ->withExceptions(function (Exceptions $exceptions) {',
        $content
    );
    file_put_contents($file, $content);
    echo "  ✓ Middleware role registrado\n";
} else {
    echo "  ↩ Middleware role já registrado\n";
}
PHPEOF
    php /tmp/patch_bootstrap.php
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
    public function generate(Request \$r, \$id) { return response()->json(['status' => 'em desenvolvimento']); }
}
PHPEOF
    fi
}

create_stub "Admin/MunicipalityController"      "Admin" "MunicipalityController"
create_stub "Admin/OnboardingController"        "Admin" "OnboardingController"
create_stub "Admin/UserController"              "Admin" "UserController"
create_stub "Admin/IntegrationMonitorController" "Admin" "IntegrationMonitorController"
create_stub "Admin/KnowledgeBaseController"     "Admin" "KnowledgeBaseController"
create_stub "Admin/ReportController"            "Admin" "ReportController"
create_stub "Admin/UsageLogController"          "Admin" "UsageLogController"
create_stub "Mayor/DashboardController"         "Mayor" "DashboardController"
create_stub "Mayor/ContentController"           "Mayor" "ContentController"
create_stub "Mayor/CommitmentController"        "Mayor" "CommitmentController"
create_stub "Mayor/FederalProgramController"    "Mayor" "FederalProgramController"
create_stub "Mayor/BriefingController"          "Mayor" "BriefingController"
create_stub "Mayor/DemandController"            "Mayor" "DemandController"
create_stub "Mayor/PushController"              "Mayor" "PushController"
create_stub "Mayor/SituacaoController"          "Mayor" "SituacaoController"
echo "  ✓ Controllers OK"

# ── 5. Garantir rota raiz ─────────────────────────────────────
if ! grep -q "redirect.*login" "$APP_DIR/routes/web.php"; then
    echo "" >> "$APP_DIR/routes/web.php"
    echo "Route::get('/', fn() => redirect()->route('login'));" >> "$APP_DIR/routes/web.php"
fi

# ── 6. Configurar .env ────────────────────────────────────────
echo "➤ Configurando .env..."
cat > "$APP_DIR/.env" << 'ENVEOF'
APP_NAME="Meu Marqueteiro"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=meu_marqueteiro
DB_USERNAME=postgres
DB_PASSWORD=secret
CACHE_STORE=redis
SESSION_DRIVER=file
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
AI_DEFAULT_PROVIDER=anthropic
OPENAI_API_KEY=${OPENAI_API_KEY}
OPENAI_ORGANIZATION=${OPENAI_ORGANIZATION}
OPENAI_MODEL=${OPENAI_MODEL:-gpt-4o-mini}
OPENAI_EMBEDDING_MODEL=${OPENAI_EMBEDDING_MODEL:-text-embedding-3-small}
ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
ANTHROPIC_MODEL=${ANTHROPIC_MODEL:-claude-sonnet-4-6}
GEMINI_API_KEY=${GEMINI_API_KEY}
GEMINI_MODEL=${GEMINI_MODEL:-gemini-1.5-pro}
VECTOR_DIMENSIONS=1536
VAPID_PUBLIC_KEY=${VAPID_PUBLIC_KEY}
VAPID_PRIVATE_KEY=${VAPID_PRIVATE_KEY}
VAPID_SUBJECT=${VAPID_SUBJECT:-mailto:admin@meumarqueteiro.com.br}
ENVEOF
echo "  ✓ .env OK"

# ── 7. Gerar chave e limpar caches ───────────────────────────
php artisan key:generate --force --quiet
php artisan config:clear --quiet
php artisan route:clear --quiet

# ── 8. Aguardar PostgreSQL ────────────────────────────────────
echo "➤ Aguardando PostgreSQL..."
TRIES=0
until php -r "\$c=pg_connect('host=postgres port=5432 dbname=meu_marqueteiro user=postgres password=secret');if(\$c){pg_close(\$c);exit(0);}exit(1);" 2>/dev/null; do
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

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "================================================"
echo " ✅ Acesse: http://localhost:8000"
echo "    Login:  admin@meumarqueteiro.com.br"
echo "    Senha:  Admin@2024!"
echo "================================================"
echo ""

exec php artisan serve --host=0.0.0.0 --port=8000