<?php

namespace App\Services;

use App\Models\MikpayRouter;
use App\Services\RouterOS\RouterosAPI;
use App\Support\TenantRouterScope;
use Illuminate\Support\Facades\Log;

class MikrotikService
{
    protected ?RouterosAPI $api = null;

    protected ?MikpayRouter $router = null;

    /**
     * Connect by session name scoped to tenant. Prefer connectRouter() when MikpayRouter is already loaded.
     */
    public function connect(string $sessionName, int $userId): bool
    {
        $router = TenantRouterScope::forUser($userId)
            ->where('session_name', $sessionName)
            ->where('status', 'enable')
            ->first();
        if (! $router) {
            Log::warning('MikrotikService: Router not found or disabled for user', [
                'session' => $sessionName,
                'user_id' => $userId,
            ]);
            return false;
        }

        $this->router = $router;
        $parts = $router->getHostParts();
        $ip = $parts['ip'];
        $port = $parts['port'] ?: (int) config('mikpay.router.api_port', 8728);
        $login = $router->username;
        $password = $router->getPasswordDecoded();

        $this->api = new RouterosAPI;
        $this->api->port = $port;
        $this->api->ssl = (bool) config('mikpay.router.ssl', false);
        $this->api->timeout = (int) config('mikpay.router.timeout', 3);
        $this->api->readMaxSeconds = (int) config('mikpay.router.read_timeout', 45);

        return $this->api->connect($ip, $login, $password);
    }

    public function connectRouter(MikpayRouter $router): bool
    {
        $this->router = $router;
        $parts = $router->getHostParts();
        $ip = $parts['ip'];
        $port = $parts['port'] ?: (int) config('mikpay.router.api_port', 8728);

        $this->api = new RouterosAPI;
        $this->api->port = $port;
        $this->api->ssl = (bool) config('mikpay.router.ssl', false);
        $this->api->timeout = (int) config('mikpay.router.timeout', 3);
        $this->api->readMaxSeconds = (int) config('mikpay.router.read_timeout', 45);

        return $this->api->connect($ip, $router->username, $router->getPasswordDecoded());
    }

    public function disconnect(): void
    {
        if ($this->api) {
            $this->api->disconnect();
            $this->api = null;
        }
        $this->router = null;
    }

    public function isConnected(): bool
    {
        return $this->api && $this->api->connected;
    }

    /**
     * @return array|mixed
     */
    public function comm(string $path, array $attrs = [])
    {
        if (! $this->api || ! $this->api->connected) {
            return [];
        }
        return $this->api->comm($path, $attrs);
    }

    /**
     * Get current router model (when connected by session name).
     */
    public function getRouter(): ?MikpayRouter
    {
        return $this->router;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
