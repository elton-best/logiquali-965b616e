<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

use Tests\TestCase;
use App\Models\User;
use App\Models\Responsibility;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for User model
 * Tests relationships, attributes and business logic
 */
class UserTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function it_has_fillable_attributes()
    {
        $user = new User();
        
        $this->assertEquals([
            'ref',
            'name',
            'first_name',
            'last_name',
            'username',
            'email',
            'password',
            'phone',
            'address',
            'photo_path',
            'signature_path',
            'signature_uploaded_at',
            'user_type',
            'enterprise_id',
            'site_id',
            'role',
            'job_title',
            'start_date',
            'is_active',
            'collaborator_approval_status',
            'collaborator_requested_by',
            'collaborator_requested_at',
            'collaborator_approved_by',
            'collaborator_approved_at',
            'collaborator_rejected_by',
            'collaborator_rejected_at',
            'collaborator_rejection_reason',
            'last_login_at',
            'must_change_password',
            'password_changed_at',
        ], $user->getFillable());
    }
    #[Test]
    public function it_casts_email_verified_at_to_datetime()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }
    #[Test]
    public function it_hides_password_in_arrays()
    {
        $user = User::factory()->create();
        
        $array = $user->toArray();
        
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }
    #[Test]
    public function it_belongs_to_responsibility()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->responsibilities()
        );
    }
    #[Test]
    public function it_belongs_to_enterprise()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $user->enterprise()
        );
    }
    #[Test]
    public function it_belongs_to_site()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $user->site()
        );
    }
    #[Test]
    public function it_uses_auto_reference_trait()
    {
        $user = new User();
        
        $this->assertContains('App\Traits\HasReference', class_uses($user));
    }
    #[Test]
    public function it_soft_deletes()
    {
        $user = User::factory()->create();
        
        $user->delete();
        
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
