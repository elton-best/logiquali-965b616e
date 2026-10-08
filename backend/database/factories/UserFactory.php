<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory pour générer des utilisateurs de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Mot de passe par défaut pour les utilisateurs générés
     */
    protected static ?string $password;

    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        
        return [
            'ref' => 'USR-' . strtoupper(Str::random(8)),
            'name' => $firstName . ' ' . $lastName,
            'username' => strtolower($firstName . '.' . $lastName),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'photo_path' => null,
            'user_type' => fake()->randomElement(['company', 'clientb']),
            'enterprise_id' => null, // À remplir manuellement
            'site_id' => null, // À remplir manuellement
            'role' => fake()->randomElement(['admin_entreprise', 'site_manager', 'lecteur']),
            'is_active' => true,
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            if ($user->user_type === \App\Models\User::TYPE_SUPER_ADMIN) {
                try {
                    if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->exists()) {
                        $user->syncRoles(['super_admin']);
                    }
                } catch (\Throwable) {
                    // Ignore role sync errors in factories.
                }
            }
        });
    }

    /**
     * Indiquer que l'email n'est pas vérifié
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Créer un administrateur entreprise
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'company',
            'role' => 'admin_entreprise',
        ]);
    }

    /**
     * Créer un pilote de processus
     */
    public function pilot(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'company',
            'role' => 'site_manager',
        ]);
    }

    /**
     * Créer un auditeur
     */
    public function auditor(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'company',
            'role' => 'lecteur',
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'super_admin',
            'role' => 'super_admin',
        ]);
    }
}
