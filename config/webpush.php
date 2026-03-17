<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Keys para Web Push Notifications
    |--------------------------------------------------------------------------
    | Geradas com: php artisan webpush:vapid
    | Ou via Python usando cryptography library.
    |
    | IMPORTANTE: As chaves abaixo são as chaves do projeto.
    | Em produção, mova para o .env.
    */

    'vapid_public_key' => env(
        'VAPID_PUBLIC_KEY',
        'BNHj8KI9UghRJDumAHw5YMHad6iSu-Aj3en496Aa9FjwdePtQbpXN9jDVIl4J9JxEX3gKJockfTJAgHI7Bn52Mo'
    ),

    'vapid_private_key' => env(
        'VAPID_PRIVATE_KEY',
        'Kd08YnUT13qDg2rFRFOxVG6j5stf1QGD-O2ZsmaV1cU'
    ),

    'vapid_subject' => env(
        'VAPID_SUBJECT',
        'mailto:admin@meumarqueteiro.com.br'
    ),
];