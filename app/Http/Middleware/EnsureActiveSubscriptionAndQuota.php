<?php

namespace App\Http\Middleware;

use App\Models\AiTask;
use App\Models\UsageCounter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscriptionAndQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $subscription = $user->tenant->subscription ?? null;

        $now = now();
        $allowedStatuses = ['active', 'trialing'];
        if (!$subscription) {
            return $this->deny($request);
        }

        $inTrial = $subscription->trial_end_at && $now->lte($subscription->trial_end_at);
        $inGrace = $subscription->grace_until && $now->lte($subscription->grace_until);

        if (!in_array($subscription->status, $allowedStatuses) && !$inTrial && !$inGrace) {
            return $this->deny($request);
        }

        $features = $subscription->plan->features ?? [];

        $maxTokens = $features['max_tokens_month'] ?? null;
        if ($maxTokens !== null) {
            $counter = UsageCounter::currentFor(
                $user,
                $subscription->current_period_start,
                $subscription->current_period_end
            );
            if ($counter->tokens_used >= $maxTokens) {
                return $this->deny($request, 'Token quota exceeded');
            }
        }

        $maxTasks = $features['max_tasks_per_day'] ?? null;
        if ($maxTasks !== null) {
            $tasksToday = AiTask::where('user_id', $user->id)
                ->whereDate('created_at', $now->toDateString())
                ->count();
            if ($tasksToday >= $maxTasks) {
                return $this->deny($request, 'Task quota exceeded');
            }
        }

        return $next($request);
    }

    private function deny(Request $request, string $message = 'Subscription required')
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $message], 402);
        }

        return redirect()->route('billing')->withErrors($message);
    }
}
