<?php
$f = '/var/www/html/bootstrap/app.php';
$c = file_get_contents($f);
if (strpos($c, 'RoleMiddleware') !== false) { echo "  ✓ Middleware já registrado\n"; exit(0); }
$middleware = "
    ->withMiddleware(function (\\Illuminate\\Foundation\\Configuration\\Middleware \$middleware) {
        \$middleware->alias([
            'role'               => \\Spatie\\Permission\\Middleware\\RoleMiddleware::class,
            'permission'         => \\Spatie\\Permission\\Middleware\\PermissionMiddleware::class,
            'role_or_permission' => \\Spatie\\Permission\\Middleware\\RoleOrPermissionMiddleware::class,
        ]);
    })";
$c = str_replace('->withExceptions(function', $middleware . "\n    ->withExceptions(function", $c);
file_put_contents($f, $c);
echo "  ✓ Middleware registrado\n";
