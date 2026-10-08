<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentWorkflowHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentWorkflowHistoryFactory extends Factory
{
    protected $model = DocumentWorkflowHistory::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['submitted', 'verified', 'approved', 'rejected']),
            'from_status' => 'draft',
            'to_status' => 'pending_verification',
            'comment' => $this->faker->optional()->sentence(),
            'metadata' => null,
            'delegated_to' => null,
            'action_at' => now(),
        ];
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'verified',
            'from_status' => 'pending_verification',
            'to_status' => 'pending_approval',
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'approved',
            'from_status' => 'pending_approval',
            'to_status' => 'approved',
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'rejected',
            'from_status' => 'pending_verification',
            'to_status' => 'awaiting_submitter_confirmation',
            'comment' => $this->faker->sentence(),
        ]);
    }

    public function delegated(): self
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'delegated',
            'delegated_to' => User::factory(),
            'comment' => $this->faker->sentence(),
        ]);
    }
}
