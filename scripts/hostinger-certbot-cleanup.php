<?php

/**
 * Certbot manual DNS cleanup hook — Hostinger API.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$result = app(App\Services\HostingerDnsService::class)->certbotCleanupHook();
fwrite(STDERR, json_encode($result, JSON_UNESCAPED_UNICODE).PHP_EOL);
// Cleanup gagal tidak boleh gagalkan certbot renew.
exit(0);
