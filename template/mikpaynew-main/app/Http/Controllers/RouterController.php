<?php

namespace App\Http\Controllers;

use App\Models\MikpayRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RouterController extends Controller
{
    /**
     * Form tambah router baru (sesuai template: admin.php?id=settings&router=new-XXXX).
     */
    public function create()
    {
        return view('routers.create');
    }

    /**
     * Simpan router baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('mikpay_routers', 'session_name')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
            'host' => 'required|string|max:255',
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'router_name' => 'nullable|string|max:100',
            'domain' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'currency_position' => 'nullable|in:10,20',
            'expiry_days' => 'nullable|integer|min:1|max:365',
            'status' => 'nullable|in:enable,disable',
            'livereport' => 'nullable|in:enable,disable',
        ], [], [
            'session_name' => 'Nama Session',
            'host' => 'IP MikroTik',
            'username' => 'Username',
            'password' => 'Password',
            'router_name' => 'Nama Router',
        ]);

        $sessionName = preg_replace('/\s+/', '_', trim($request->session_name));
        if (strtolower($sessionName) === 'mikpay') {
            return redirect()->back()->withInput()->with('error', 'Session name tidak boleh "mikpay".');
        }

        MikpayRouter::create([
            'user_id' => Auth::id(),
            'session_name' => $sessionName,
            'host' => preg_replace('/\s+/', '', $request->host),
            'username' => $request->username,
            'password' => base64_encode($request->password),
            'router_name' => $request->router_name ?: $sessionName,
            'domain' => $request->domain ?: '',
            'currency' => $request->currency ?: 'Rp',
            'currency_position' => (int) ($request->currency_position ?? 10),
            'expiry_mode' => 1,
            'expiry_days' => (int) ($request->expiry_days ?? 10),
            'status' => $request->status ?: 'enable',
            'livereport' => $request->livereport ?: 'disable',
        ]);

        return redirect()->route('sessions')->with('success', 'Router berhasil ditambahkan.');
    }

    /**
     * Form edit router (sesuai template: admin.php?id=settings&session=XXX).
     */
    public function edit(MikpayRouter $router)
    {
        return view('routers.edit', ['router' => $router]);
    }

    /**
     * Update router.
     */
    public function update(Request $request, MikpayRouter $router)
    {
        $request->validate([
            'session_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('mikpay_routers', 'session_name')
                    ->ignore($router->id)
                    ->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
            'host' => 'required|string|max:255',
            'username' => 'required|string|max:100',
            'password' => 'nullable|string|max:255',
            'router_name' => 'nullable|string|max:100',
            'domain' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'currency_position' => 'nullable|in:10,20',
            'expiry_days' => 'nullable|integer|min:1|max:365',
            'status' => 'nullable|in:enable,disable',
            'livereport' => 'nullable|in:enable,disable',
        ], [], [
            'session_name' => 'Nama Session',
            'host' => 'IP MikroTik',
            'username' => 'Username',
            'router_name' => 'Nama Router',
        ]);

        $sessionName = preg_replace('/\s+/', '_', trim($request->session_name));
        if (strtolower($sessionName) === 'mikpay') {
            return redirect()->back()->withInput()->with('error', 'Session name tidak boleh "mikpay".');
        }

        $data = [
            'session_name' => $sessionName,
            'host' => preg_replace('/\s+/', '', $request->host),
            'username' => $request->username,
            'router_name' => $request->router_name ?: $sessionName,
            'domain' => $request->domain ?: '',
            'currency' => $request->currency ?: 'Rp',
            'currency_position' => (int) ($request->currency_position ?? 10),
            'expiry_days' => (int) ($request->expiry_days ?? 10),
            'status' => $request->status ?: 'enable',
            'livereport' => $request->livereport ?: 'disable',
        ];
        if ($request->filled('password')) {
            $data['password'] = base64_encode($request->password);
        }

        $router->update($data);

        return redirect()->route('sessions')->with('success', 'Router berhasil diubah.');
    }

    /**
     * Hapus router (sesuai template: remove-session).
     */
    public function destroy(MikpayRouter $router)
    {
        if (strtolower($router->session_name) === 'mikpay') {
            return redirect()->route('sessions')->with('error', 'Tidak dapat menghapus session mikpay.');
        }
        $router->delete();
        return redirect()->route('sessions')->with('success', 'Router berhasil dihapus.');
    }
}
