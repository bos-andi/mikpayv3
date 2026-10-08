<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\SupportTicket;
use App\Models\SupportTicketUpdate;
use App\Models\User;
use App\Support\ActiveRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketsController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = current_tenant();
        $router = ActiveRouter::current();
        $status = (string) $request->query('status', 'open');
        $q = trim((string) $request->query('q', ''));

        $query = SupportTicket::query()
            ->with(['assignedTo', 'createdBy', 'odp'])
            ->where('tenant_id', $tenant?->id ?? 0)
            ->orderByDesc('created_at');

        if ($status === 'open') {
            $query->whereIn('status', SupportTicket::OPEN_STATUSES);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('subject', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('location_label', 'like', "%{$q}%");
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        $counts = [
            'open' => SupportTicket::query()
                ->where('tenant_id', $tenant?->id ?? 0)
                ->whereIn('status', SupportTicket::OPEN_STATUSES)
                ->count(),
            'resolved' => SupportTicket::query()
                ->where('tenant_id', $tenant?->id ?? 0)
                ->where('status', SupportTicket::STATUS_RESOLVED)
                ->count(),
            'closed' => SupportTicket::query()
                ->where('tenant_id', $tenant?->id ?? 0)
                ->where('status', SupportTicket::STATUS_CLOSED)
                ->count(),
        ];

        $oltNodes = Odp::query()
            ->where('tenant_id', $tenant?->id ?? 0)
            ->when($router, fn ($builder) => $builder->where('router_id', $router->id))
            ->where('type', Odp::TYPE_OLT)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $assignees = User::query()
            ->where('tenant_id', $tenant?->id ?? 0)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('panel.tickets', [
            'tickets' => $tickets,
            'counts' => $counts,
            'status' => $status,
            'q' => $q,
            'oltNodes' => $oltNodes,
            'assignees' => $assignees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        $router = ActiveRouter::current();
        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(['gangguan', 'instalasi', 'billing', 'optical', 'other'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'location_label' => ['nullable', 'string', 'max:200'],
            'odp_id' => [
                'nullable',
                'integer',
                Rule::exists('odps', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
        ]);

        $ticket = SupportTicket::query()->create([
            'tenant_id' => $tenant->id,
            'code' => SupportTicket::nextCode((int) $tenant->id),
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => SupportTicket::STATUS_OPEN,
            'router_id' => $router?->id,
            'odp_id' => $data['odp_id'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'location_label' => $data['location_label'] ?? null,
            'created_by_user_id' => auth()->id(),
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
        ]);

        if (! empty($data['description'])) {
            SupportTicketUpdate::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'body' => $data['description'],
                'is_internal' => false,
            ]);
        }

        return redirect()
            ->to(panel_route('tickets'))
            ->with('success', "Tiket {$ticket->code} berhasil dibuat.");
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant || (int) $ticket->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['nullable', Rule::in([
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_IN_PROGRESS,
                SupportTicket::STATUS_RESOLVED,
                SupportTicket::STATUS_CLOSED,
            ])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $updates = [];
        if (isset($data['status']) && $data['status'] !== $ticket->status) {
            $updates['status'] = $data['status'];
            if ($data['status'] === SupportTicket::STATUS_RESOLVED) {
                $updates['resolved_at'] = wib_now();
            }
            if ($data['status'] === SupportTicket::STATUS_CLOSED) {
                $updates['closed_at'] = wib_now();
            }
        }
        if (array_key_exists('priority', $data) && $data['priority'] !== null) {
            $updates['priority'] = $data['priority'];
        }
        if (array_key_exists('assigned_to_user_id', $data)) {
            $updates['assigned_to_user_id'] = $data['assigned_to_user_id'];
        }

        if ($updates !== []) {
            $ticket->update($updates);
        }

        if (! empty($data['note'])) {
            SupportTicketUpdate::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'body' => $data['note'],
                'is_internal' => (bool) $request->boolean('is_internal'),
            ]);
        }

        return back()->with('success', "Tiket {$ticket->code} diperbarui.");
    }

    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant || (int) $ticket->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $code = $ticket->code;
        $ticket->delete();

        return back()->with('success', "Tiket {$code} dihapus.");
    }
}
