<?php

namespace FilamentInbox\Events;

use FilamentInbox\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageForwarded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Message $forwardedMessage,
        public Message $originalMessage,
    ) {}
}
