<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Manajemen user oleh admin tenant: tambah staf & atur menu yang boleh dibuka.
 */
class TenantUsersController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', [User::ROLE_TENANT_ADMIN, User::ROLE_TENANT_USER])
            ->orderByRaw('role = ? desc', [User::ROLE_TENANT_ADMIN])
            ->orderBy('name')
            ->get();

        return view('panel.settings.users', [
            'users' => $users,
            'modules' => TenantAccess::MODULES,
            'presets' => TenantAccess::PRESETS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            ...$this->accessRules(),
        ], [], $this->attributeNames());

        if (User::isAllowedSuperAdminEmail($data['email'])) {
            return back()->withInput()->with('error', 'Email ini tidak bisa dipakai untuk user tenant.');
        }

        User::query()->create([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'role' => $data['role'],
            'permissions' => $this->permissionsFor($data),
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->to(panel_route('settings.users'))
            ->with('success', "User {$data['name']} ditambahkan. Ia bisa login dengan email {$data['email']}.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            ...$this->accessRules(),
        ], [], $this->attributeNames());

        if (User::isAllowedSuperAdminEmail($data['email'])) {
            return back()->with('error', 'Email ini tidak bisa dipakai untuk user tenant.');
        }

        $isSelf = (int) $user->id === (int) auth()->id();
        if ($isSelf && $data['role'] !== User::ROLE_TENANT_ADMIN) {
            return back()->with('error', 'Anda tidak bisa menurunkan hak akses akun sendiri.');
        }

        if ($user->isTenantAdmin() && $data['role'] !== User::ROLE_TENANT_ADMIN && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Minimal harus ada 1 admin aktif di ISP ini.');
        }

        $user->name = $data['name'];
        $user->email = strtolower(trim($data['email']));
        $user->role = $data['role'];
        $user->permissions = $this->permissionsFor($data);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect()
            ->to(panel_route('settings.users'))
            ->with('success', "User {$user->name} diperbarui.");
    }

    public function toggle(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }

        if ($user->is_active && $user->isTenantAdmin() && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Minimal harus ada 1 admin aktif di ISP ini.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', "User {$user->name} ".($user->is_active ? 'diaktifkan.' : 'dinonaktifkan dan tidak bisa login.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        if ($user->isTenantAdmin() && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Minimal harus ada 1 admin aktif di ISP ini.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "User {$name} dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function accessRules(): array
    {
        return [
            'role' => ['required', Rule::in([User::ROLE_TENANT_ADMIN, User::ROLE_TENANT_USER])],
            'permissions' => ['exclude_unless:role,'.User::ROLE_TENANT_USER, 'required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(TenantAccess::moduleKeys())],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'role' => 'Hak akses',
            'permissions' => 'Menu yang boleh dibuka',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>|null
     */
    private function permissionsFor(array $data): ?array
    {
        return $data['role'] === User::ROLE_TENANT_USER
            ? TenantAccess::sanitize($data['permissions'] ?? [])
            : null;
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return ! User::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('role', User::ROLE_TENANT_ADMIN)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->exists();
    }

    private function authorizeUser(User $user): void
    {
        abort_unless(
            (int) $user->tenant_id === (int) $this->tenant()->id
                && in_array($user->role, [User::ROLE_TENANT_ADMIN, User::ROLE_TENANT_USER], true),
            404
        );
    }

    private function tenant(): Tenant
    {
        $tenant = current_tenant();
        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }
}
