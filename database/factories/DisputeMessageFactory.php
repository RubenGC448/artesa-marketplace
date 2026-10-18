<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisputeMessage>
 */
class DisputeMessageFactory extends Factory
{
    protected $model = DisputeMessage::class;

    public function definition(): array
    {
        return [
            'dispute_id' => Dispute::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
            'attachment' => null,
        ];
    }
}
