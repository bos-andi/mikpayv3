<?php

namespace App\Helpers;

class MikrotikFormat
{
    public static function formatBytes(int $size, int $decimals = 0): string
    {
        $unit = ['Byte', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $i = 0;
        while ($size >= 1024 && $i < count($unit) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, $decimals) . ' ' . $unit[$i];
    }

    /** Format MikroTik uptime string to readable (e.g. 2d1h30m15s -> 2d 1h 30m 15s) */
    public static function formatUptime(string $dtm): string
    {
        $s = preg_replace('/([0-9]+)([wdhms])/', '$1$2 ', $dtm);
        return trim($s) ?: $dtm;
    }
}
