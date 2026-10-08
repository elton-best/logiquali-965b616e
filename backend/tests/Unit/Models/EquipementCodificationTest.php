<?php

namespace Tests\Unit\Models;

use App\Models\Equipement;
use PHPUnit\Framework\TestCase;

class EquipementCodificationTest extends TestCase
{
    public function test_it_derives_enterprise_sigle_from_multi_word_name(): void
    {
        $sigle = Equipement::deriveEnterpriseSigle('Best Experts Group');

        $this->assertSame('BEG', $sigle);
    }

    public function test_it_derives_enterprise_sigle_from_single_word_name(): void
    {
        $sigle = Equipement::deriveEnterpriseSigle('BestQHSE');

        $this->assertSame('BES', $sigle);
    }

    public function test_it_generates_complete_code_with_enterprise_sigle(): void
    {
        $code = Equipement::genererCodeComplet('BEG', 'INF', 'ECR', 'CEO', '001', 2025);

        $this->assertSame('BEG/INF/ECR/CEO/001/2025', $code);
    }
}
