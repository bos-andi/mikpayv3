<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\View\Compilers\BladeCompiler;

class VoucherTemplateService
{
    public const KEYS = ['default', 'thermal', 'small'];

    /** @var array<string, array{storage: string, view: string}> */
    private const MAP = [
        'default' => ['storage' => 'default.blade.php', 'view' => 'voucher.print-default'],
        'thermal' => ['storage' => 'thermal.blade.php', 'view' => 'voucher.print-thermal'],
        'small' => ['storage' => 'small.blade.php', 'view' => 'voucher.print-small'],
    ];

    public function isValidKey(string $key): bool
    {
        return isset(self::MAP[$key]);
    }

    public function storageBasename(string $key): string
    {
        return self::MAP[$key]['storage'] ?? 'default.blade.php';
    }

    public function bundledViewName(string $key): string
    {
        return self::MAP[$key]['view'] ?? 'voucher.print-default';
    }

    public function legacyStoragePath(string $key): string
    {
        return storage_path('app/voucher-templates/'.$this->storageBasename($key));
    }

    /**
     * Override per tenant: storage/app/voucher-templates/tenant-{id}/default.blade.php
     */
    public function storagePath(string $key, int $tenantId): string
    {
        return $this->tenantStorageDir($tenantId).DIRECTORY_SEPARATOR.$this->storageBasename($key);
    }

    public function tenantStorageDir(int $tenantId): string
    {
        return storage_path('app/voucher-templates/tenant-'.$tenantId);
    }

    public function resolveStoragePath(string $key, int $tenantId): ?string
    {
        $tenantPath = $this->storagePath($key, $tenantId);
        if (File::exists($tenantPath)) {
            return $tenantPath;
        }

        $legacy = $this->legacyStoragePath($key);
        if (File::exists($legacy)) {
            return $legacy;
        }

        return null;
    }

    public function bundledViewFilePath(string $key): string
    {
        $view = $this->bundledViewName($key);

        return resource_path('views/'.str_replace('.', '/', $view).'.blade.php');
    }

    public function getEditorContent(string $key, int $tenantId): string
    {
        $resolved = $this->resolveStoragePath($key, $tenantId);
        if ($resolved !== null) {
            return File::get($resolved);
        }

        $bundled = $this->bundledViewFilePath($key);

        return File::exists($bundled) ? File::get($bundled) : '';
    }

    public function save(string $key, string $content, int $tenantId): void
    {
        $dir = $this->tenantStorageDir($tenantId);
        File::ensureDirectoryExists($dir);
        File::put($this->storagePath($key, $tenantId), $content);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $key, array $data, int $tenantId): string
    {
        $resolved = $this->resolveStoragePath($key, $tenantId);
        if ($resolved !== null) {
            return BladeCompiler::render(File::get($resolved), $data);
        }

        return view($this->bundledViewName($key), $data)->render();
    }
}
