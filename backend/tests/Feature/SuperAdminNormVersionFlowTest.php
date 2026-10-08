<?php

namespace Tests\Feature;

use App\Models\Norm;
use App\Models\User;
use Database\Seeders\UnifiedPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuperAdminNormVersionFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function superadmin_can_add_version_to_legacy_norm_and_then_add_sections(): void
    {
        $this->seed(UnifiedPermissionsSeeder::class);

        $superAdmin = User::factory()->create([
            'user_type' => 'super_admin',
        ]);

        // Legacy state: norm exists without current version.
        $norm = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'description' => 'Norme legacy sans version liée',
            'domain' => 'quality',
            'status' => 'draft',
        ]);

        $updateResponse = $this->actingAs($superAdmin, 'sanctum')
            ->putJson("/api/v1/superadmin/norms/{$norm->id}", [
                'version_code' => '2015',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $norm->refresh();
        $this->assertNotNull($norm->current_version_id);
        $this->assertDatabaseHas('norm_versions', [
            'id' => $norm->current_version_id,
            'norm_id' => $norm->id,
            'version_code' => '2015',
            'full_code' => 'ISO 9001:2015',
            'is_current' => true,
        ]);

        $chapterResponse = $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/v1/superadmin/norm-versions/{$norm->current_version_id}/sections", [
                'type' => 'chapter',
                'number' => '4',
                'title' => 'Contexte de l\'organisme',
                'content' => 'Texte du chapitre 4',
                'references' => [],
            ]);

        $chapterResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $chapterId = $chapterResponse->json('data.id');
        $this->assertNotNull($chapterId);

        $subChapterResponse = $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/v1/superadmin/norm-versions/{$norm->current_version_id}/sections", [
                'parent_id' => $chapterId,
                'type' => 'subchapter',
                'number' => '4.1',
                'title' => 'Compréhension de l\'organisme',
                'content' => 'Texte du sous-chapitre 4.1',
                'references' => [],
            ]);

        $subChapterResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('norm_sections', [
            'norm_version_id' => $norm->current_version_id,
            'type' => 'chapter',
            'number' => '4',
        ]);
        $this->assertDatabaseHas('norm_sections', [
            'norm_version_id' => $norm->current_version_id,
            'type' => 'subchapter',
            'number' => '4.1',
            'parent_id' => $chapterId,
        ]);
    }
}
