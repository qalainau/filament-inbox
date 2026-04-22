<?php

namespace FilamentInbox\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRecipient extends Model
{
    /** @use HasFactory<\FilamentInbox\Database\Factories\MessageRecipientFactory> */
    use HasFactory;

    protected $fillable = [
        'message_id',
        'recipient_id',
        'read_at',
        'starred_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'starred_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): \FilamentInbox\Database\Factories\MessageRecipientFactory
    {
        return \FilamentInbox\Database\Factories\MessageRecipientFactory::new();
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'recipient_id');
    }
}
