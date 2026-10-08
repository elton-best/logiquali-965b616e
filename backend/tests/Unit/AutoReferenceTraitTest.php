<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

use Tests\TestCase;
use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for HasReference trait
 * Tests automatic reference generation across models
 */
class AutoReferenceTraitTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function it_generates_unique_reference_for_enterprises()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $enterprise1 = Enterprise::factory()->create();
        $enterprise2 = Enterprise::factory()->create();

        $currentYear = date('Y');
        $this->assertEquals("ENT-{$currentYear}-001", $enterprise1->ref);
        $this->assertEquals("ENT-{$currentYear}-002", $enterprise2->ref);
    }
    #[Test]
    public function it_uses_correct_year_in_reference()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $enterprise = Enterprise::factory()->create();
        
        $currentYear = date('Y');
        $this->assertStringContainsString($currentYear, $enterprise->ref);
    }
    #[Test]
    public function it_pads_numbers_with_zeros()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $enterprise = Enterprise::factory()->create();
        
        $this->assertMatchesRegularExpression('/ENT-\d{4}-\d{3}$/', $enterprise->ref);
    }
}
