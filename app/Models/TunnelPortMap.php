<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TunnelPortMap extends Model
{
    protected $fillable = [
        'tunnel_account_id',
        'label',
        'remote_port',
        'target_port',
        'protocol',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'remote_port' => 'integer',
            'target_port' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TunnelAccount::class, 'tunnel_account_id');
    }

    public function endpoint(): string
    {
        $this->loadMissing('account.node');

        $host = $this->account?->nodeHostname()
            ?? config('mikpay.tunnel.default_node_host', 'an-1.mikpay.link');

        return $host.':'.$this->remote_port;
    }

    /** Contoh tampilan List VPN: an-1.mikpay.link:1000 ↔ 8291 */
    public function remoteArrowLabel(): string
    {
        return $this->endpoint().' ↔ '.$this->target_port;
    }
}
