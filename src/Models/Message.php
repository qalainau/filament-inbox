<?php

namespace FilamentInbox\Models;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use FilamentInbox\Pages\Inbox;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    /** @use HasFactory<\FilamentInbox\Database\Factories\MessageFactory> */
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'parent_id',
        'thread_id',
        'subject',
        'body',
        'sender_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'sender_deleted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): \FilamentInbox\Database\Factories\MessageFactory
    {
        return \FilamentInbox\Database\Factories\MessageFactory::new();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'sender_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(self::class, 'thread_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function threadMessages(): HasMany
    {
        return $this->hasMany(self::class, 'thread_id');
    }

    public function messageRecipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model'), 'message_recipients', 'message_id', 'recipient_id')
            ->withPivot('read_at', 'starred_at', 'deleted_at')
            ->withTimestamps();
    }

    public function notifyRecipients(): void
    {
        $sender = $this->sender ?? $this->belongsTo(config('auth.providers.users.model'), 'sender_id')->first();
        $isReply = $this->parent_id !== null;

        $title = $isReply
            ? "{$sender->name} replied: {$this->subject}"
            : "New message from {$sender->name}";

        foreach ($this->messageRecipients()->with('recipient')->get() as $mr) {
            Notification::make()
                ->title($title)
                ->body(\Illuminate\Support\Str::limit(strip_tags($this->body), 80))
                ->icon($isReply ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-envelope')
                ->actions([
                    Action::make('view')
                        ->label('View')
                        ->url(Inbox::getUrl())
                        ->markAsRead(),
                ])
                ->sendToDatabase($mr->recipient);
        }
    }
}
