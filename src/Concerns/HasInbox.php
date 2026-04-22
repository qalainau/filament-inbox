<?php

namespace FilamentInbox\Concerns;

use FilamentInbox\Models\Message;
use FilamentInbox\Models\MessageRecipient;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasInbox
{
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messageRecipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class, 'recipient_id');
    }

    public function receivedMessages(): BelongsToMany
    {
        return $this->belongsToMany(Message::class, 'message_recipients', 'recipient_id', 'message_id')
            ->withPivot('read_at', 'starred_at', 'deleted_at')
            ->withTimestamps();
    }
}
