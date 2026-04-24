<?php

namespace FilamentInbox\Events;

use FilamentInbox\Models\MessageRecipient;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead
{
    use Dispatchable, SerializesModels;

    public function __construct(public MessageRecipient $messageRecipient) {}
}
