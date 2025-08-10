<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['tenant','plan'])->paginate();
        return view('admin.subscriptions.index', compact('subscriptions'));
    }
}
