<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\AutoIsolirService;
use Illuminate\Console\Command;

class BillingAutoIsolirCommand extends Command
{
    protected $signature = 'billing:auto-isolir {--router= : ID router tertentu}';

    protected $description = 'Evaluasi tunggakan dan auto isolir/buka PPP di MikroTik';

    public function handle(AutoIsolirService $autoIsolir): int
    {
        $query = Router::query()->where('status', 'enable');
        if ($this->option('router')) {
            $query->where('id', (int) $this->option('router'));
        }

        $routers = $query->get();
        if ($routers->isEmpty()) {
            $this->warn('Tidak ada router enable.');

            return self::SUCCESS;
        }

        foreach ($routers as $router) {
            $this->info("Router #{$router->id} {$router->displayName()}...");
            $summary = $autoIsolir->syncRouter($router);
            $this->line(sprintf(
                '  isolir=%d open=%d none=%d skip=%d error=%d',
                $summary['isolir'] ?? 0,
                $summary['open'] ?? 0,
                $summary['none'] ?? 0,
                $summary['skip'] ?? 0,
                $summary['error'] ?? 0
            ));
        }

        return self::SUCCESS;
    }
}
