<?php

namespace App\Http\Controllers;

use App\Helpers\MikrotikFormat;
use App\Models\MikpayRouter;
use App\Services\MikrotikService;
use App\Support\TenantRouterScope;
use App\Services\VoucherTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class HotspotController extends Controller
{
    /**
     * Pastikan ada selected_router dan connect API. Return redirect atau context untuk layout + mikrotik.
     *
     * @return array{currentRouter: MikpayRouter, routers: \Illuminate\Database\Eloquent\Collection, identity: string, clock: array, mikrotik: MikrotikService}|RedirectResponse
     */
    private function ensureRouter(Request $request): array|RedirectResponse
    {
        $userId = (int) ($request->user()?->id ?? 0);
        $sessionName = $request->session()->get('selected_router');
        if (! $sessionName) {
            return redirect()->route('sessions');
        }

        $router = TenantRouterScope::forUser($userId)->where('session_name', $sessionName)->where('status', 'enable')->first();
        if (! $router) {
            $request->session()->forget('selected_router');
            return redirect()->route('sessions')->with('error', 'Router tidak ditemukan.');
        }

        $mikrotik = new MikrotikService;
        if (! $mikrotik->connectRouter($router)) {
            $mikrotik->disconnect();
            return redirect()->route('sessions')->with('error', 'Tidak bisa connect ke router.');
        }

        $identity = $router->router_name ?: $sessionName;
        $clock = ['time' => date('H:i:s'), 'date' => date('d M Y'), 'time-zone-name' => date_default_timezone_get()];
        try {
            $getIdentity = $mikrotik->comm('/system/identity/print');
            if (is_array($getIdentity) && isset($getIdentity[0]['name'])) {
                $identity = $getIdentity[0]['name'];
            }
            $getClock = $mikrotik->comm('/system/clock/print');
            if (is_array($getClock) && isset($getClock[0])) {
                $clock = $getClock[0];
            }
        } catch (\Throwable $e) {
            // keep defaults
        }

        $routers = TenantRouterScope::forUser($userId)->where('status', 'enable')->orderBy('session_name')->get();

        return [
            'currentRouter' => $router,
            'routers' => $routers,
            'identity' => $identity,
            'clock' => $clock,
            'mikrotik' => $mikrotik,
        ];
    }

    /** Daftar user hotspot (filter: profile, comment, exp) */
    public function users(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        $profile = $request->get('profile', 'all');
        $comment = $request->get('comment');
        $exp = $request->get('exp');

        $users = [];
        $profiles = [];
        $commentOptions = [];
        $totalCount = 0;
        try {
            if ($exp === '1') {
                $users = $mikrotik->comm('/ip/hotspot/user/print', ['?limit-uptime' => '1s']);
            } elseif ($comment) {
                $users = $mikrotik->comm('/ip/hotspot/user/print', ['?comment' => $comment]);
            } elseif ($profile !== 'all') {
                $users = $mikrotik->comm('/ip/hotspot/user/print', ['?profile' => $profile]);
            } else {
                $users = $mikrotik->comm('/ip/hotspot/user/print');
            }
            $users = is_array($users) ? $users : [];
            $totalCount = count($users);
            $profiles = $mikrotik->comm('/ip/hotspot/user/profile/print');
            $profiles = is_array($profiles) ? $profiles : [];

            // Opsi filter Comment (voucher-style: vc-xxx / up-xxx dengan angka) + profile, seperti template
            $commentCounts = [];
            foreach ($users as $u) {
                $c = $u['comment'] ?? '';
                $p = $u['profile'] ?? '';
                if ($c === '') {
                    continue;
                }
                $pre = substr($c, 0, 3);
                if (($pre === 'vc-' || $pre === 'up-') && is_numeric(substr($c, 3, 3))) {
                    $key = $c . '#' . $p;
                    $commentCounts[$key] = ($commentCounts[$key] ?? 0) + 1;
                }
            }
            foreach ($commentCounts as $key => $cnt) {
                $parts = explode('#', $key, 2);
                $commentOptions[] = [
                    'comment' => $parts[0],
                    'profile' => $parts[1] ?? '',
                    'count' => $cnt,
                ];
            }
            usort($commentOptions, fn ($a, $b) => strcmp($a['comment'], $b['comment']));
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.users', array_merge($ctx, [
            'users' => $users,
            'profiles' => $profiles,
            'commentOptions' => $commentOptions,
            'totalCount' => $totalCount,
            'filterProfile' => $profile,
            'filterComment' => $comment,
            'filterExp' => $exp,
        ]));
    }

    /** Hotspot active (user online) */
    public function active(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        $server = $request->get('server');

        $list = [];
        try {
            if ($server !== null && $server !== '') {
                $list = $mikrotik->comm('/ip/hotspot/active/print', ['?server' => $server]);
            } else {
                $list = $mikrotik->comm('/ip/hotspot/active/print');
            }
            $list = is_array($list) ? $list : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.active', array_merge($ctx, [
            'actives' => $list,
            'filterServer' => $server,
        ]));
    }

    /** User profile list */
    public function userProfiles(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $profiles = [];
        try {
            $profiles = $mikrotik->comm('/ip/hotspot/user/profile/print');
            $profiles = is_array($profiles) ? $profiles : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.user-profiles', array_merge($ctx, ['profiles' => $profiles]));
    }

    /** Form add user profile hotspot (sesuaikan template adduserprofile.php) */
    public function addUserProfile(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $mikrotik = $ctx['mikrotik'];
        $pools = [];
        $queues = [];
        try {
            $pools = $mikrotik->comm('/ip/pool/print');
            $pools = is_array($pools) ? $pools : [];

            // parent queue: /queue/simple/print with dynamic=false
            $queues = $mikrotik->comm('/queue/simple/print', ['?dynamic' => 'false']);
            $queues = is_array($queues) ? $queues : [];
        } catch (\Throwable $e) {
            report($e);
        }

        $mikrotik->disconnect();

        return view('hotspot.user-profile-add', array_merge($ctx, [
            'pools' => $pools,
            'queues' => $queues,
        ]));
    }

    /** Store user profile hotspot (sesuai logika template lama) */
    public function storeUserProfile(Request $request): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $mikrotik = $ctx['mikrotik'];

        $data = $request->validate([
            'name' => 'required|string|max:128',
            'ppool' => 'nullable|string|max:128',
            'sharedusers' => 'nullable|numeric|min:0|max:999999',
            'ratelimit' => 'nullable|string|max:128',
            'expmode' => 'required|in:0,rem,ntf,remc,ntfc',
            'validity' => 'nullable|string|max:64',
            'graceperiod' => 'nullable|string|max:64',
            'price' => 'nullable|numeric|min:0|max:999999999',
            'sprice' => 'nullable|numeric|min:0|max:999999999',
            'lockunlock' => 'required|in:Enable,Disable',
            'parent' => 'nullable|string|max:128',
        ]);

        $name = preg_replace('/\s+/', '-', (string) $data['name']);
        $addrpool = (string) ($data['ppool'] ?? 'none');
        if ($addrpool === '') {
            $addrpool = 'none';
        }
        $sharedusers = (string) ($data['sharedusers'] ?? 1);
        $ratelimit = (string) ($data['ratelimit'] ?? '');
        $expmode = (string) $data['expmode'];
        $validity = (string) ($data['validity'] ?? '');
        $graceperiod = (string) ($data['graceperiod'] ?? ''); // belum dipakai di template on-login
        $price = (string) ($data['price'] ?? '');
        $sprice = (string) ($data['sprice'] ?? '');
        $lockunlock = (string) ($data['lockunlock'] ?? 'Disable');
        $parent = (string) ($data['parent'] ?? 'none');

        if ($price === '' || $price === '0') {
            $price = '0';
        }
        if ($sprice === '' || $sprice === '0') {
            $sprice = '0';
        }

        // Dalam template lama:
        // - $getlock dimasukkan ke parameter :put (Enable/Disable)
        // - $lock adalah blok script (hanya jika Enable)
        $getlock = $lockunlock; // "Enable" atau "Disable"
        $lock = $getlock === 'Enable'
            ? '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]'
            : '';

        // Randomized scheduler timing (template style)
        $randstarttime = '0' . rand(1, 5) . ':' . rand(10, 59) . ':' . rand(10, 59);
        $randinterval = '00:02:' . rand(10, 59);

        // record part (dipakai untuk remc/ntfc di template)
        $comment = '';
        $address = '';
        $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-'
            . $price
            . '-|-$address-|-$mac-|-' . $validity . '-|-' . $name . '-|-$comment" owner="$month$year" source="$date" comment="mikpay"';

        // String on-login mengikuti template lama adduserprofile.php
        $onlogin = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $getlock . ',"); {:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ]; /sys sch add name="$user" disable=no start-date=$date interval="' . $validity . '"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];};:delay 5s; /sys sch remove [find where name="$user"]';

        if ($expmode === 'rem') {
            $onlogin .= $lock . '}}';
            $mode = 'remove';
        } elseif ($expmode === 'ntf') {
            $onlogin .= $lock . '}}';
            $mode = 'set limit-uptime=1s';
        } elseif ($expmode === 'remc') {
            $onlogin .= $record . $lock . '}}';
            $mode = 'remove';
        } elseif ($expmode === 'ntfc') {
            $onlogin .= $record . $lock . '}}';
            $mode = 'set limit-uptime=1s';
        } elseif ($expmode === '0') {
            // Template: if ($expmode == "0" && $price != "") { ... }
            $onlogin = ($price !== '') ? ':put (",,' . $price . ',,,noexp,' . $getlock . ',")' . $lock : '';
            $mode = '';
        } else {
            $onlogin = '';
            $mode = '';
        }

        $bgservice = ':local dateint do={:local montharray ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );:local days [ :pick $d 4 6 ]; :local month [ :pick $d 0 3 ]; :local year [ :pick $d 7 11 ]; :local monthint ([ :find $montharray $month]); :local month ($monthint + 1); :if ( [len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="' . $name . '" ] do={ :local comment [ /ip hotspot user get $i comment]; :local name [ /ip hotspot user get $i name]; :local gettime [:pic $comment 12 20]; :if ([:pic $comment 3] = "/" and [:pic $comment 6] = "/") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user ' . $mode . ' $i ]; [ /ip hotspot active remove [find where user=$name] ];}}}';

        try {
            $mikrotik->comm('/ip/hotspot/user/profile/add', [
                'name' => $name,
                'address-pool' => $addrpool,
                'rate-limit' => $ratelimit,
                'shared-users' => $sharedusers,
                'status-autorefresh' => '1m',
                'on-login' => $onlogin,
                'parent-queue' => $parent !== '' ? $parent : 'none',
            ]);

            if ($expmode !== '0') {
                // scheduler monitor profile
                $mon = $mikrotik->comm('/system/scheduler/print', ['?name' => $name]);
                $monid = is_array($mon) && isset($mon[0]['.id']) ? $mon[0]['.id'] : null;

                if (empty($monid)) {
                    $mikrotik->comm('/system/scheduler/add', [
                        'name' => $name,
                        'start-time' => $randstarttime,
                        'interval' => $randinterval,
                        'on-event' => $bgservice,
                        'disabled' => 'no',
                        'comment' => 'Monitor Profile ' . $name,
                    ]);
                } else {
                    $mikrotik->comm('/system/scheduler/set', [
                        '.id' => $monid,
                        'name' => $name,
                        'start-time' => $randstarttime,
                        'interval' => $randinterval,
                        'on-event' => $bgservice,
                        'disabled' => 'no',
                        'comment' => 'Monitor Profile ' . $name,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('hotspot.user-profiles')->with('error', 'Gagal menambahkan user profile hotspot.');
        }

        $mikrotik->disconnect();

        return redirect()->route('hotspot.user-profiles')->with('success', 'User profile berhasil ditambahkan.');
    }

    /** Hosts (hotspot host) - filter: all, authorized, bypassed */
    public function hosts(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        $filter = $request->get('filter', 'all'); // all, hosta (authorized), hostp (bypassed)

        $list = [];
        try {
            if ($filter === 'hostp') {
                $list = $mikrotik->comm('/ip/hotspot/host/print', ['?bypassed' => 'yes']);
            } elseif ($filter === 'hosta') {
                $list = $mikrotik->comm('/ip/hotspot/host/print', ['?authorized' => 'yes']);
            } else {
                $list = $mikrotik->comm('/ip/hotspot/host/print');
            }
            $list = is_array($list) ? $list : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.hosts', array_merge($ctx, ['hosts' => $list, 'filter' => $filter]));
    }

    /** IP Binding */
    public function ipbinding(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $list = [];
        try {
            $list = $mikrotik->comm('/ip/hotspot/ip-binding/print');
            $list = is_array($list) ? $list : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.ipbinding', array_merge($ctx, ['bindings' => $list]));
    }

    /** Hotspot cookies */
    public function cookies(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $list = [];
        try {
            $list = $mikrotik->comm('/ip/hotspot/cookie/print');
            $list = is_array($list) ? $list : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.cookies', array_merge($ctx, ['cookies' => $list]));
    }

    /** Tambah user (form) */
    public function addUser(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        $profiles = [];
        $servers = [];
        try {
            $profiles = $mikrotik->comm('/ip/hotspot/user/profile/print');
            $profiles = is_array($profiles) ? $profiles : [];
            $servers = $mikrotik->comm('/ip/hotspot/print');
            $servers = is_array($servers) ? $servers : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.add-user', array_merge($ctx, ['profiles' => $profiles, 'servers' => $servers]));
    }

    /** Simpan user hotspot manual (sesuai template adduser.php) */
    public function storeAddUser(Request $request): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $mikrotik = $ctx['mikrotik'];

        $data = $request->validate([
            'server' => 'nullable|string|max:100',
            'name' => 'required|string|max:64',
            'pass' => 'required|string|max:64',
            'profile' => 'required|string|max:128',
            'timelimit' => 'nullable|string|max:32',
            'datalimit' => 'nullable|numeric|min:0|max:9999',
            'mbgb' => 'nullable|in:1048576,1073741824',
            'comment' => 'nullable|string|max:64',
        ]);

        $server = $data['server'] ?? '';
        $name = trim((string) $data['name']);
        $password = trim((string) $data['pass']);
        $profile = (string) $data['profile'];
        $timelimit = trim((string) ($data['timelimit'] ?? ''));
        $datalimit = $data['datalimit'] ?? null;
        $mbgb = (int) ($data['mbgb'] ?? 1048576);
        $commentInput = trim((string) ($data['comment'] ?? ''));

        // Template logic: if username == password => vc-<comment>, else up-<comment>
        $usermode = ($name === $password) ? 'vc-' : 'up-';
        $commentFinal = $usermode . $commentInput;

        $params = [
            'name' => $name,
            'password' => $password,
            'profile' => $profile,
            'disabled' => 'no',
            'comment' => $commentFinal,
        ];

        if ($server !== '' && $server !== 'all') {
            $params['server'] = $server;
        }

        if ($timelimit !== '' && $timelimit !== '0') {
            $params['limit-uptime'] = $timelimit;
        }

        if ($datalimit !== null && $datalimit > 0) {
            $limitBytes = (int) $datalimit * $mbgb;
            if ($limitBytes > 0) {
                $params['limit-bytes-total'] = (string) $limitBytes;
            }
        }

        try {
            $mikrotik->comm('/ip/hotspot/user/add', $params);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('hotspot.users')->with('error', 'Gagal menambahkan user hotspot.');
        }

        $mikrotik->disconnect();

        // Redirect balik dengan filter agar user mudah ditemukan
        return redirect()->route('hotspot.users', [
            'profile' => $profile,
            'comment' => $commentFinal,
        ])->with('success', 'User hotspot berhasil ditambahkan.');
    }

    /** Generate user (voucher) - placeholder */
    public function generateUser(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        $profiles = [];
        $servers = [];
        try {
            $profiles = $mikrotik->comm('/ip/hotspot/user/profile/print');
            $profiles = is_array($profiles) ? $profiles : [];
            $servers = $mikrotik->comm('/ip/hotspot/print');
            $servers = is_array($servers) ? $servers : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('hotspot.generate-user', array_merge($ctx, [
            'profiles' => $profiles,
            'servers' => $servers,
            'lastGenerate' => session('last_generate_voucher'),
        ]));
    }

    /** Store generated voucher users (VC mode: username == password) */
    public function storeGenerateUser(Request $request): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $mikrotik = $ctx['mikrotik'];
        $data = $request->validate([
            'qty' => 'required|integer|min:1|max:500',
            'server' => 'required|string|max:100',
            'user' => 'required|in:up,vc',
            'userl' => 'required|integer|in:3,4,5,6,7,8',
            'prefix' => 'nullable|string|max:6',
            'char' => 'required|in:lower,upper,upplow,num,mix,mix1,mix2',
            'profile' => 'required|string|max:128',
            'timelimit' => 'nullable|string|max:32',
            'datalimit' => 'nullable|numeric|min:0|max:9999',
            'adcomment' => 'nullable|string|max:64',
            'mbgb' => 'required|in:1048576,1073741824',
        ]);

        $quantity = (int) $data['qty'];
        $server = trim((string) $data['server']);
        $userMode = (string) $data['user'];
        $profile = (string) $data['profile'];
        $userLength = (int) $data['userl'];
        $prefix = trim((string) ($data['prefix'] ?? ''));
        $char = (string) $data['char'];
        $adComment = trim((string) ($data['adcomment'] ?? ''));

        $timelimit = trim((string) ($data['timelimit'] ?? ''));
        $datalimit = $data['datalimit'] ?? null;
        $mbgb = (int) $data['mbgb'];

        if ($server === 'all') {
            $server = '';
        }
        $random3 = (string) random_int(100, 999);
        $datePart = now()->format('m.d.y');
        $commentFinal = trim('vc-' . $random3 . '-' . $datePart . '-' . $adComment, '-');

        $limitBytesTotal = null;
        if ($datalimit !== null && (float) $datalimit > 0) {
            $limitBytesTotal = (int) ((float) $datalimit * (float) $mbgb);
            if ($limitBytesTotal <= 0) {
                $limitBytesTotal = null;
            }
        }

        $createdCount = 0;
        $lastCode = null;
        try {
            for ($i = 0; $i < $quantity; $i++) {
                [$name, $password] = $this->generateVoucherCredentialByTemplate($userMode, $userLength, $char, $prefix);
                $attempt = 0;
                do {
                    $attempt++;
                    $existsRows = $mikrotik->comm('/ip/hotspot/user/print', ['?name' => $name]);
                    $existsInRouter = count($this->normalizeHotspotUserPrintRows($existsRows)) > 0;
                    if ($existsInRouter) {
                        [$name, $password] = $this->generateVoucherCredentialByTemplate($userMode, $userLength, $char, $prefix);
                    }
                    if ($attempt >= 30 && $existsInRouter) {
                        throw new \RuntimeException('Gagal membuat username voucher unik setelah beberapa percobaan.');
                    }
                } while ($existsInRouter);
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

                if ($limitBytesTotal !== null) {
                    $params['limit-bytes-total'] = (string) $limitBytesTotal;
                }

                $mikrotik->comm('/ip/hotspot/user/add', $params);
                $createdCount++;
            }
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()
                ->route('hotspot.generate-user')
                ->with('error', 'Gagal membuat voucher: ' . $e->getMessage());
        }

        $mikrotik->disconnect();

        $request->session()->flash('last_generate_voucher', [
            'code' => $lastCode,
            'date' => now()->format('d M Y H:i'),
            'profile' => $profile,
            'user_mode' => $userMode,
            'timelimit' => $timelimit !== '' ? $timelimit : '-',
            'datalimit' => $limitBytesTotal !== null ? MikrotikFormat::formatBytes($limitBytesTotal, 2) : '-',
            'comment' => $commentFinal,
            'qty' => $createdCount,
        ]);

        return redirect()->route('hotspot.generate-user', [
            'profile' => $profile,
            'comment' => $commentFinal,
        ])->with('success', "Voucher berhasil dibuat: {$createdCount} user (comment batch: {$commentFinal}).");
    }

    private function generateVoucherCredentialByTemplate(string $userMode, int $length, string $charMode, string $prefix): array
    {
        if ($userMode === 'up') {
            $username = $prefix . match ($charMode) {
                'lower' => $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyz'),
                'upper' => $this->randFromCharset($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                'upplow' => $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                'mix' => $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyz0123456789'),
                'mix1' => $this->randFromCharset($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
                'mix2' => $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
                'num' => $this->randFromCharset($length, '0123456789'),
                default => $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
            };
            $password = $this->randFromCharset($length, '0123456789');

            return [$username, $password];
        }

        // VC mode: username == password (voucher code)
        $digitMap = [3 => 1, 4 => 2, 5 => 2, 6 => 3, 7 => 3, 8 => 4];
        $digitLen = ($digitMap[$length] ?? 2);
        $alphaLen = max(0, $length - $digitLen);

        $code = match ($charMode) {
            'lower' => $prefix . $this->randFromCharset($alphaLen, 'abcdefghijklmnopqrstuvwxyz') . $this->randFromCharset($digitLen, '0123456789'),
            'upper' => $prefix . $this->randFromCharset($alphaLen, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ') . $this->randFromCharset($digitLen, '0123456789'),
            'upplow' => $prefix . $this->randFromCharset($alphaLen, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') . $this->randFromCharset($digitLen, '0123456789'),
            'num' => $prefix . $this->randFromCharset($length, '0123456789'),
            'mix' => $prefix . $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyz0123456789'),
            'mix1' => $prefix . $this->randFromCharset($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
            'mix2' => $prefix . $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
            default => $prefix . $this->randFromCharset($length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
        };

        return [$code, $code];
    }

    private function randFromCharset(int $length, string $charset): string
    {
        $length = max(1, $length);
        $max = strlen($charset) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $charset[random_int(0, $max)];
        }

        return $out;
    }

    /** Hapus satu user hotspot (.id) */
    public function removeUser(Request $request, string $id): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        try {
            $mikrotik->comm('/ip/hotspot/user/remove', ['.id' => $id]);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('hotspot.users')->with('error', 'Gagal menghapus user.');
        }
        $mikrotik->disconnect();
        return redirect()->route('hotspot.users', $request->only(['profile', 'comment', 'exp']))->with('success', 'User dihapus.');
    }

    /** Enable user hotspot */
    public function enableUser(Request $request, string $id): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        try {
            $mikrotik->comm('/ip/hotspot/user/enable', ['.id' => $id]);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('hotspot.users')->with('error', 'Gagal mengaktifkan user.');
        }
        $mikrotik->disconnect();
        return redirect()->route('hotspot.users', $request->only(['profile', 'comment', 'exp']))->with('success', 'User diaktifkan.');
    }

    /** Disable user hotspot */
    public function disableUser(Request $request, string $id): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        try {
            $mikrotik->comm('/ip/hotspot/user/disable', ['.id' => $id]);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('hotspot.users')->with('error', 'Gagal menonaktifkan user.');
        }
        $mikrotik->disconnect();
        return redirect()->route('hotspot.users', $request->only(['profile', 'comment', 'exp']))->with('success', 'User dinonaktifkan.');
    }

    /** Hapus semua user dengan comment tertentu */
    public function removeByComment(Request $request): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $comment = $request->get('comment');
        if (! $comment) {
            return redirect()->route('hotspot.users')->with('error', 'Comment wajib.');
        }
        $mikrotik = $ctx['mikrotik'];
        try {
            $users = $mikrotik->comm('/ip/hotspot/user/print', ['?comment' => $comment]);
            $users = is_array($users) ? $users : [];
            foreach ($users as $u) {
                if (isset($u['.id'])) {
                    $mikrotik->comm('/ip/hotspot/user/remove', ['.id' => $u['.id']]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('hotspot.users')->with('error', 'Gagal menghapus user by comment.');
        }
        $mikrotik->disconnect();
        return redirect()->route('hotspot.users')->with('success', 'User dengan comment tersebut dihapus.');
    }

    /** Hapus user expired (limit-uptime 1s) */
    public function removeExpired(Request $request): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        try {
            $users = $mikrotik->comm('/ip/hotspot/user/print', ['?limit-uptime' => '1s']);
            $users = is_array($users) ? $users : [];
            foreach ($users as $u) {
                if (isset($u['.id'])) {
                    $mikrotik->comm('/ip/hotspot/user/remove', ['.id' => $u['.id']]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('hotspot.users')->with('error', 'Gagal menghapus user expired.');
        }
        $mikrotik->disconnect();
        return redirect()->route('hotspot.users')->with('success', 'User expired dihapus.');
    }

    /** Print voucher — Blade resources/views/voucher atau override storage/app/voucher-templates/ */
    public function printVoucher(Request $request, VoucherTemplateService $voucherTemplates)
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $mikrotik = $ctx['mikrotik'];
        $comment = (string) $request->get('id', '');
        $userParam = (string) $request->get('user', '');
        $qr = $request->get('qr', 'no') === 'yes';
        $small = $request->get('small', 'no') === 'yes';

        $users = [];
        $profilesByName = [];
        try {
            if ($userParam !== '') {
                $parts = explode('-', $userParam, 2);
                $un = $parts[1] ?? '';
                if ($un !== '') {
                    $users = $mikrotik->comm('/ip/hotspot/user/print', ['?name' => $un]);
                }
            } elseif ($comment !== '') {
                // Tombol "Cetak" massal memakai ?id=<comment> persis seperti di MikroTik.
                // Jika tidak ada yang cocok, dan id berbentuk vc-... / up-... (sama seperti ?user=),
                // coba lagi by name — sering comment di router ≠ string vc-username.
                $users = $mikrotik->comm('/ip/hotspot/user/print', ['?comment' => $comment]);
                $users = $this->normalizeHotspotUserPrintRows($users);
                if (count($users) === 0 && preg_match('/^(vc|up)-(.+)$/i', $comment, $m)) {
                    $users = $mikrotik->comm('/ip/hotspot/user/print', ['?name' => $m[2]]);
                }
            }
            $users = $this->normalizeHotspotUserPrintRows($users);
            $profiles = $mikrotik->comm('/ip/hotspot/user/profile/print');
            $profiles = is_array($profiles) ? $profiles : [];
            foreach ($profiles as $p) {
                $name = $p['name'] ?? '';
                if ($name !== '') {
                    $profilesByName[$name] = $p;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $routerHost = $ctx['currentRouter']->host ?: '127.0.0.1';
        $dnsname = explode(':', $routerHost)[0];
        $hotspotname = $ctx['identity'] ?? 'MIKPAY HOTSPOT';

        $currency = 'Rp';
        $logoPath = public_path('mikpay/img');
        $logoDefault = asset('mikpay/img/logo.png');
        $logoSession = $logoDefault;
        $logoFile = 'logo.png';
        $selectedSessionName = (string) ($ctx['currentRouter']->session_name ?? 'mikpay');
        $safeSession = preg_replace('/[^a-zA-Z0-9_-]/', '', $selectedSessionName) ?: 'router';
        $sessionLogoFile = 'logo-' . $safeSession . '.png';
        if (is_dir($logoPath) && file_exists($logoPath . DIRECTORY_SEPARATOR . $sessionLogoFile)) {
            $logoSession = asset('mikpay/img/' . $sessionLogoFile);
            $logoFile = $sessionLogoFile;
        }
        // $logo dipakai untuk "logo aplikasi" (fixed default), sedangkan $logo_session untuk logo voucher per router/session.
        $logo = $logoDefault . '?v=' . time();

        $templateKey = $small ? 'small' : 'default';
        $bundledPath = $voucherTemplates->bundledViewFilePath($templateKey);
        $userId = (int) auth()->id();
        $resolvedTemplate = $voucherTemplates->resolveStoragePath($templateKey, $userId);
        if ($resolvedTemplate === null && ! File::exists($bundledPath)) {
            $mikrotik->disconnect();
            return response('<p>Template voucher tidak ditemukan untuk: ' . e($templateKey) . '</p>', 404)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $voucherHtmlParts = [];
        $num = 0;
        foreach ($users as $u) {
            $num++;
            $username = $u['name'] ?? '-';
            $password = (string) ($u['password'] ?? '');
            if ($password === '') {
                $password = $username;
            }
            $profileName = $u['profile'] ?? '';
            $limitUptime = $u['limit-uptime'] ?? '6h';
            $limitBytes = isset($u['limit-bytes-total']) ? (int) $u['limit-bytes-total'] : 0;

            $validityRaw = '1d';
            $timelimitRaw = $limitUptime;
            $getsprice = '5000';
            $getprice = '5000';
            $profile = $profilesByName[$profileName] ?? null;
            if ($profile && ! empty($profile['on-login'])) {
                $parts = explode(',', $profile['on-login']);
                if (isset($parts[3])) {
                    $validityRaw = trim($parts[3]);
                }
                if (isset($parts[2])) {
                    $getprice = trim($parts[2]);
                }
                if (isset($parts[4])) {
                    $getsprice = trim($parts[4]);
                }
            }

            $price = $currency . ' ' . number_format((float) ($getsprice ?: 0), 0, ',', '.');
            $getdatalimit = $limitBytes;
            $datalimit = $getdatalimit > 0 ? MikrotikFormat::formatBytes($getdatalimit, 2) : '';

            if ($small) {
                $validity = $validityRaw;
                $timelimit = $timelimitRaw;
                if (substr($validityRaw, -1) === 'd') {
                    $validity = 'MASA AKTIF : ' . substr($validityRaw, 0, -1) . ' Hari';
                } elseif (substr($validityRaw, -1) === 'h') {
                    $validity = 'MASA AKTIF : ' . substr($validityRaw, 0, -1) . ' Jam';
                }
                if (substr($timelimitRaw, -1) === 'h') {
                    $timelimit = 'Durasi:' . substr($timelimitRaw, 0, -1) . ' Jam';
                } elseif (substr($timelimitRaw, -1) === 'd') {
                    $timelimit = 'Durasi:' . substr($timelimitRaw, 0, -1) . ' HARI';
                } else {
                    $timelimit = 'Durasi:' . $timelimitRaw;
                }
            } else {
                $validity = $validityRaw;
                $timelimit = $timelimitRaw;
            }

            $usermode = ($password === $username) ? 'vc' : 'up';
            $v_opsi = 'down';
            $urilogin = 'http://' . $dnsname . '/login?username=' . rawurlencode($username) . '&password=' . rawurlencode($password);
            $qrId = 'qr-' . $num;
            $qrcode = '<canvas class="qrcode" id="' . e($qrId) . '"></canvas><script>(function(){new QRious({element:document.getElementById("' . e($qrId) . '"),value:' . json_encode($urilogin) . ',size:256});})();</script>';
            try {
                $voucherHtmlParts[] = $voucherTemplates->render($templateKey, [
                    'username' => $username,
                    'password' => $password,
                    'validity' => $validity,
                    'timelimit' => $timelimit,
                    'getdatalimit' => $getdatalimit,
                    'datalimit' => $datalimit,
                    'getsprice' => (string) $getsprice,
                    'getprice' => (string) $getprice,
                    'profile' => $profileName,
                    'comment' => (string) ($u['comment'] ?? ''),
                    'hotspotname' => $hotspotname,
                    'num' => $num,
                    'usermode' => $usermode,
                    'v_opsi' => $v_opsi,
                    'urilogin' => $urilogin,
                    'qrcode' => $qrcode,
                    'logo' => $logo,
                    'logo_session' => $logoSession,
                    'logo_default' => $logoDefault,
                    'logo_file' => $logoFile,
                    'session_name' => $selectedSessionName,
                    'price' => $price,
                    'currency' => $currency,
                    'dnsname' => $dnsname,
                ], $userId);
            } catch (\Throwable $e) {
                report($e);
                $voucherHtmlParts[] = '<div style="padding:8px;border:1px solid #ccc;">Error: ' . e($e->getMessage()) . '</div>';
            }
        }

        $mikrotik->disconnect();

        $bodyHtml = implode('', $voucherHtmlParts);
        if ($bodyHtml === '') {
            $bodyHtml = '<p style="padding:16px;">Tidak ada data voucher.</p>';
        }

        $qriousScript = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
        $fullHtml = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Voucher Print</title>
	<script src="' . e($qriousScript) . '"></script>
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
' . $bodyHtml . '
</body>
</html>';

        return response($fullHtml)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Hasil /ip/hotspot/user/print: satu user bisa jadi satu array assosiatif tanpa indeks 0.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeHotspotUserPrintRows(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        if (isset($raw['name']) && ! isset($raw[0])) {
            return [$raw];
        }

        return array_values(array_filter($raw, fn ($row) => is_array($row) && isset($row['name'])));
    }
}
