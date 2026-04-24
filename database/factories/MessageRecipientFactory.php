<?php

namespace FilamentInbox\Database\Factories;

use FilamentInbox\Models\Message;
use FilamentInbox\Models\MessageRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageRecipient>
 */
class MessageRecipientFactory extends Factory
{
    protected $model = MessageRecipient::class;

    public function definition(): array
    {
        $userModel = \FilamentInbox\FilamentInboxServiceProvider::getUserModel();

        return [
            'message_id' => Message::factory(),
            'recipient_id' => $userModel::factory(),
            'read_at' => null,
            'starred_at' => null,
            'deleted_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    public function starred(): static
    {
        return $this->state(fn () => ['starred_at' => now()]);
    }

    public function trashed(): static
    {
        return $this->state(fn () => ['deleted_at' => now()]);
    }
}
