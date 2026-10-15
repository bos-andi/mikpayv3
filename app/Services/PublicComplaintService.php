<?php

namespace App\Services;

use App\Jobs\SendTenantWhatsapp;
use App\Models\BillingCustomer;
use App\Models\OdpPort;
use App\Models\SupportTicket;
use App\Models\SupportTicketUpdate;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Aduan gangguan dari pelanggan lewat halaman publik {tenant}/lapor.
 */
class PublicComplaintService
{
    public function __construct(private WaApiService $waapi) {}

    /**
     * Cocokkan pelanggan: nomor HP terdaftar, atau kode pelanggan + 4 digit akhir HP.
     *
     * @return array{customer: ?BillingCustomer, error: ?string, need_last4?: bool}
     */
    public function findCustomer(Tenant $tenant, string $identifier, ?string $last4): array
    {
        $identifier = trim($identifier);
        $digits = preg_replace('/\D+/', '', $identifier) ?: '';

        if ($this->looksLikePhone($identifier)) {
            $target = $this->waapi->normalizeTarget($digits);
            $matches = BillingCustomer::query()
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get()
                ->filter(fn (BillingCustomer $c) => $this->waapi->normalizeTarget((string) $c->phone) === $target)
                ->values();

            if ($matches->isEmpty()) {
                return ['customer' => null, 'error' => 'Nomor HP tidak terdaftar sebagai pelanggan. Coba pakai kode pelanggan.'];
            }
            if ($matches->count() > 1) {
                return ['customer' => null, 'error' => 'Nomor HP ini terdaftar di beberapa akun. Silakan pakai kode pelanggan + 4 digit akhir HP.'];
            }

            return ['customer' => $matches->first(), 'error' => null];
        }

        $customer = BillingCustomer::query()
            ->where('tenant_id', $tenant->id)
            ->whereRaw('UPPER(public_code) = ?', [strtoupper($identifier)])
            ->first();

        if (! $customer) {
            return ['customer' => null, 'error' => 'Kode pelanggan tidak ditemukan.'];
        }

        if ($last4 === null || ! preg_match('/^\d{4}$/', $last4)) {
            return ['customer' => null, 'error' => 'Isi 4 digit akhir nomor HP yang terdaftar.', 'need_last4' => true];
        }

        $phoneDigits = preg_replace('/\D+/', '', (string) $customer->phone) ?: '';
        if ($phoneDigits === '' || ! str_ends_with($phoneDigits, $last4)) {
            return ['customer' => null, 'error' => 'Verifikasi 4 digit akhir HP tidak cocok.', 'need_last4' => true];
        }

        return ['customer' => $customer, 'error' => null];
    }

    public function looksLikePhone(string $identifier): bool
    {
        $digits = preg_replace('/\D+/', '', $identifier) ?: '';

        return strlen($digits) >= 9 && preg_match('/^[\d\s+\-().]+$/', trim($identifier)) === 1;
    }

    public function openTicketFor(BillingCustomer $customer): ?SupportTicket
    {
        return SupportTicket::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('billing_customer_id', $customer->id)
            ->whereIn('status', SupportTicket::OPEN_STATUSES)
            ->latest('id')
            ->first();
    }

    public function create(
        Tenant $tenant,
        BillingCustomer $customer,
        string $issueKey,
        ?string $description,
        ?string $location,
        ?string $contactPhone,
    ): SupportTicket {
        $issue = SupportTicket::PUBLIC_ISSUES[$issueKey] ?? SupportTicket::PUBLIC_ISSUES['lainnya'];
        $name = $customer->display_name ?: $customer->customer_name;

        $port = OdpPort::query()
            ->whereHas('odp', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->where('customer_name', $customer->customer_name)
            ->first();

        $phone = trim((string) $contactPhone) !== '' ? $contactPhone : $customer->phone;

        $ticket = SupportTicket::query()->create([
            'tenant_id' => $tenant->id,
            'code' => SupportTicket::nextCode((int) $tenant->id),
            'public_token' => Str::random(40),
            'subject' => $issue['label'],
            'description' => $description,
            'category' => $issue['category'],
            'priority' => $issue['priority'],
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_PUBLIC,
            'router_id' => $customer->router_id,
            'odp_id' => $port?->odp_id,
            'odp_port_id' => $port?->id,
            'billing_customer_id' => $customer->id,
            'customer_name' => $name,
            'location_label' => $location ?: $customer->address,
            'reporter_phone' => $phone ? $this->waapi->normalizeTarget((string) $phone) : null,
            'meta' => ['issue' => $issueKey, 'pppoe' => $customer->customer_name],
        ]);

        if ($description) {
            SupportTicketUpdate::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'body' => $description,
                'is_internal' => false,
            ]);
        }

        $this->notifyCreated($tenant, $ticket);

        return $ticket;
    }

    public function notifyCreated(Tenant $tenant, SupportTicket $ticket): void
    {
        if (! $this->waReady($tenant)) {
            return;
        }

        if ($ticket->reporter_phone) {
            SendTenantWhatsapp::dispatch($tenant->id, $ticket->reporter_phone, implode("\n", [
                "Halo *{$ticket->customer_name}*,",
                '',
                "Aduan Anda sudah kami terima dengan nomor tiket *{$ticket->code}*.",
                "Keluhan: {$ticket->subject}",
                '',
                'Tim kami akan segera menindaklanjuti. Cek status aduan di:',
                (string) $ticket->publicUrl(),
                '',
                "Terima kasih — {$tenant->name}",
            ]));
        }

        $adminPhone = $this->adminPhone($tenant);
        if ($adminPhone) {
            $lines = [
                '*Aduan baru dari pelanggan*',
                '',
                "Tiket: *{$ticket->code}*",
                "Pelanggan: {$ticket->customer_name}".(($ticket->meta['pppoe'] ?? null) ? ' ('.$ticket->meta['pppoe'].')' : ''),
                "Keluhan: {$ticket->subject}",
            ];
            if ($ticket->reporter_phone) {
                $lines[] = 'HP: +'.$ticket->reporter_phone;
            }
            if ($ticket->location_label) {
                $lines[] = "Lokasi: {$ticket->location_label}";
            }
            if ($ticket->description) {
                $lines[] = '';
                $lines[] = Str::limit($ticket->description, 500);
            }
            $lines[] = '';
            $lines[] = 'Buka panel: '.tenant_panel_url($tenant, '/tickets');

            SendTenantWhatsapp::dispatch($tenant->id, $adminPhone, implode("\n", $lines));
        }
    }

    public function notifyStatusChanged(SupportTicket $ticket): void
    {
        $tenant = $ticket->tenant;
        if (! $ticket->isFromPublic() || ! $ticket->reporter_phone || ! $tenant || ! $this->waReady($tenant)) {
            return;
        }

        $line = match ($ticket->status) {
            SupportTicket::STATUS_IN_PROGRESS => 'sedang *dikerjakan* oleh teknisi kami.',
            SupportTicket::STATUS_RESOLVED => 'sudah *selesai* ditangani. Jika masih ada kendala, silakan kirim aduan baru.',
            default => null,
        };
        if ($line === null) {
            return;
        }

        SendTenantWhatsapp::dispatch($tenant->id, $ticket->reporter_phone, implode("\n", [
            "Halo *{$ticket->customer_name}*,",
            '',
            "Aduan *{$ticket->code}* ({$ticket->subject}) {$line}",
            '',
            'Detail: '.$ticket->publicUrl(),
            '',
            "Terima kasih — {$tenant->name}",
        ]));
    }

    public function adminPhone(Tenant $tenant): ?string
    {
        $phone = trim((string) $tenant->metaGet('waapi.complaint_phone', ''));

        return $phone !== '' ? $this->waapi->normalizeTarget($phone) : null;
    }

    private function waReady(Tenant $tenant): bool
    {
        return $tenant->waapiEnabled() && $tenant->hasWaapiToken();
    }
}
