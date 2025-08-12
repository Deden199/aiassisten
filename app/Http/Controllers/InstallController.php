<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Tenant;
use App\Models\User;
use App\Models\License;
use App\Services\EnvatoService;

class InstallController extends Controller
{
    public function welcome()
    {
        $checks = [
            'php' => PHP_VERSION,
            'ext' => [
                'openssl'  => extension_loaded('openssl'),
                'pdo'      => extension_loaded('pdo'),
                'mbstring' => extension_loaded('mbstring'),
                'tokenizer'=> extension_loaded('tokenizer'),
                'xml'      => extension_loaded('xml'),
                'ctype'    => extension_loaded('ctype'),
                'json'     => extension_loaded('json'),
                'fileinfo' => extension_loaded('fileinfo'),
            ],
            'writable' => [
                'storage'         => is_writable(storage_path()),
                'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            ],
        ];

        return view('install.step1', compact('checks'));
    }

    public function saveEnv(Request $request)
    {
        $data = $request->validate([
            'app_name'    => ['required','string','max:120'],
            'app_url'     => ['required','url'],
            'db_host'     => ['required','string'],
            'db_port'     => ['required','numeric'],
            'db_database' => ['required','string'],
            'db_username' => ['required','string'],
            'db_password' => ['nullable','string'],
            'envato_token'=> ['nullable','string'],
        ]);

        $this->writeEnv([
            'APP_NAME'        => $data['app_name'],
            'APP_URL'         => $data['app_url'],
            'APP_ENV'         => 'production',
            'APP_KEY'         => 'base64:'.base64_encode(random_bytes(32)),
            'DB_CONNECTION'   => 'mysql',
            'DB_HOST'         => $data['db_host'],
            'DB_PORT'         => $data['db_port'],
            'DB_DATABASE'     => $data['db_database'],
            'DB_USERNAME'     => $data['db_username'],
            'DB_PASSWORD'     => $data['db_password'],
            'ENVATO_API_TOKEN'=> $data['envato_token'] ?? '',
            'LICENSE_BYPASS'  => 'false',
        ]);

        return response()->json(['ok' => true]);
    }

    public function migrate()
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        $code = Artisan::call('migrate', ['--force' => true]);

        $out = Artisan::output();
        if ($code !== 0) {
            return response()->json(['ok' => false, 'output' => $out], 500);
        }

        return response()->json(['ok' => true, 'output' => $out]);
    }

public function createAdmin(Request $request, \App\Services\EnvatoService $envato)
{
    // VALIDATOR dev-friendly: terima domain localhost/IP + boleh kosong
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'tenant_name'    => ['required','string','max:120'],
        'admin_name'     => ['required','string','max:120'],
        'admin_email'    => ['required','email','max:190'],
        'admin_password' => ['required','string','min:8'],
        'purchase_code'  => ['nullable','string','max:120'],
        // terima domain TLD normal ATAU 'localhost' ATAU IPv4 (127.0.0.1)
        'domain'         => ['nullable','regex:/^((?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}|localhost|\\d{1,3}(?:\\.\\d{1,3}){3})$/i'],
    ]);

    if ($validator->fails()) {
        return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
    }
    $data = $validator->validated();

    try {
        \Illuminate\Support\Facades\DB::beginTransaction();

        // Tenant
        $tenant = \App\Models\Tenant::create([
            'id'   => (string) \Illuminate\Support\Str::uuid(),
            'name' => $data['tenant_name'],
            'slug' => \Illuminate\Support\Str::slug($data['tenant_name']).'-'.\Illuminate\Support\Str::random(5),
        ]);

        // Admin user — jika email sudah ada, update saja (dev-friendly)
        $user = \App\Models\User::where('email', $data['admin_email'])->first();
        if ($user) {
            $user->fill([
                'name'      => $data['admin_name'],
                'password'  => \Illuminate\Support\Facades\Hash::make($data['admin_password']),
                'tenant_id' => $tenant->id,
                'role'      => 'admin',
                'locale'    => 'en',
                'timezone'  => 'UTC',
            ])->save();
        } else {
            $user = \App\Models\User::create([
                'name'      => $data['admin_name'],
                'email'     => $data['admin_email'],
                'password'  => \Illuminate\Support\Facades\Hash::make($data['admin_password']),
                'tenant_id' => $tenant->id,
                'role'      => 'admin',
                'locale'    => 'en',
                'timezone'  => 'UTC',
            ]);
        }

$license = License::create([
    'tenant_id'     => $tenant->id,
    'purchase_code' => '',     // <-- placeholder, biar NOT NULL terpenuhi
    'domain'        => '',     // <-- placeholder
    'status'        => 'grace',
    'activated_at'  => now(),
    'grace_until'   => now()->addDays((int) config('license.grace_days', 7)),
]);


        // Optional: aktivasi Envato kalau diisi
        if (!empty($data['purchase_code']) && !empty($data['domain'])) {
            $result = $envato->verify($data['purchase_code']);
            if (!empty($result['valid'])) {
                $license->update([
                    'purchase_code' => hash('sha256', $data['purchase_code']),
                    'domain'        => hash('sha256', strtolower($data['domain'])),
                    'status'        => 'valid',
                    'activated_at'  => now(),
                    'grace_until'   => now()->addDays((int) config('license.grace_days', 7)),
                    'meta'          => ['envato' => $result['data'] ?? []],
                ]);
            }
        }

        // lock installer
        @file_put_contents(storage_path('installed'), now()->toDateTimeString());

        \Illuminate\Support\Facades\DB::commit();
        return response()->json(['ok' => true]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        \Illuminate\Support\Facades\Log::error('[Install] createAdmin failed', [
            'msg'  => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}


    public function done()
    {
        return view('install.done');
    }

    protected function writeEnv(array $pairs): void
    {
        $envPath = base_path('.env');
        $current = file_exists($envPath) ? file_get_contents($envPath) : "";

        foreach ($pairs as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $line = $key.'='.(str_contains((string) $value, ' ') ? '"'.$value.'"' : $value);
            if (preg_match($pattern, $current)) {
                $current = preg_replace($pattern, $line, $current);
            } else {
                $current .= PHP_EOL.$line;
            }
        }

        file_put_contents($envPath, $current);
    }
}
