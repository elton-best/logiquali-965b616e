<?php

namespace Database\Factories;

use App\Models\Enterprise;
use App\Models\JobDescription;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobDescriptionFactory extends Factory
{
    protected $model = JobDescription::class;

    public function definition(): array
    {
        return [
            'enterprise_id' => Enterprise::factory(),
            'site_id' => Site::factory(),
            'job_title' => $this->faker->jobTitle(),
            'department' => $this->faker->word(),
            'mission' => $this->faker->paragraph(),
            'activities' => $this->faker->paragraph(),
            'required_skills' => [$this->faker->word(), $this->faker->word()],
            'required_experience' => $this->faker->sentence(),
            'required_education' => $this->faker->sentence(),
        ];
    }
}