<?php

/**
 * Certbot manual DNS auth hook — Hostinger API.
 * Env dari certbot: CERTBOT_DOMAIN, CERTBOT_VALIDATION
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$result = app(App\Services\HostingerDnsService::class)->certbotAuthHook();
fwrite(STDERR, json_encode($result, JSON_UNESCAPED_UNICODE).PHP_EOL);

if (! ($result['ok'] ?? false)) {
    exit(1);
}
