<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Services\PublicComplaintService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Halaman publik pelanggan untuk melapor gangguan: {tenant}.mikpay.link/lapor
 */
class PublicComplaintController extends Controller
{
    public function __construct(private PublicComplaintService $complaints) {}

    public function create(): View
    {
        return view('public.complaint-form', [
            'tenant' => $this->tenant(),
            'issues' => SupportTicket::PUBLIC_ISSUES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        if (filled($request->input('website'))) {
            return back();
        }

        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:32'],
            'phone_last4' => ['nullable', 'string', 'size:4', 'regex:/^\d{4}$/'],
            'issue' => ['required', Rule::in(array_keys(SupportTicket::PUBLIC_ISSUES))],
            'description' => ['nullable', 'required_if:issue,lainnya', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s+\-().]{9,20}$/'],
        ], [
            'description.required_if' => 'Ceritakan keluhan Anda untuk jenis "Lainnya".',
        ], [
            'identifier' => 'Kode pelanggan / nomor HP',
            'phone_last4' => '4 digit akhir HP',
            'issue' => 'Jenis keluhan',
            'description' => 'Keterangan',
            'location' => 'Lokasi',
            'contact_phone' => 'Nomor WA aktif',
        ]);

        $match = $this->complaints->findCustomer($tenant, $data['identifier'], $data['phone_last4'] ?? null);
        if (! $match['customer']) {
            return back()
                ->withInput()
                ->with('error', $match['error'])
                ->with('need_last4', (bool) ($match['need_last4'] ?? false));
        }

        $existing = $this->complaints->openTicketFor($match['customer']);
        if ($existing) {
            return redirect()
                ->to('/lapor/t/'.$existing->public_token)
                ->with('success', "Aduan Anda sebelumnya ({$existing->code}) masih kami proses. Tidak perlu membuat aduan baru.");
        }

        $ticket = $this->complaints->create(
            $tenant,
            $match['customer'],
            $data['issue'],
            isset($data['description']) ? trim($data['description']) : null,
            isset($data['location']) ? trim($data['location']) : null,
            $data['contact_phone'] ?? null,
        );

        return redirect()
            ->to('/lapor/t/'.$ticket->public_token)
            ->with('success', "Aduan terkirim. Nomor tiket Anda {$ticket->code}.");
    }

    public function check(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'check_last4' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
        ], [], [
            'code' => 'Nomor tiket',
            'check_last4' => '4 digit akhir HP',
        ]);

        $ticket = SupportTicket::query()
            ->with('billingCustomer')
            ->where('tenant_id', $tenant->id)
            ->where('source', SupportTicket::SOURCE_PUBLIC)
            ->whereRaw('UPPER(code) = ?', [strtoupper(trim($data['code']))])
            ->first();

        $phones = array_filter([
            preg_replace('/\D+/', '', (string) $ticket?->reporter_phone),
            preg_replace('/\D+/', '', (string) $ticket?->billingCustomer?->phone),
        ]);
        $verified = $ticket && collect($phones)->contains(fn ($p) => str_ends_with($p, $data['check_last4']));

        if (! $verified) {
            return back()->withInput()->with('check_error', 'Nomor tiket atau 4 digit HP tidak cocok.');
        }

        return redirect()->to('/lapor/t/'.$ticket->public_token);
    }

    public function show(string $token): View
    {
        $tenant = $this->tenant();

        $ticket = SupportTicket::query()
            ->where('tenant_id', $tenant->id)
            ->where('public_token', $token)
            ->firstOrFail();

        $updates = $ticket->updates()
            ->where('is_internal', false)
            ->with('user:id,name')
            ->get();

        return view('public.complaint-status', [
            'tenant' => $tenant,
            'ticket' => $ticket,
            'updates' => $updates,
        ]);
    }

    private function tenant(): Tenant
    {
        $tenant = current_tenant();
        abort_unless($tenant instanceof Tenant && $tenant->id, 404);

        return $tenant;
    }
}
