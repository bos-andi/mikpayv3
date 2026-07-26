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

    /**
     * Override lama (sebelum per-user): file langsung di bawah voucher-templates/.
     */
    public function legacyStoragePath(string $key): string
    {
        return storage_path('app/voucher-templates/' . $this->storageBasename($key));
    }

    /**
     * Path override per akun login: storage/app/voucher-templates/user-{id}/default.blade.php
     */
    public function storagePath(string $key, int $userId): string
    {
        $dir = $this->userStorageDir($userId);

        return $dir . DIRECTORY_SEPARATOR . $this->storageBasename($key);
    }

    public function userStorageDir(int $userId): string
    {
        return storage_path('app/voucher-templates/user-' . $userId);
    }

    /**
     * File override yang dipakai: prioritas akun, lalu file global lama (kompatibilitas).
     *
     * @return non-empty-string|null
     */
    public function resolveStoragePath(string $key, int $userId): ?string
    {
        $userPath = $this->storagePath($key, $userId);
        if (File::exists($userPath)) {
            return $userPath;
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

        return resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');
    }

    /**
     * Isi editor: override per user → override global lama → salinan view bawaan.
     */
    public function getEditorContent(string $key, int $userId): string
    {
        $resolved = $this->resolveStoragePath($key, $userId);
        if ($resolved !== null) {
            return File::get($resolved);
        }
        $bundled = $this->bundledViewFilePath($key);

        return File::exists($bundled) ? File::get($bundled) : '';
    }

    public function save(string $key, string $content, int $userId): void
    {
        $dir = $this->userStorageDir($userId);
        File::ensureDirectoryExists($dir);
        File::put($this->storagePath($key, $userId), $content);
    }

    /**
     * Render satu voucher (HTML fragment, tanpa layout penuh).
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $key, array $data, int $userId): string
    {
        $resolved = $this->resolveStoragePath($key, $userId);
        if ($resolved !== null) {
            return BladeCompiler::render(File::get($resolved), $data);
        }

        return view($this->bundledViewName($key), $data)->render();
    }
}
