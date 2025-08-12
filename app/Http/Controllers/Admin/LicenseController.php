<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\EnvatoService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::with('tenant')->paginate();
        return view('admin.licenses.index', compact('licenses'));
    }

    public function update(Request $request, License $license, EnvatoService $envato)
    {
        $data = $request->validate([
            'purchase_code' => 'required|string',
            'domain' => 'required|string',
        ]);

        $result = $envato->verify($data['purchase_code']);

        if (! $result['valid']) {
            return redirect()->back()->withErrors(['purchase_code' => $result['message'] ?? 'Verification failed']);
        }

        $license->update([
            'purchase_code' => hash('sha256', $data['purchase_code']),
            'domain' => hash('sha256', $data['domain']),
            'status' => 'valid',
            'activated_at' => now(),
        ]);

        return redirect()->route('admin.licenses.index')->with('status', 'License verified');
    }
}
