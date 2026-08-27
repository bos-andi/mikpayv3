<?php

namespace App\Http\Controllers;

use App\Helpers\MikrotikFormat;
use App\Models\Router;
use App\Services\MikrotikService;
use App\Services\VoucherTemplateService;
use App\Support\ActiveRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\View;

class HotspotController extends Controller
{
    public function index(Request $request, MikrotikService $mikrotik, VoucherTemplateService $voucherTemplates): View|RedirectResponse
    {
        $section = (string) $request->query('section', 'users');
        if (! in_array($section, ['users', 'generate', 'profiles', 'active', 'report', 'template'], true)) {
            $section = 'users';
        }

        $filters = [
            'profile' => (string) $request->query('profile', 'all'),
            'comment' => (string) $request->query('comment', ''),
            'exp' => $request->boolean('exp'),
        ];

        $users = [];
        $profiles = [];
        $servers = [];
        $pools = [];
        $queues = [];
        $active = [];
        $sellingRows = [];
        $sellingTotal = 0;
        $sellingOwner = '';
        $commentOptions = [];
        $fetchOk = true;
        $fetchMessage = '';
        $templateKey = 'default';
        $templateFile = '';
        $templateContent = '';
        $variableContent = '';
        $templateTenantId = (int) (current_tenant()?->id ?? 0);

        if ($section === 'template') {
            $router = ActiveRouter::current();
            $templateKey = (string) $request->query('template', 'default');
            if (! $voucherTemplates->isValidKey($templateKey)) {
                $templateKey = 'default';
            }
            $tenantPath = $voucherTemplates->storagePath($templateKey, max(1, $templateTenantId));
            $legacyPath = $voucherTemplates->legacyStoragePath($templateKey);
            $basename = $voucherTemplates->storageBasename($templateKey);
            if ($templateTenantId > 0 && File::exists($tenantPath)) {
                $templateFile = 'tenant-'.$templateTenantId.'/'.$basename.' (override tenant)';
            } elseif (File::exists($legacyPath)) {
                $templateFile = $basename.' (override global)';
            } else {
                $templateFile = $basename.' — bawaan resources/views/voucher/';
            }
            $templateContent = $voucherTemplates->getEditorContent($templateKey, max(1, $templateTenantId));
            $variablesPath = resource_path('data/voucher-template-variables.txt');
            $variableContent = File::exists($variablesPath) ? File::get($variablesPath) : '';

            return view('panel.vouchers', [
                'router' => $router,
                'section' => $section,
                'filters' => $filters,
                'users' => $users,
                'profiles' => $profiles,
                'servers' => $servers,
                'pools' => $pools,
                'queues' => $queues,
                'active' => $active,
                'sellingRows' => $sellingRows,
                'sellingTotal' => $sellingTotal,
                'sellingOwner' => $sellingOwner,
                'commentOptions' => $commentOptions,
                'fetchOk' => true,
                'fetchMessage' => '',
                'lastGenerate' => session('last_generate_voucher'),
                'templateKey' => $templateKey,
                'templateFile' => $templateFile,
                'templateContent' => $templateContent,
                'variableContent' => $variableContent,
                'templateTenantId' => $templateTenantId,
            ]);
        }

        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        if ($section === 'users' || $section === 'generate') {
            $allUsers = $mikrotik->fetchHotspotUsers($router);
            $result = $mikrotik->fetchHotspotUsers($router, array_filter([
                'profile' => $filters['profile'] !== 'all' ? $filters['profile'] : null,
                'comment' => $filters['comment'] !== '' ? $filters['comment'] : null,
                'exp' => $filters['exp'] ? '1' : null,
            ], fn ($v) => $v !== null && $v !== '' && $v !== false));
            $fetchOk = $result['ok'];
            $fetchMessage = $result['message'];
            $users = $result['users'];
            $profiles = $allUsers['ok'] ? $allUsers['profiles'] : $result['profiles'];
            $servers = $allUsers['ok'] ? $allUsers['servers'] : $result['servers'];

            $counts = [];
            foreach (($allUsers['ok'] ? $allUsers['users'] : $users) as $u) {
                $c = trim((string) ($u['comment'] ?? ''));
                if ($c === '') {
                    continue;
                }
                $counts[$c] = ($counts[$c] ?? 0) + 1;
            }
            arsort($counts);
            $commentOptions = $counts;
        } elseif ($section === 'profiles') {
            $result = $mikrotik->fetchHotspotProfiles($router);
            $fetchOk = $result['ok'];
            $fetchMessage = $result['message'];
            $profiles = $result['profiles'];
            $pools = $result['pools'];
            $queues = $result['queues'];
        } elseif ($section === 'active') {
            $result = $mikrotik->fetchHotspotActive($router);
            $fetchOk = $result['ok'];
            $fetchMessage = $result['message'];
            $active = $result['active'];
        } else {
            $owner = (string) $request->query('owner', '');
            $result = $mikrotik->fetchHotspotSelling($router, $owner !== '' ? $owner : null);
            $fetchOk = $result['ok'];
            $fetchMessage = $result['message'];
            $sellingRows = $result['rows'];
            $sellingTotal = (int) $result['total'];
            $sellingOwner = (string) ($result['owner'] ?? '');
        }

        return view('panel.vouchers', [
            'router' => $router,
            'section' => $section,
            'filters' => $filters,
            'users' => $users,
            'profiles' => $profiles,
            'servers' => $servers,
            'pools' => $pools,
            'queues' => $queues,
            'active' => $active,
            'sellingRows' => $sellingRows,
            'sellingTotal' => $sellingTotal,
            'sellingOwner' => $sellingOwner,
            'commentOptions' => $commentOptions,
            'fetchOk' => $fetchOk,
            'fetchMessage' => $fetchMessage,
            'lastGenerate' => session('last_generate_voucher'),
            'templateKey' => $templateKey,
            'templateFile' => $templateFile,
            'templateContent' => $templateContent,
            'variableContent' => $variableContent,
            'templateTenantId' => $templateTenantId,
        ]);
    }

    public function storeGenerate(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:500'],
            'server' => ['nullable', 'string', 'max:100'],
            'user' => ['required', 'in:up,vc'],
            'userl' => ['required', 'integer', 'in:3,4,5,6,7,8'],
            'prefix' => ['nullable', 'string', 'max:6'],
            'char' => ['required', 'in:lower,upper,upplow,num,mix,mix1,mix2'],
            'profile' => ['required', 'string', 'max:128'],
            'timelimit' => ['nullable', 'string', 'max:32'],
            'datalimit' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'adcomment' => ['nullable', 'string', 'max:64'],
            'mbgb' => ['required', 'in:1048576,1073741824'],
        ]);

        $qty = (int) $data['qty'];
        $server = trim((string) ($data['server'] ?? ''));
        if ($server === 'all') {
            $server = '';
        }
        $userMode = (string) $data['user'];
        $profile = (string) $data['profile'];
        $userLength = (int) $data['userl'];
        $prefix = trim((string) ($data['prefix'] ?? ''));
        $char = (string) $data['char'];
        $adComment = trim((string) ($data['adcomment'] ?? ''));
        $timelimit = trim((string) ($data['timelimit'] ?? ''));
        $datalimit = $data['datalimit'] ?? null;
        $mbgb = (int) $data['mbgb'];

        $commentFinal = trim('vc-'.random_int(100, 999).'-'.now()->format('m.d.y').'-'.$adComment, '-');
        $limitBytes = null;
        if ($datalimit !== null && (float) $datalimit > 0) {
            $limitBytes = (int) ((float) $datalimit * $mbgb);
        }

        $batch = [];
        $lastCode = null;
        for ($i = 0; $i < $qty; $i++) {
            [$name, $password] = $this->generateCredential($userMode, $userLength, $char, $prefix);
            $attempt = 0;
            while ($mikrotik->hotspotUserExists($router, $name) && $attempt < 30) {
                [$name, $password] = $this->generateCredential($userMode, $userLength, $char, $prefix);
                $attempt++;
            }
            $lastCode = $name;
            $params = [
                'name' => $name,
                'password' => $password,
                'profile' => $profile,
                'disabled' => 'no',
                'comment' => $commentFinal,
            ];
            if ($server !== '') {
                $params['server'] = $server;
            }
            if ($timelimit !== '' && $timelimit !== '0') {
                $params['limit-uptime'] = $timelimit;
            }
            if ($limitBytes) {
                $params['limit-bytes-total'] = (string) $limitBytes;
            }
            $batch[] = $params;
        }

        // Reconnect clean for batch (hotspotUserExists may have left connection)
        $mikrotik->disconnect();
        $result = $mikrotik->addHotspotUsersBatch($router, $batch);

        if (! $result['ok']) {
            return redirect()
                ->to($this->vouchersUrl('generate'))
                ->with('error', $result['message']);
        }

        $request->session()->flash('last_generate_voucher', [
            'code' => $lastCode,
            'date' => now()->format('d M Y H:i'),
            'profile' => $profile,
            'comment' => $commentFinal,
            'qty' => $result['created'],
        ]);

        return redirect()
            ->to($this->vouchersUrl('users', ['comment' => $commentFinal, 'profile' => $profile]))
            ->with('success', $result['message'].' Batch: '.$commentFinal);
    }

    public function storeUser(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $data = $request->validate([
            'server' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:64'],
            'pass' => ['required', 'string', 'max:64'],
            'profile' => ['required', 'string', 'max:128'],
            'timelimit' => ['nullable', 'string', 'max:32'],
            'datalimit' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'mbgb' => ['nullable', 'in:1048576,1073741824'],
            'comment' => ['nullable', 'string', 'max:64'],
        ]);

        $name = trim((string) $data['name']);
        $password = trim((string) $data['pass']);
        $mode = $name === $password ? 'vc-' : 'up-';
        $params = [
            'name' => $name,
            'password' => $password,
            'profile' => (string) $data['profile'],
            'disabled' => 'no',
            'comment' => $mode.trim((string) ($data['comment'] ?? '')),
        ];
        $server = trim((string) ($data['server'] ?? ''));
        if ($server !== '' && $server !== 'all') {
            $params['server'] = $server;
        }
        $timelimit = trim((string) ($data['timelimit'] ?? ''));
        if ($timelimit !== '' && $timelimit !== '0') {
            $params['limit-uptime'] = $timelimit;
        }
        $datalimit = $data['datalimit'] ?? null;
        $mbgb = (int) ($data['mbgb'] ?? 1048576);
        if ($datalimit !== null && (float) $datalimit > 0) {
            $params['limit-bytes-total'] = (string) ((int) ((float) $datalimit * $mbgb));
        }

        $result = $mikrotik->addHotspotUser($router, $params);

        return redirect()
            ->to($this->vouchersUrl('users', ['profile' => $params['profile'], 'comment' => $params['comment']]))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function storeProfile(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'ppool' => ['nullable', 'string', 'max:128'],
            'sharedusers' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'ratelimit' => ['nullable', 'string', 'max:128'],
            'expmode' => ['required', 'in:0,rem,ntf,remc,ntfc'],
            'validity' => ['nullable', 'string', 'max:64'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sprice' => ['nullable', 'numeric', 'min:0'],
            'lockunlock' => ['required', 'in:Enable,Disable'],
            'parent' => ['nullable', 'string', 'max:128'],
        ]);

        $name = preg_replace('/\s+/', '-', (string) $data['name']) ?: 'profile';
        $addrpool = (string) ($data['ppool'] ?? 'none') ?: 'none';
        $sharedusers = (string) ($data['sharedusers'] ?? 1);
        $ratelimit = (string) ($data['ratelimit'] ?? '');
        $expmode = (string) $data['expmode'];
        $validity = (string) ($data['validity'] ?? '');
        $price = (string) (($data['price'] ?? '') ?: '0');
        $sprice = (string) (($data['sprice'] ?? '') ?: '0');
        $getlock = (string) $data['lockunlock'];
        $parent = (string) (($data['parent'] ?? '') ?: 'none');

        $lock = $getlock === 'Enable'
            ? '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]'
            : '';

        $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-'
            .$price
            .'-|-$address-|-$mac-|-'.$validity.'-|-'.$name.'-|-$comment" owner="$month$year" source="$date" comment="mikpay"';

        $onlogin = ':put (",'.$expmode.','.$price.','.$validity.','.$sprice.',,'.$getlock.',"); {:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ]; /sys sch add name="$user" disable=no start-date=$date interval="'.$validity.'"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];};:delay 5s; /sys sch remove [find where name="$user"]';

        $mode = '';
        if ($expmode === 'rem') {
            $onlogin .= $lock.'}}';
            $mode = 'remove';
        } elseif ($expmode === 'ntf') {
            $onlogin .= $lock.'}}';
            $mode = 'set limit-uptime=1s';
        } elseif ($expmode === 'remc') {
            $onlogin .= $record.$lock.'}}';
            $mode = 'remove';
        } elseif ($expmode === 'ntfc') {
            $onlogin .= $record.$lock.'}}';
            $mode = 'set limit-uptime=1s';
        } elseif ($expmode === '0') {
            $onlogin = ($price !== '0' && $price !== '') ? ':put (",,'.$price.',,,noexp,'.$getlock.',")'.$lock : '';
            $mode = '';
        }

        $bgservice = ':local dateint do={:local montharray ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );:local days [ :pick $d 4 6 ]; :local month [ :pick $d 0 3 ]; :local year [ :pick $d 7 11 ]; :local monthint ([ :find $montharray $month]); :local month ($monthint + 1); :if ( [len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="'.$name.'" ] do={ :local comment [ /ip hotspot user get $i comment]; :local name [ /ip hotspot user get $i name]; :local gettime [:pic $comment 12 20]; :if ([:pic $comment 3] = "/" and [:pic $comment 6] = "/") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user '.$mode.' $i ]; [ /ip hotspot active remove [find where user=$name] ];}}}';

        $profile = [
            'name' => $name,
            'address-pool' => $addrpool,
            'rate-limit' => $ratelimit,
            'shared-users' => $sharedusers,
            'status-autorefresh' => '1m',
            'on-login' => $onlogin,
            'parent-queue' => $parent,
        ];

        $monitor = $expmode !== '0' ? [
            'scheduler_name' => $name,
            'scheduler_event' => $bgservice,
        ] : null;

        $result = $mikrotik->addHotspotProfile($router, $profile, $monitor);

        return redirect()
            ->to($this->vouchersUrl('profiles'))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function userAction(Request $request, string $id, string $action, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $result = $mikrotik->hotspotUserAction($router, $action, $id);

        return redirect()
            ->to($this->vouchersUrl('users', $request->only(['profile', 'comment', 'exp'])))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function removeByComment(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $comment = trim((string) $request->input('comment', ''));
        if ($comment === '') {
            return redirect()->to($this->vouchersUrl('users'))->with('error', 'Comment wajib.');
        }

        $result = $mikrotik->removeHotspotUsers($router, ['?comment' => $comment]);

        return redirect()
            ->to($this->vouchersUrl('users'))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function removeExpired(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $result = $mikrotik->removeHotspotUsers($router, ['?limit-uptime' => '1s']);

        return redirect()
            ->to($this->vouchersUrl('users'))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function print(Request $request, MikrotikService $mikrotik, VoucherTemplateService $voucherTemplates): Response|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $comment = (string) ($request->query('id') ?: $request->query('comment', ''));
        $userParam = (string) $request->query('user', '');
        $qr = $request->query('qr', 'no') === 'yes';
        $small = $request->query('small', 'no') === 'yes';
        $tpl = (string) $request->query('tpl', '');
        $templateKey = $small ? 'small' : ($voucherTemplates->isValidKey($tpl) ? $tpl : 'default');

        $users = [];
        $profilesByName = [];
        $identity = $router->router_name ?: $router->name ?: 'MIKPAY HOTSPOT';

        if ($userParam !== '') {
            $name = $userParam;
            if (preg_match('/^(vc|up)-(.+)$/i', $userParam, $m)) {
                $name = $m[2];
            }
            $result = $mikrotik->fetchHotspotUsers($router);
            $users = array_values(array_filter($result['users'], fn ($u) => ($u['name'] ?? '') === $name));
            foreach ($result['profiles'] as $p) {
                $profilesByName[(string) ($p['name'] ?? '')] = $p;
            }
            if ($result['ok'] === false) {
                return response('<p>'.e($result['message'] ?: 'Gagal ambil data voucher.').'</p>', 502)
                    ->header('Content-Type', 'text/html; charset=UTF-8');
            }
        } elseif ($comment !== '') {
            $result = $mikrotik->fetchHotspotUsers($router, ['comment' => $comment]);
            $users = $result['users'];
            foreach ($result['profiles'] as $p) {
                $profilesByName[(string) ($p['name'] ?? '')] = $p;
            }
            if (count($users) === 0 && preg_match('/^(vc|up)-(.+)$/i', $comment, $m)) {
                $byName = $mikrotik->fetchHotspotUsers($router);
                $users = array_values(array_filter($byName['users'], fn ($u) => ($u['name'] ?? '') === $m[2]));
                foreach ($byName['profiles'] as $p) {
                    $profilesByName[(string) ($p['name'] ?? '')] = $p;
                }
            }
        } else {
            return response('<p style="padding:16px;">Pilih batch comment atau user untuk dicetak.</p>', 400)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $tenant = current_tenant();
        $tenantId = max(1, (int) ($tenant?->id ?? 0));
        $bundledPath = $voucherTemplates->bundledViewFilePath($templateKey);
        if ($voucherTemplates->resolveStoragePath($templateKey, $tenantId) === null && ! File::exists($bundledPath)) {
            return response('<p>Template voucher tidak ditemukan: '.e($templateKey).'</p>', 404)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $dnsname = trim((string) ($router->domain ?: ''));
        if ($dnsname === '') {
            $dnsname = explode(':', (string) $router->host)[0] ?: 'hotspot.local';
        }
        $hotspotname = $identity;
        $currency = trim((string) ($router->currency ?: 'Rp')) ?: 'Rp';
        $logoDefault = asset('logo-mikpay.png');
        $logoSession = $logoDefault;
        $logoFile = 'logo-mikpay.png';
        $logoPath = public_path('mikpay/img');
        $safeRouter = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($router->name ?: 'router')) ?: 'router';
        $sessionLogoFile = 'logo-'.$safeRouter.'.png';
        if (is_dir($logoPath) && File::exists($logoPath.DIRECTORY_SEPARATOR.$sessionLogoFile)) {
            $logoSession = asset('mikpay/img/'.$sessionLogoFile);
            $logoFile = $sessionLogoFile;
        } elseif (File::exists(public_path('logo-mikpay.png'))) {
            $logoSession = $logoDefault;
        }
        $logo = $logoDefault.'?v='.time();

        // QR always rendered in template via $qrcode; hide when qr=no by empty canvas size style in wrapper
        $voucherHtmlParts = [];
        $num = 0;
        foreach ($users as $u) {
            $num++;
            $username = (string) ($u['name'] ?? '-');
            $password = (string) ($u['password'] ?? '');
            if ($password === '') {
                $password = $username;
            }
            $profileName = (string) ($u['profile'] ?? '');
            $limitUptime = (string) ($u['limit-uptime'] ?? '');
            $limitBytes = isset($u['limit-bytes-total']) ? (int) $u['limit-bytes-total'] : 0;
            $meta = $this->parseOnLoginMeta((string) ($profilesByName[$profileName]['on-login'] ?? ''));
            $validityRaw = $meta['validity'] !== '' ? $meta['validity'] : '1d';
            $timelimitRaw = $limitUptime !== '' ? $limitUptime : '6h';
            $getprice = (string) ($meta['price'] ?: 0);
            $getsprice = (string) (($meta['sprice'] ?: $meta['price']) ?: 0);
            $price = $currency.' '.number_format((float) $getsprice, 0, ',', '.');
            $datalimit = $limitBytes > 0 ? MikrotikFormat::formatBytes($limitBytes, 2) : '';

            if ($small) {
                $validity = $validityRaw;
                $timelimit = $timelimitRaw;
                if (str_ends_with($validityRaw, 'd')) {
                    $validity = 'MASA AKTIF : '.substr($validityRaw, 0, -1).' Hari';
                } elseif (str_ends_with($validityRaw, 'h')) {
                    $validity = 'MASA AKTIF : '.substr($validityRaw, 0, -1).' Jam';
                }
                if (str_ends_with($timelimitRaw, 'h')) {
                    $timelimit = 'Durasi:'.substr($timelimitRaw, 0, -1).' Jam';
                } elseif (str_ends_with($timelimitRaw, 'd')) {
                    $timelimit = 'Durasi:'.substr($timelimitRaw, 0, -1).' HARI';
                } else {
                    $timelimit = 'Durasi:'.$timelimitRaw;
                }
            } else {
                $validity = $validityRaw;
                $timelimit = $timelimitRaw;
            }

            $usermode = ($password === $username) ? 'vc' : 'up';
            $urilogin = 'http://'.$dnsname.'/login?username='.rawurlencode($username).'&password='.rawurlencode($password);
            $qrId = 'qr-'.$num;
            $qrcode = $qr
                ? '<canvas class="qrcode" id="'.e($qrId).'"></canvas><script>(function(){new QRious({element:document.getElementById("'.e($qrId).'"),value:'.json_encode($urilogin).',size:256});})();</script>'
                : '<span class="qrcode" style="display:inline-block;width:45px;height:45px;"></span>';

            try {
                $voucherHtmlParts[] = $voucherTemplates->render($templateKey, [
                    'username' => $username,
                    'password' => $password,
                    'validity' => $validity,
                    'timelimit' => $timelimit,
                    'getdatalimit' => $limitBytes,
                    'datalimit' => $datalimit,
                    'getsprice' => $getsprice,
                    'getprice' => $getprice,
                    'profile' => $profileName,
                    'comment' => (string) ($u['comment'] ?? ''),
                    'hotspotname' => $hotspotname,
                    'num' => $num,
                    'usermode' => $usermode,
                    'v_opsi' => 'down',
                    'urilogin' => $urilogin,
                    'qrcode' => $qrcode,
                    'logo' => $logo,
                    'logo_session' => $logoSession,
                    'logo_default' => $logoDefault,
                    'logo_file' => $logoFile,
                    'session_name' => (string) ($router->name ?? 'router'),
                    'price' => $price,
                    'currency' => $currency,
                    'dnsname' => $dnsname,
                ], $tenantId);
            } catch (\Throwable $e) {
                report($e);
                $voucherHtmlParts[] = '<div style="padding:8px;border:1px solid #ccc;">Error: '.e($e->getMessage()).'</div>';
            }
        }

        $bodyHtml = implode('', $voucherHtmlParts);
        if ($bodyHtml === '') {
            $bodyHtml = '<p style="padding:16px;">Tidak ada data voucher untuk dicetak.</p>';
        }

        $qriousScript = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
        $fullHtml = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Voucher Print</title>
	<script src="'.e($qriousScript).'"></script>
	<style>
body { color: #000; background: #fff; font-size: 14px; font-family: Helvetica, Arial, sans-serif; margin: 0; -webkit-print-color-adjust: exact; }
table.voucher { display: inline-block; border: 2px solid black; margin: 2px; }
.qrcode { height: 45px; width: 45px; }
@page { size: auto; margin: 9mm 3mm; }
@media print { table { page-break-inside: avoid; } tr { page-break-inside: avoid; } td { page-break-inside: avoid; } }
#num { float: right; display: inline-block; }
	</style>
</head>
<body onload="window.print()">
'.$bodyHtml.'
</body>
</html>';

        return response($fullHtml)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function saveTemplate(Request $request, VoucherTemplateService $voucherTemplates): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            return redirect()->to($this->vouchersUrl('template'))->with('error', 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'template' => ['required', 'string', 'in:default,thermal,small'],
            'content' => ['required', 'string'],
        ]);

        $key = (string) $data['template'];
        $voucherTemplates->save($key, (string) $data['content'], (int) $tenant->id);

        return redirect()
            ->to($this->vouchersUrl('template', ['template' => $key]))
            ->with('success', 'Template disimpan: tenant-'.$tenant->id.'/'.$voucherTemplates->storageBasename($key));
    }

    public function previewTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template' => ['required', 'string', 'in:default,thermal,small'],
            'content' => ['required', 'string', 'max:500000'],
        ]);

        $templateKey = (string) $request->input('template');
        $content = (string) $request->input('content');
        $router = ActiveRouter::current();
        $tenant = current_tenant();

        $username = 'mikpay';
        $password = '1234';
        $getsprice = '5000';
        $currency = trim((string) ($router?->currency ?: 'Rp')) ?: 'Rp';
        $price = $currency.' '.number_format((float) $getsprice, 0, ',', '.');
        $dnsname = trim((string) ($router?->domain ?: '')) ?: 'hotspot.local';
        $hotspotname = (string) ($router?->router_name ?: $router?->name ?: ($tenant?->name ?: 'MIKPAY HOTSPOT'));
        $logoDefault = asset('logo-mikpay.png');
        $logoSession = $logoDefault;
        $urilogin = 'http://'.$dnsname.'/login?username='.$username.'&password='.$password;
        $qrcode = '<canvas class="qrcode" id="qr"></canvas><script>(function(){new QRious({element:document.getElementById("qr"),value:'.json_encode($urilogin).',size:256});})();</script>';

        $previewData = [
            'hotspotname' => $hotspotname,
            'username' => $username,
            'password' => $password,
            'validity' => '1d',
            'timelimit' => '6h',
            'getdatalimit' => 1073741824,
            'datalimit' => '1 GiB',
            'getsprice' => $getsprice,
            'getprice' => $getsprice,
            'profile' => '6Jam',
            'comment' => 'Preview',
            'dnsname' => $dnsname,
            'num' => 1,
            'usermode' => 'up',
            'v_opsi' => 'down',
            'urilogin' => $urilogin,
            'qrcode' => $qrcode,
            'logo' => $logoDefault,
            'logo_session' => $logoSession,
            'logo_default' => $logoDefault,
            'logo_file' => 'logo-mikpay.png',
            'session_name' => (string) ($router?->name ?: 'preview'),
            'price' => $price,
            'currency' => $currency,
        ];

        try {
            $html = BladeCompiler::render($content, $previewData);
        } catch (\Throwable $e) {
            $html = '<div style="color:#fff;background:#ef4444;padding:12px;border-radius:10px;font-family:ui-monospace,monospace;">Preview gagal: '.e($e->getMessage()).'</div>';
        }

        $qriousScript = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
        $wrappedHtml = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Preview Template - '.e($templateKey).'</title>
	<script src="'.e($qriousScript).'"></script>
	<style>
body { color: #000000; background-color: #FFFFFF; font-size: 14px; font-family: Helvetica, arial, sans-serif; margin: 0; -webkit-print-color-adjust: exact; }
table.voucher { display: inline-block; border: 2px solid black; margin: 2px; }
.qrcode { height: 80px; width: 80px; }
@page { size: auto; margin: 9mm 3mm; }
@media print { table { page-break-inside: avoid; } tr { page-break-inside: avoid; } td { page-break-inside: avoid; } }
#num { float: right; display: inline-block; }
	</style>
</head>
<body>
'.$html.'
</body>
</html>';

        return response()->json([
            'template' => $templateKey,
            'html' => $wrappedHtml,
        ]);
    }

    /** @return array{0: string, 1: string} */
    protected function generateCredential(string $userMode, int $length, string $charMode, string $prefix): array
    {
        if ($userMode === 'up') {
            $username = $prefix.$this->randCharset($length, $this->charset($charMode));
            $password = $this->randCharset($length, '0123456789');

            return [$username, $password];
        }

        $digitMap = [3 => 1, 4 => 2, 5 => 2, 6 => 3, 7 => 3, 8 => 4];
        $digitLen = $digitMap[$length] ?? 2;
        $alphaLen = max(0, $length - $digitLen);

        if ($charMode === 'num') {
            $code = $prefix.$this->randCharset($length, '0123456789');
        } elseif (in_array($charMode, ['mix', 'mix1', 'mix2'], true)) {
            $code = $prefix.$this->randCharset($length, $this->charset($charMode));
        } else {
            $code = $prefix.$this->randCharset($alphaLen, $this->charset($charMode)).$this->randCharset($digitLen, '0123456789');
        }

        return [$code, $code];
    }

    protected function charset(string $mode): string
    {
        return match ($mode) {
            'lower' => 'abcdefghijklmnopqrstuvwxyz',
            'upper' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'upplow' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'num' => '0123456789',
            'mix' => 'abcdefghijklmnopqrstuvwxyz0123456789',
            'mix1' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            default => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        };
    }

    protected function randCharset(int $length, string $charset): string
    {
        $length = max(1, $length);
        $max = strlen($charset) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $charset[random_int(0, $max)];
        }

        return $out;
    }

    /** @return array{price: int, sprice: int, validity: string, expmode: string} */
    protected function parseOnLoginMeta(string $onlogin): array
    {
        // :put (",expmode,price,validity,sprice,,lock,");
        if (preg_match('/:put\s*\(\s*",([^"]*)"/', $onlogin, $m)) {
            $parts = explode(',', $m[1]);

            return [
                'expmode' => (string) ($parts[1] ?? ''),
                'price' => (int) ($parts[2] ?? 0),
                'validity' => (string) ($parts[3] ?? ''),
                'sprice' => (int) ($parts[4] ?? 0),
            ];
        }

        return ['price' => 0, 'sprice' => 0, 'validity' => '', 'expmode' => ''];
    }

    protected function vouchersUrl(string $section, array $extra = []): string
    {
        $query = array_filter(array_merge(['section' => $section], $extra), fn ($v) => $v !== null && $v !== '');

        return panel_route('vouchers').'?'.http_build_query($query);
    }

    protected function requireActiveRouter(): Router|RedirectResponse
    {
        $router = ActiveRouter::current();

        if (! $router) {
            return redirect()
                ->to(panel_route('settings').'?tab=mikrotik')
                ->with('error', 'Belum ada router MikroTik. Tambah & aktifkan router dulu.');
        }

        if (! $router->isEnabled()) {
            return redirect()
                ->to(panel_route('settings').'?tab=mikrotik')
                ->with('error', 'Router aktif dinonaktifkan. Enable atau pilih router lain.');
        }

        return $router;
    }
}
