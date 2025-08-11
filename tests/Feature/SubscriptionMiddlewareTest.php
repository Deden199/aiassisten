<?php

namespace Tests\Feature;

use App\Models\{User, Tenant, Plan, Price, Subscription, AiProject, UsageCounter};
use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Http\Middleware\CostCapGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setupData(string $status = 'active', int $tokensUsed = 0): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'T',
            'slug' => Str::slug('t-'.Str::random(6)),
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $plan = Plan::create([
            'id' => (string) Str::uuid(),
            'code' => 'basic',
            'features' => ['max_tokens_month' => 5, 'max_tasks_per_day' => 2],
            'is_active' => true,
        ]);
        Price::create([
            'id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'currency' => 'usd',
            'amount_cents' => 1000,
            'is_active' => true,
            'provider' => 'stripe',
            'provider_price_id' => 'price_123',
            'interval' => 'month',
        ]);
        Subscription::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_123',
            'status' => $status,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
        ]);

        $this->actingAs($user);
        $project = AiProject::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'Test',
        ]);
        UsageCounter::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'tokens_used' => $tokensUsed,
        ]);

        return [$user, $project];
    }

    public function test_blocks_when_subscription_inactive(): void
    {
        [$user, $project] = $this->setupData('canceled');

        $this->withoutMiddleware([ThrottleRequests::class, CostCapGuard::class]);
        $response = $this->postJson("/projects/{$project->id}/tasks/summarize");
        $response->assertStatus(402);
    }

    public function test_blocks_when_quota_exceeded(): void
    {
        [$user, $project] = $this->setupData('active', 5);

        $this->withoutMiddleware([ThrottleRequests::class, CostCapGuard::class]);
        $response = $this->postJson("/projects/{$project->id}/tasks/summarize");
        $response->assertStatus(402);
    }
}
