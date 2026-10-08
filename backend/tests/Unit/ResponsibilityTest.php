<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

use Tests\TestCase;
use App\Models\Responsibility;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for Responsibility model
 */
class ResponsibilityTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function it_has_correct_fillable_attributes()
    {
        $responsibility = new Responsibility();

        $this->assertEquals([
            'ref',
            'enterprise_id',
            'process_id',
            'user_id',
            'level',
            'roles',
            'deliverables',
            'responsible_type',
            'responsible_id',
            'role_title',
            'responsibilities',
            'authorities',
            'start_date',
            'end_date',
        ], $responsibility->getFillable());
    }
    #[Test]
    public function it_belongs_to_process()
    {
        $responsibility = Responsibility::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $responsibility->process()
        );
    }
    #[Test]
    public function it_belongs_to_user()
    {
        $responsibility = Responsibility::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $responsibility->user()
        );
    }
    #[Test]
    public function it_soft_deletes()
    {
        $responsibility = Responsibility::factory()->create();

        $responsibility->delete();

        $this->assertSoftDeleted('responsibilities', ['id' => $responsibility->id]);

        // Can still be retrieved with withTrashed
        $found = Responsibility::withTrashed()->find($responsibility->id);
        $this->assertNotNull($found);
    }
}
