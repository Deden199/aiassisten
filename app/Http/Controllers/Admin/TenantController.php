<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::withCount('users')->paginate();
        return view('admin.tenants.index', compact('tenants'));
    }
}
