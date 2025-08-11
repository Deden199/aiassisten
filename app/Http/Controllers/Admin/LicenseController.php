<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::with('tenant')->paginate();
        return view('admin.licenses.index', compact('licenses'));
    }

    public function update(Request $request, License $license)
    {
        $data = $request->validate([
            'status' => 'required|string',
        ]);
        $license->update($data);

        return redirect()->route('admin.licenses.index')->with('status', 'License updated');
    }
}
