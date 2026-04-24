<?php

namespace FilamentInbox\Database\Factories;

use FilamentInbox\FilamentInboxServiceProvider;
use FilamentInbox\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $userModel = FilamentInboxServiceProvider::getUserModel();

        return [
            'sender_id' => $userModel::factory(),
            'parent_id' => null,
            'thread_id' => null,
            'subject' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'sender_deleted_at' => null,
        ];
    }

    public function reply(Message $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'thread_id' => $parent->thread_id ?? $parent->id,
            'subject' => 'Re: '.preg_replace('/^(Re: )+/', '', $parent->subject),
        ]);
    }

    public function deletedBySender(): static
    {
        return $this->state(fn () => [
            'sender_deleted_at' => now(),
        ]);
    }
}
