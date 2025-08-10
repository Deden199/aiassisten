<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\License;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'tenantsCount' => Tenant::count(),
            'usersCount' => User::count(),
            'plansCount' => Plan::count(),
            'subscriptionsCount' => Subscription::count(),
            'licensesCount' => License::count(),
        ]);
    }
}
