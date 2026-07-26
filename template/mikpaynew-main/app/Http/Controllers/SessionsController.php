<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\FonnteSetting;
use App\Models\MikpayRouter;
use App\Services\FonnteApiService;
use App\Services\TelegramBotService;
use App\Support\TenantRouterScope;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class SessionsController extends Controller
{
    private function normalizeIndonesiaWhatsapp(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '62')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    /**
     * Halaman pertama setelah login: pilih/kelola router (sesuai template admin.php?id=sessions).
     */
    public function index()
    {
        $user = auth()->user();
        $routers = TenantRouterScope::forUser((int) ($user?->id ?? 0))->orderBy('session_name')->get();
        $selected = session('selected_router');
        $countEnabled = $routers->where('status', 'enable')->count();
        $countDisabled = $routers->where('status', '!=', 'enable')->count();

        $subscriptionUntil = null;
        if ($user && $user->subscription_end) {
            try {
                $subscriptionUntil = Carbon::parse($user->subscription_end);
            } catch (\Throwable $e) {
                $subscriptionUntil = null;
            }
        }

        return view('sessions.index', [
            'routers' => $routers,
            'selected_router' => $selected,
            'count_enabled' => $countEnabled,
            'count_disabled' => $countDisabled,
            'subscription_until' => $subscriptionUntil?->format('d M Y'),
            'businessSetting' => BusinessSetting::get(),
            'fonnteSetting' => $user ? FonnteSetting::forUser($user) : null,
        ]);
    }

    /**
     * Halaman konfigurasi Fonnte (WhatsApp API) per user.
     */
    public function fonnte()
    {
        $user = auth()->user();
        $fonnte = FonnteSetting::forUser($user);

        return view('sessions.fonnte', [
            'fonnte' => $fonnte,
        ]);
    }

    /**
     * Simpan pengaturan Fonnte per user.
     */
    public function updateFonnte(Request $request)
    {
        $user = $request->user();
        $fonnte = FonnteSetting::forUser($user);

        $validated = $request->validate([
            'enabled' => ['nullable', 'in:0,1'],
            'api_token' => ['nullable', 'string', 'max:512'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'auto_send_h3' => ['nullable', 'in:0,1'],
            'auto_send_h0' => ['nullable', 'in:0,1'],
            'auto_send_overdue' => ['nullable', 'in:0,1'],
            'send_window_start' => ['required', 'date_format:H:i'],
            'send_window_end' => ['required', 'date_format:H:i'],
            'antispam_enabled' => ['nullable', 'in:0,1'],
            'antispam_delay_min' => ['required', 'integer', 'min:0', 'max:600'],
            'antispam_delay_max' => ['required', 'integer', 'min:0', 'max:600'],
            'antispam_hourly_limit' => ['required', 'integer', 'min:1', 'max:5000'],
            'antispam_daily_limit' => ['required', 'integer', 'min:1', 'max:50000'],
            'antispam_randomize_message' => ['nullable', 'in:0,1'],
            'template_greetings_json' => ['nullable', 'string', 'max:65535'],
            'template_closings_json' => ['nullable', 'string', 'max:65535'],
            'template_reminder' => ['nullable', 'string', 'max:65535'],
            'template_due_today' => ['nullable', 'string', 'max:65535'],
            'template_janji_bayar' => ['nullable', 'string', 'max:65535'],
            'template_overdue' => ['nullable', 'string', 'max:65535'],
        ]);

        if ((int) $validated['antispam_delay_max'] < (int) $validated['antispam_delay_min']) {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->withErrors(['antispam_delay_max' => 'Delay maksimal harus ≥ delay minimal.']);
        }

        try {
            $validated['send_window_start'] = Carbon::parse($validated['send_window_start'])->format('H:i');
            $validated['send_window_end'] = Carbon::parse($validated['send_window_end'])->format('H:i');
        } catch (\Throwable) {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->withErrors(['send_window_start' => 'Format jam mulai / selesai tidak valid.']);
        }

        $winStart = $validated['send_window_start'];
        $winEnd = $validated['send_window_end'];
        if ($winStart === $winEnd) {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->withErrors(['send_window_end' => 'Jam akhir harus berbeda dari jam mulai.']);
        }

        $parseJsonField = function (?string $raw): ?array {
            if ($raw === null || trim($raw) === '') {
                return null;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return null;
            }

            return $decoded;
        };

        $greetings = $parseJsonField($validated['template_greetings_json'] ?? null);
        $closings = $parseJsonField($validated['template_closings_json'] ?? null);
        if (($validated['template_greetings_json'] ?? '') !== '' && $greetings === null) {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->withErrors(['template_greetings_json' => 'Format JSON tidak valid (harus array JSON).']);
        }
        if (($validated['template_closings_json'] ?? '') !== '' && $closings === null) {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->withErrors(['template_closings_json' => 'Format JSON tidak valid (harus array JSON).']);
        }

        $willBeEnabled = (string) ($validated['enabled'] ?? '0') === '1';

        $token = trim((string) ($validated['api_token'] ?? ''));
        if ($request->boolean('api_token_clear')) {
            $fonnte->api_token = null;
        } elseif ($token !== '') {
            $fonnte->api_token = $token;
        }

        $effectiveToken = $fonnte->api_token;
        if ($willBeEnabled && blank($effectiveToken)) {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->with('error', 'Untuk mengaktifkan Fonnte, isi API token dari dashboard Fonnte (atau hapus centang aktif).');
        }

        $fonnte->enabled = (string) ($validated['enabled'] ?? '0') === '1';
        $fonnte->sender_name = $validated['sender_name'] ?? null;
        $fonnte->auto_send_h3 = (string) ($validated['auto_send_h3'] ?? '0') === '1';
        $fonnte->auto_send_h0 = (string) ($validated['auto_send_h0'] ?? '0') === '1';
        $fonnte->auto_send_overdue = (string) ($validated['auto_send_overdue'] ?? '0') === '1';
        $fonnte->send_window_start = $validated['send_window_start'];
        $fonnte->send_window_end = $validated['send_window_end'];
        $fonnte->antispam_enabled = (string) ($validated['antispam_enabled'] ?? '0') === '1';
        $fonnte->antispam_delay_min = (int) $validated['antispam_delay_min'];
        $fonnte->antispam_delay_max = (int) $validated['antispam_delay_max'];
        $fonnte->antispam_hourly_limit = (int) $validated['antispam_hourly_limit'];
        $fonnte->antispam_daily_limit = (int) $validated['antispam_daily_limit'];
        $fonnte->antispam_randomize_message = (string) ($validated['antispam_randomize_message'] ?? '0') === '1';
        $fonnte->template_greetings = $greetings;
        $fonnte->template_closings = $closings;
        $fonnte->template_reminder = $validated['template_reminder'] ?? null;
        $fonnte->template_due_today = $validated['template_due_today'] ?? null;
        $fonnte->template_janji_bayar = $validated['template_janji_bayar'] ?? null;
        $fonnte->template_overdue = $validated['template_overdue'] ?? null;
        $fonnte->save();

        return redirect()->route('sessions.fonnte')->with('success', 'Pengaturan Fonnte berhasil disimpan.');
    }

    /**
     * Kirim pesan test WhatsApp ke nomor tujuan untuk validasi konfigurasi Fonnte.
     */
    public function testFonnte(Request $request, FonnteApiService $fonnteApiService)
    {
        $user = $request->user();
        $fonnte = FonnteSetting::forUser($user);

        $validated = $request->validate([
            'test_target' => ['required', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
        ], [
            'test_target.regex' => 'Nomor WhatsApp test tidak valid. Gunakan format 08xx / 62xx / +62xx.',
        ]);

        $token = trim((string) ($fonnte->api_token ?? ''));
        if ($token === '') {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->with('error', 'API token Fonnte belum tersimpan. Simpan token dulu, lalu coba test kirim.');
        }

        $target = $this->normalizeIndonesiaWhatsapp($validated['test_target']);
        if ($target === null) {
            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->with('error', 'Nomor tujuan test tidak bisa diproses.');
        }

        $sender = trim((string) ($fonnte->sender_name ?: ($user->username ?? 'MIKPAY')));
        $message = "Halo, ini pesan test Fonnte dari {$sender}.\n"
            . 'Waktu: ' . now()->format('d M Y H:i:s') . "\n"
            . "Jika pesan ini masuk, konfigurasi API token Fonnte Anda sudah benar.";

        $result = $fonnteApiService->sendText($token, $target, $message);
        if (! ($result['ok'] ?? false)) {
            $status = isset($result['status']) ? ('HTTP ' . $result['status']) : 'tanpa status';
            $body = trim((string) ($result['body'] ?? ''));
            $shortBody = $body !== '' ? mb_strimwidth($body, 0, 180, '...') : 'tidak ada respons body';

            return redirect()->route('sessions.fonnte')
                ->withInput()
                ->with('error', "Gagal kirim test WhatsApp ({$status}). Detail: {$shortBody}");
        }

        return redirect()->route('sessions.fonnte')
            ->with('success', 'Test WhatsApp berhasil dikirim ke +' . $target . '.');
    }

    /**
     * Update username / password admin yang sedang login (Admin Settings → Admin Account).
     */
    public function updateAccount(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'username.unique' => 'Username ini sudah dipakai pengguna lain.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return redirect()->route('sessions')
                ->withErrors(['current_password' => 'Password saat ini tidak benar.'])
                ->withInput($request->except('current_password', 'password', 'password_confirmation'));
        }

        $user->username = $validated['username'];
        // Samakan name agar konsisten di seluruh UI yang memakai name
        $user->name = $validated['username'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('sessions')->with('success', 'Akun admin berhasil diperbarui.');
    }

    /**
     * Simpan konfigurasi Telegram bot per user (tanpa .env).
     */
    public function updateTelegram(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:64'],
            'telegram_router_session' => ['nullable', 'string', 'max:100'],
            'telegram_enabled' => ['nullable', 'in:0,1'],
            'telegram_notify_connection' => ['nullable', 'in:0,1'],
        ]);

        $token = trim((string) ($validated['telegram_bot_token'] ?? ''));
        $chatId = trim((string) ($validated['telegram_chat_id'] ?? ''));
        $routerSession = trim((string) ($validated['telegram_router_session'] ?? ''));
        $enabled = (string) ($validated['telegram_enabled'] ?? '0') === '1';
        $notifyConnection = (string) ($validated['telegram_notify_connection'] ?? '0') === '1';

        if ($enabled && ($token === '' || $chatId === '')) {
            return redirect()->route('sessions')
                ->with('error', 'Aktifkan Telegram butuh Bot Token dan Chat ID.');
        }

        if ($routerSession !== '') {
            $routerExists = MikpayRouter::where('user_id', auth()->id())->where('session_name', $routerSession)->exists();
            if (! $routerExists) {
                return redirect()->route('sessions')->with('error', 'Router Telegram tidak ditemukan.');
            }
        }

        $user->telegram_bot_token = $token !== '' ? $token : null;
        $user->telegram_chat_id = $chatId !== '' ? $chatId : null;
        $user->telegram_router_session = $routerSession !== '' ? $routerSession : null;
        $user->telegram_enabled = $enabled;
        $user->telegram_notify_connection = $notifyConnection;
        if (! $enabled) {
            $user->telegram_last_update_id = null;
        }
        $user->save();

        return redirect()->route('sessions')->with('success', 'Konfigurasi Telegram berhasil disimpan.');
    }

    /**
     * Kirim pesan test ke chat Telegram yang disimpan user.
     */
    public function testTelegram(Request $request, TelegramBotService $telegramBotService)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('sessions')->with('error', 'User tidak ditemukan.');
        }

        if (blank($user->telegram_bot_token) || blank($user->telegram_chat_id)) {
            $user->telegram_test_last_status = 'failed';
            $user->telegram_test_last_message = 'Bot Token / Chat ID kosong';
            $user->telegram_test_last_at = now();
            $user->save();
            return redirect()->route('sessions')
                ->with('error', 'Isi dan simpan Bot Token + Chat ID dulu sebelum test kirim.');
        }

        $ok = $telegramBotService->sendTestMessage($user);
        if (! $ok) {
            $user->telegram_test_last_status = 'failed';
            $user->telegram_test_last_message = 'Kirim ke Telegram API gagal';
            $user->telegram_test_last_at = now();
            $user->save();
            return redirect()->route('sessions')
                ->with('error', 'Gagal kirim test Telegram. Cek token, chat ID, dan apakah bot sudah pernah kamu chat.');
        }

        $user->telegram_test_last_status = 'success';
        $user->telegram_test_last_message = 'Pesan test berhasil terkirim';
        $user->telegram_test_last_at = now();
        $user->save();

        return redirect()->route('sessions')->with('success', 'Pesan test Telegram berhasil dikirim.');
    }

    /**
     * Connect ke router = test API, simpan session, redirect ke dashboard (sesuai template: id=connect lalu ?session=XXX).
     */
    public function connect(Request $request)
    {
        $request->validate([
            'session' => 'required|string|max:100',
        ]);

        $sessionName = $request->input('session');
        $router = MikpayRouter::where('user_id', auth()->id())->where('session_name', $sessionName)->first();

        if (! $router) {
            return redirect()->route('sessions')->with('error', 'Router tidak ditemukan.');
        }

        if ($router->status !== 'enable') {
            return redirect()->route('sessions')->with('error', 'Router dinonaktifkan.');
        }

        $mikrotik = new MikrotikService;
        if (! $mikrotik->connectRouter($router)) {
            $mikrotik->disconnect();
            return redirect()->route('sessions')->with('error', 'Tidak bisa connect ke router. Cek IP:Port, Username, Password, dan pastikan API MikroTik (port 8728) enabled.');
        }
        $mikrotik->disconnect();

        $request->session()->put('selected_router', $sessionName);

        return redirect()->route('dashboard');
    }

    /**
     * Disconnect / ganti router (kembali ke sessions).
     */
    public function disconnect(Request $request)
    {
        $request->session()->forget('selected_router');
        return redirect()->route('sessions');
    }
}
