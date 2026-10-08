<?php

use Tests\TestCase;
use Tests\Traits\WithPermissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

$test = new class extends TestCase {
    use RefreshDatabase, WithPermissions;
    
    public function test() {
        $this->setUp();
        $admin = User::factory()->create();
        $this->grantAllPermissions();
        
        $site = \App\Models\Site::factory()->create();
        $process = \App\Models\Process::factory()->create();
        $author = User::factory()->create();
        
        $data = [
            'site_id' => $site->id,
            'process_id' => $process->id,
            'title' => 'Procédure Test',
            'code' => 'PROC-001',
            'type' => 'procedure',
            'status' => 'draft',
            'author_id' => $author->id,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/documents', $data);

        echo "Status: " . $response->status() . "\n";
        echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
        echo "Ref: " . $response->json('data.attributes.ref') . "\n";
    }
};

$test->test();
