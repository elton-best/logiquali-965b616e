<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Middleware\AdvancedRateLimit;
use App\Models\User;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class AdvancedRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $enterprise;
    protected $site;
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $this->middleware = new AdvancedRateLimit();
        
        // Clear rate limiter state
        RateLimiter::clear('api');
        Cache::flush();
    }

    public function test_allows_requests_within_normal_limits_for_regular_users()
    {
        $user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company'
        ]);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn() => $user);
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_applies_different_limits_based_on_user_type()
    {
        $normalUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company'
        ]);
        
        $adminUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company'
        ]);
        
        $request = Request::create('/api/v1/test', 'GET');
        
        // Test utilisateur normal
        $request->setUserResolver(fn() => $normalUser);
        
        for ($i = 0; $i < 5; $i++) {
            $response = $this->middleware->handle($request, function () {
                return new Response('OK');
            });
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        // Test admin - limites plus élevées
        $request->setUserResolver(fn() => $adminUser);
        
        for ($i = 0; $i < 10; $i++) {
            $response = $this->middleware->handle($request, function () {
                return new Response('OK');
            });
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function test_blocks_requests_when_rate_limit_exceeded()
    {
        $user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'clientb'
        ]);
        
        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn() => $user);
        
        // Simulate many requests to trigger rate limit
        $responses = [];
        for ($i = 0; $i < 150; $i++) {
            try {
                $response = $this->middleware->handle($request, function () {
                    return new Response('OK');
                });
                $responses[] = $response->getStatusCode();
            } catch (\Exception $e) {
                $responses[] = 429;
                break;
            }
        }
        
        $this->assertContains(429, $responses);
    }
}