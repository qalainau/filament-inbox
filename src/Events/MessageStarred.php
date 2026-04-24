<?php

namespace FilamentInbox\Events;

use FilamentInbox\Models\MessageRecipient;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageStarred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MessageRecipient $messageRecipient,
        public bool $starred,
    ) {}
}
