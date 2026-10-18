<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Contraseña por defecto cacheada para agilizar la generación en masa.
     */
    protected static ?string $password;

    /**
     * Definición del estado base del usuario con campos de Artesa en español.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->boolean(80) ? fake()->numerify('6########') : null,
            'avatar' => null,
            'telegram_chat_id' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
        ];
    }

    /**
     * Estado para usuario sin verificar email.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Estado para el Super Administrador del marketplace.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Administrador Artesa',
            'email' => 'admin@artesa.local',
        ]);
    }

    /**
     * Estado para el Moderador de contenidos y disputas.
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Moderador Artesa',
            'email' => 'moderador@artesa.local',
        ]);
    }

    /**
     * Estado para artesano titular de taller.
     */
    public function artisan(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => fake()->numerify('6########'),
            'telegram_chat_id' => (string) fake()->numberBetween(100000000, 999999999),
        ]);
    }

    /**
     * Compatibilidad con equipos de Jetstream si están activos.
     */
    public function withPersonalTeam(?callable $callback = null): static
    {
        if (! Features::hasTeamFeatures()) {
            return $this->state([]);
        }

        return $this->has(
            Team::factory()
                ->state(fn (array $attributes, User $user) => [
                    'name' => $user->name.'\'s Team',
                    'user_id' => $user->id,
                    'personal_team' => true,
                ])
                ->when(is_callable($callback), $callback),
            'ownedTeams'
        );
    }
}
