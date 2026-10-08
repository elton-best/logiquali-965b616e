<?php

namespace Tests\Unit\Models;

use App\Models\Habilitation;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HabilitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_habilitation_belongs_to_user()
    {
        $user = User::factory()->create();
        $habilitation = Habilitation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $habilitation->user);
        $this->assertEquals($user->id, $habilitation->user->id);
    }

    public function test_expires_soon_scope()
    {
        $expiringSoon = Habilitation::factory()->create([
            'expiry_date' => now()->addDays(15),
            'status' => 'active'
        ]);

        $notExpiring = Habilitation::factory()->create([
            'expiry_date' => now()->addDays(60),
            'status' => 'active'
        ]);

        $results = Habilitation::expiresSoon(30)->get();

        $this->assertTrue($results->contains($expiringSoon));
        $this->assertFalse($results->contains($notExpiring));
    }

    public function test_expired_scope()
    {
        $expired = Habilitation::factory()->create([
            'expiry_date' => now()->subDays(5),
            'status' => 'active'
        ]);

        $active = Habilitation::factory()->create([
            'expiry_date' => now()->addDays(30),
            'status' => 'active'
        ]);

        $results = Habilitation::expired()->get();

        $this->assertTrue($results->contains($expired));
        $this->assertFalse($results->contains($active));
    }

    public function test_is_expired_method()
    {
        $expired = Habilitation::factory()->create([
            'expiry_date' => now()->subDays(1)
        ]);

        $active = Habilitation::factory()->create([
            'expiry_date' => now()->addDays(30)
        ]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($active->isExpired());
    }

    public function test_is_expiring_soon_method()
    {
        $expiringSoon = Habilitation::factory()->create([
            'expiry_date' => now()->addDays(15)
        ]);

        $notExpiring = Habilitation::factory()->create([
            'expiry_date' => now()->addDays(60)
        ]);

        $this->assertTrue($expiringSoon->isExpiringSoon(30));
        $this->assertFalse($notExpiring->isExpiringSoon(30));
    }
}