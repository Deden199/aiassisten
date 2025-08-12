<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\EnvatoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenseController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant;
        $license = License::firstOrCreate(['tenant_id' => $tenant->id], [
            'status' => 'none',
        ]);

        return view('admin.licenses.index', ['license' => $license]);
    }

    public function update(Request $request, License $license, EnvatoService $envato)
    {
        $data = $request->validate([
            'purchase_code' => ['required', 'string', 'max:120'],
            'domain' => ['required', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
        ]);

        $result = $envato->verify($data['purchase_code']);

        if (! $result['valid']) {
            return back()->withErrors(['purchase_code' => $result['message'] ?? 'Verification failed'])->withInput();
        }

        $license->update([
            'purchase_code' => hash('sha256', $data['purchase_code']),
            'domain' => hash('sha256', strtolower($data['domain'])),
            'status' => 'valid',
            'activated_at' => now(),
            'grace_until' => now()->addDays((int) config('license.grace_days', 7)),
            'meta' => ['envato' => $result['data'] ?? []],
        ]);

        return redirect()->route('admin.licenses.index')->with('status', 'License verified and activated.');
    }

    public function deactivate(License $license)
    {
        $license->update([
            'status' => 'none',
            'activated_at' => null,
            'grace_until' => null,
            'purchase_code' => null,
            'domain' => null,
            'meta' => null,
        ]);

        return redirect()->route('admin.licenses.index')->with('status', 'License deactivated. You can re-activate with a different domain.');
    }
}
