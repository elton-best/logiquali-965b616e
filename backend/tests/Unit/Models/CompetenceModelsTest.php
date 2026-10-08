<?php

namespace Tests\Unit\Models;

use App\Models\CompetenceAcquise;
use App\Models\CompetenceRequise;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CompetenceModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_competence_requise_scopes()
    {
        $obligatoire = CompetenceRequise::factory()->create(['priority' => 'obligatoire']);
        $recommandee = CompetenceRequise::factory()->create(['priority' => 'recommandee']);
        $withCert = CompetenceRequise::factory()->withCertification()->create();
        $technique = CompetenceRequise::factory()->create(['competence_type' => 'technique']);

        $this->assertTrue(CompetenceRequise::obligatoire()->get()->contains($obligatoire));
        $this->assertFalse(CompetenceRequise::obligatoire()->get()->contains($recommandee));
        $this->assertTrue(CompetenceRequise::withCertification()->get()->contains($withCert));
        $this->assertTrue(CompetenceRequise::byType('technique')->get()->contains($technique));
    }

    public function test_competence_requise_methods()
    {
        $obligatoire = CompetenceRequise::factory()->create(['priority' => 'obligatoire']);
        $optionnelle = CompetenceRequise::factory()->create(['priority' => 'optionnelle']);
        $withExpiry = CompetenceRequise::factory()->create(['validity_months' => 24]);
        $withoutExpiry = CompetenceRequise::factory()->create(['validity_months' => null]);

        $this->assertTrue($obligatoire->isObligatoire());
        $this->assertFalse($optionnelle->isObligatoire());
        $this->assertTrue($withExpiry->hasExpiry());
        $this->assertFalse($withoutExpiry->hasExpiry());
    }

    public function test_competence_acquise_scopes()
    {
        $active = CompetenceAcquise::factory()->active()->create();
        $expired = CompetenceAcquise::factory()->expired()->create();
        $expiringSoon = CompetenceAcquise::factory()->create([
            'expiry_date' => now()->addDays(15),
            'status' => 'active'
        ]);

        $this->assertTrue(CompetenceAcquise::active()->get()->contains($active));
        $this->assertFalse(CompetenceAcquise::active()->get()->contains($expired));
        $this->assertTrue(CompetenceAcquise::expired()->get()->contains($expired));
        $this->assertTrue(CompetenceAcquise::expiresSoon(30)->get()->contains($expiringSoon));
    }

    public function test_competence_acquise_status_methods()
    {
        $expired = CompetenceAcquise::factory()->create([
            'expiry_date' => now()->subDays(5),
            'status' => 'active'
        ]);

        $expiringSoon = CompetenceAcquise::factory()->create([
            'expiry_date' => now()->addDays(15),
            'status' => 'active'
        ]);

        $active = CompetenceAcquise::factory()->create([
            'expiry_date' => now()->addDays(60),
            'status' => 'active'
        ]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expiringSoon->isExpired());
        $this->assertFalse($active->isExpired());

        $this->assertFalse($expired->isExpiringSoon(30));
        $this->assertTrue($expiringSoon->isExpiringSoon(30));
        $this->assertFalse($active->isExpiringSoon(30));
    }

    public function test_competence_acquise_meets_required_level()
    {
        $competenceRequise = CompetenceRequise::factory()->create(['level_required' => 'intermediaire']);
        
        $sufficient = CompetenceAcquise::factory()->create([
            'competence_requise_id' => $competenceRequise->id,
            'level_acquired' => 'avance'
        ]);

        $insufficient = CompetenceAcquise::factory()->create([
            'competence_requise_id' => $competenceRequise->id,
            'level_acquired' => 'base'
        ]);

        $this->assertTrue($sufficient->meetsRequiredLevel());
        $this->assertFalse($insufficient->meetsRequiredLevel());
    }

    public function test_competence_acquise_days_until_expiry()
    {
        $fixedDate = Carbon::parse('2024-01-01');
        Carbon::setTestNow($fixedDate);
        
        $withExpiry = CompetenceAcquise::factory()->create([
            'expiry_date' => $fixedDate->copy()->addDays(30)
        ]);

        $withoutExpiry = CompetenceAcquise::factory()->create([
            'expiry_date' => null
        ]);

        $this->assertEquals(30, $withExpiry->getDaysUntilExpiry());
        $this->assertNull($withoutExpiry->getDaysUntilExpiry());
        
        Carbon::setTestNow();
    }
}