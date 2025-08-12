<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('tenant')->paginate();
        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $tenants = Tenant::orderBy('name')->get();
        $roles = ['admin', 'user'];
        return view('admin.users.edit', compact('user', 'tenants', 'roles'));
    }

    public function update(Request $r, User $user)
    {
        $data = $r->validate([
            'role' => 'required|in:admin,user',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $user->update($data);

        return redirect()->route('admin.users.index');
    }
}
