<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $tenants = Tenant::query()
            ->with(['users' => fn ($query) => $query->orderBy('id')])
            ->withCount(['users', 'routers', 'tunnelAccounts'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('subdomain', 'like', $like)
                        ->orWhereHas('users', function ($u) use ($like) {
                            $u->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->get();

        $orphanUsers = User::query()
            ->whereNull('tenant_id')
            ->where('role', '!=', User::ROLE_SUPERADMIN)
            ->orderBy('id')
            ->get();

        $superadmins = User::query()
            ->where('role', User::ROLE_SUPERADMIN)
            ->orderBy('id')
            ->get();

        return view('superadmin.users', [
            'tenants' => $tenants,
            'orphanUsers' => $orphanUsers,
            'superadmins' => $superadmins,
            'q' => $q,
            'tenantCount' => Tenant::query()->count(),
            'activeTenantCount' => Tenant::query()->where('status', 'active')->count(),
            'userCount' => User::query()->where('role', '!=', User::ROLE_SUPERADMIN)->count(),
        ]);
    }

    public function updateTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', Rule::in(['active', 'suspended', 'trial', 'inactive'])],
            'plan' => ['required', 'string', 'max:40'],
        ], [], [
            'name' => 'Nama tenant',
            'status' => 'Status',
            'plan' => 'Paket',
        ]);

        $tenant->forceFill([
            'name' => $data['name'],
            'status' => $data['status'],
            'plan' => $data['plan'],
        ])->save();

        return redirect()
            ->route('superadmin.users', array_filter(['q' => $request->query('q')]))
            ->with('success', "Tenant {$tenant->name} diperbarui.");
    }

    public function toggleTenantStatus(Tenant $tenant): RedirectResponse
    {
        $tenant->status = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->save();

        $label = $tenant->status === 'active' ? 'diaktifkan' : 'disuspend';

        return back()->with('success', "Tenant {$tenant->name} {$label}.");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && (int) $user->id !== (int) auth()->id()) {
            return back()->with('error', 'Tidak bisa mengubah superadmin lain dari sini.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in([
                User::ROLE_TENANT_ADMIN,
                User::ROLE_TENANT_USER,
                User::ROLE_SUPERADMIN,
            ])],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [], [
            'name' => 'Nama',
            'email' => 'Email',
            'role' => 'Role',
            'password' => 'Password',
        ]);

        // Jangan turunkan role diri sendiri dari superadmin.
        if ((int) $user->id === (int) auth()->id() && $data['role'] !== User::ROLE_SUPERADMIN) {
            return back()->with('error', 'Anda tidak bisa menurunkan role akun sendiri.');
        }

        if ($data['role'] === User::ROLE_SUPERADMIN) {
            $user->tenant_id = null;
        } elseif (! $user->tenant_id && $data['role'] !== User::ROLE_SUPERADMIN) {
            return back()->with('error', 'User tanpa tenant hanya boleh role superadmin, atau buat user lewat tenant.');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->is_active = $request->boolean('is_active');

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('success', "User {$user->email} diperbarui.");
    }

    public function toggleUserActive(User $user): RedirectResponse
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Tidak bisa menonaktifkan superadmin dari sini.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $label = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "User {$user->email} {$label}.");
    }

    public function storeTenantUser(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', Rule::in([User::ROLE_TENANT_ADMIN, User::ROLE_TENANT_USER])],
        ], [], [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'role' => 'Role',
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return back()->with('success', "User {$data['email']} ditambahkan ke {$tenant->name}.");
    }
}
