<?php

namespace App\Console\Commands;

use App\Models\OltDevice;
use App\Services\OltMonitorService;
use Illuminate\Console\Command;

class OltPollCommand extends Command
{
    protected $signature = 'olt:poll {--tenant= : ID tenant tertentu} {--router= : ID router tertentu}';

    protected $description = 'Poll status OLT via tunnel HTTP endpoint';

    public function handle(OltMonitorService $monitor): int
    {
        $query = OltDevice::query()->with('tenant');
        if ($this->option('tenant')) {
            $query->where('tenant_id', (int) $this->option('tenant'));
        }
        if ($this->option('router')) {
            $query->where('router_id', (int) $this->option('router'));
        }

        $devices = $query->get();
        if ($devices->isEmpty()) {
            $this->warn('Tidak ada perangkat OLT.');

            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            $result = $monitor->pollDevice($device);
            $this->line(sprintf(
                'OLT #%d %s → %s (%s)',
                $device->id,
                $device->name,
                $result['status'] ?? 'unknown',
                $result['message'] ?? ''
            ));
        }

        return self::SUCCESS;
    }
}
