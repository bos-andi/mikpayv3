<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Services\VoucherTemplateService;
use App\Support\TenantRouterScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    private function preferredSessionName(Request $request): string
    {
        return (string) ($request->session()->get('selected_router') ?: 'mikpay');
    }

    public function uploadLogo(Request $request): View|RedirectResponse
    {
        $sessionName = $this->preferredSessionName($request);

        $dir = public_path('mikpay/img');
        $files = [];
        if (File::isDirectory($dir)) {
            $files = collect(File::files($dir))
                ->filter(fn ($f) => Str::startsWith($f->getFilename(), 'logo-') && Str::endsWith($f->getFilename(), '.png'))
                ->map(fn ($f) => $f->getFilename())
                ->sort()
                ->values()
                ->all();
        }

        return view('admin.upload-logo', [
            'session_name' => $sessionName,
            'logo_files' => $files,
            'router_sessions' => TenantRouterScope::forUser((int) auth()->id())->orderBy('session_name')->pluck('session_name')->all(),
        ]);
    }

    public function uploadLogoStore(Request $request): RedirectResponse
    {
        $sessionName = $request->input('session_name') ?: $this->preferredSessionName($request);
        $sessionName = (string) $sessionName;

        $request->validate([
            'session_name' => 'nullable|string|max:100',
            'logo' => 'required|file|mimes:png|max:2048',
        ]);

        $allowedSessions = TenantRouterScope::forUser((int) auth()->id())->orderBy('session_name')->pluck('session_name')->all();
        if ($allowedSessions !== [] && ($sessionName === '' || ! in_array($sessionName, $allowedSessions, true))) {
            $sessionName = $allowedSessions[0];
        }

        $dir = public_path('mikpay/img');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $safeSession = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $sessionName) ?: 'router';
        $filename = 'logo-' . $safeSession . '.png';

        $request->file('logo')->move($dir, $filename);

        return redirect()->route('admin.upload-logo')->with('success', 'Logo berhasil diupload: ' . $filename);
    }

    public function uploadLogoDelete(Request $request, string $filename): RedirectResponse
    {
        if (! preg_match('/^logo-[a-zA-Z0-9_-]+\.png$/', $filename)) {
            return redirect()->route('admin.upload-logo')->with('error', 'Nama file tidak valid.');
        }

        $path = public_path('mikpay/img/' . $filename);
        if (File::exists($path)) {
            File::delete($path);
            return redirect()->route('admin.upload-logo')->with('success', 'Logo dihapus: ' . $filename);
        }

        return redirect()->route('admin.upload-logo')->with('error', 'File tidak ditemukan.');
    }

    public function templateEditor(Request $request, VoucherTemplateService $voucherTemplates): View|RedirectResponse
    {
        $userId = (int) auth()->id();

        $key = $request->get('template', 'default');
        if (! $voucherTemplates->isValidKey($key)) {
            $key = 'default';
        }

        $userPath = $voucherTemplates->storagePath($key, $userId);
        $legacyPath = $voucherTemplates->legacyStoragePath($key);
        $basename = $voucherTemplates->storageBasename($key);
        if (File::exists($userPath)) {
            $fileLabel = 'user-' . $userId . '/' . $basename . ' (override akun Anda)';
        } elseif (File::exists($legacyPath)) {
            $fileLabel = $basename . ' (override global lama — klik Simpan untuk memindah ke akun ini)';
        } else {
            $fileLabel = $basename . ' — bawaan resources/views/voucher/';
        }

        $content = $voucherTemplates->getEditorContent($key, $userId);

        $variablesPath = resource_path('data/voucher-template-variables.txt');
        $variables = File::exists($variablesPath) ? File::get($variablesPath) : '';

        return view('admin.template-editor', [
            'template_key' => $key,
            'template_file' => $fileLabel,
            'template_content' => $content,
            'variable_content' => $variables,
            'template_user_id' => $userId,
        ]);
    }

    public function templateEditorSave(Request $request, VoucherTemplateService $voucherTemplates): RedirectResponse
    {
        $request->validate([
            'template' => 'required|string|in:default,thermal,small',
            'content' => 'required|string',
        ]);

        $key = $request->input('template');
        $userId = (int) $request->user()->id;
        $voucherTemplates->save($key, $request->input('content'), $userId);

        $rel = 'storage/app/voucher-templates/user-' . $userId . '/' . $voucherTemplates->storageBasename($key);

        return redirect()
            ->route('admin.template-editor', ['template' => $key])
            ->with('success', 'Template Blade disimpan untuk akun Anda: ' . $rel);
    }

    public function templateEditorPreview(Request $request): JsonResponse
    {
        $request->validate([
            'template' => 'required|string|in:default,thermal,small',
            'content' => 'required|string|max:500000',
        ]);

        $templateKey = $request->input('template');
        $content = $request->input('content');

        // Variabel preview (sama seperti cetak voucher) untuk Blade::render
        $hotspotname = 'MIKPAY HOTSPOT';
        $username = 'mikpay';
        $password = '1234';
        $validity = '1d';
        $timelimit = '6h';
        $getdatalimit = '1073741824';
        $datalimit = '1 GB';
        $getsprice = '5000';
        $currency = 'Rp';
        $price = $currency . ' ' . number_format((float) $getsprice, 0, ',', '.');
        $profile = '6Jam';
        $comment = 'Preview';
        $dnsname = 'an.net';
        $usermode = 'up';
        $num = 1;
        $v_opsi = 'down';

        // Logo: pakai aset Laravel (logo session atau fallback) supaya mirip print voucher
        $session_name = (string) ($request->session()->get('selected_router') ?: 'preview-router');
        $logoPath = public_path('mikpay/img');
        $logo_default = asset('mikpay/img/logo.png');
        $logo_session = $logo_default;
        $logo_file = 'logo.png';
        $safeSession = preg_replace('/[^a-zA-Z0-9_-]/', '', $session_name) ?: 'router';
        $sessionLogoFile = 'logo-' . $safeSession . '.png';
        if (is_dir($logoPath) && File::exists($logoPath . DIRECTORY_SEPARATOR . $sessionLogoFile)) {
            $logo_session = asset('mikpay/img/' . $sessionLogoFile);
            $logo_file = $sessionLogoFile;
        } elseif (is_dir($logoPath)) {
            $logos = glob($logoPath . DIRECTORY_SEPARATOR . 'logo-*.png');
            if (! empty($logos)) {
                $logo_session = asset('mikpay/img/' . basename($logos[0]));
                $logo_file = basename($logos[0]);
            }
        }

        $urilogin = 'http://' . $dnsname . '/login?username=' . $username . '&password=' . $password;
        $qrcode = '
	<canvas class="qrcode" id="qr"></canvas>
	<script>
	  (function() {
	    var qr = new QRious({
	      element: document.getElementById("qr"),
	      value: "' . addslashes($urilogin) . '",
	      size: 256
	    });
	  })();
	</script>
	';

        // $logo dipakai untuk "logo aplikasi" (fixed default), sedangkan $logo_session untuk logo voucher per router/session.
        $logo = $logo_default;

        $previewData = [
            'hotspotname' => $hotspotname,
            'username' => $username,
            'password' => $password,
            'validity' => $validity,
            'timelimit' => $timelimit,
            'getdatalimit' => $getdatalimit,
            'datalimit' => $datalimit,
            'getsprice' => (string) $getsprice,
            'getprice' => (string) $getsprice,
            'profile' => $profile,
            'comment' => $comment,
            'dnsname' => $dnsname,
            'num' => $num,
            'usermode' => $usermode,
            'v_opsi' => $v_opsi,
            'urilogin' => $urilogin,
            'qrcode' => $qrcode,
            'logo' => $logo,
            'logo_session' => $logo_session,
            'logo_default' => $logo_default,
            'logo_file' => $logo_file,
            'session_name' => $session_name,
            'price' => $price,
            'currency' => $currency,
        ];

        try {
            $html = BladeCompiler::render($content, $previewData);
        } catch (\Throwable $e) {
            $html = '<div style="color:#fff;background:#ef4444;padding:12px;border-radius:10px;font-family:ui-monospace,monospace;">Preview gagal: ' . e($e->getMessage()) . '</div>';
        }

        // Dokumen HTML penuh seperti vpreview.php: DOCTYPE, head (style + QRious), body
        // Supaya script QR jalan dan tampilan sama dengan template utama.
        $qriousScript = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
        $wrappedHtml = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Preview Template - ' . e($templateKey) . '</title>
	<script src="' . e($qriousScript) . '"></script>
	<style>
body { color: #000000; background-color: #FFFFFF; font-size: 14px; font-family: Helvetica, arial, sans-serif; margin: 0; -webkit-print-color-adjust: exact; }
table.voucher { display: inline-block; border: 2px solid black; margin: 2px; }
.qrc { width:30px; height:30px; margin-top:1px; }
.qrcode { height: 80px; width: 80px; }
@page { size: auto; margin: 9mm 3mm; }
@media print { table { page-break-after: auto; } tr { page-break-inside: avoid; } td { page-break-inside: avoid; } }
#num { float: right; display: inline-block; }
	</style>
</head>
<body>
' . $html . '
</body>
</html>';

        return response()->json([
            'template' => $templateKey,
            'html' => $wrappedHtml,
        ]);
    }

    /**
     * Simpan tanggal mulai pencatatan tagihan (kolom billing_tracking_start_date di business_settings).
     */
    public function updateBusinessSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'billing_tracking_start_date' => 'nullable|date',
            'letterhead_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:4096',
            'letterhead_remove' => 'nullable|in:0,1',
        ]);

        $settings = BusinessSetting::get();
        $settings->billing_tracking_start_date = $request->filled('billing_tracking_start_date')
            ? $request->date('billing_tracking_start_date')
            : null;

        $imgDir = public_path('mikpay/img');
        if (! File::isDirectory($imgDir)) {
            File::makeDirectory($imgDir, 0755, true);
        }

        $removeLetterhead = (string) $request->input('letterhead_remove', '0') === '1';
        if ($removeLetterhead) {
            $old = trim((string) ($settings->letterhead_image ?? ''));
            if ($old !== '' && File::exists($imgDir . DIRECTORY_SEPARATOR . $old)) {
                File::delete($imgDir . DIRECTORY_SEPARATOR . $old);
            }
            $settings->letterhead_image = null;
        }

        if ($request->hasFile('letterhead_file')) {
            $old = trim((string) ($settings->letterhead_image ?? ''));
            if ($old !== '' && File::exists($imgDir . DIRECTORY_SEPARATOR . $old)) {
                File::delete($imgDir . DIRECTORY_SEPARATOR . $old);
            }

            $ext = strtolower((string) $request->file('letterhead_file')->getClientOriginalExtension());
            if ($ext === '') {
                $ext = 'png';
            }
            $filename = 'letterhead-' . date('YmdHis') . '.' . $ext;
            $request->file('letterhead_file')->move($imgDir, $filename);
            $settings->letterhead_image = $filename;
        }

        $settings->save();

        return redirect()->route('sessions')->with('success', 'Pengaturan berhasil disimpan.');
    }
}

