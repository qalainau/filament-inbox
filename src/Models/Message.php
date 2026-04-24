<?php

namespace FilamentInbox\Models;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use FilamentInbox\Database\Factories\MessageFactory;
use FilamentInbox\FilamentInboxServiceProvider;
use FilamentInbox\Pages\Inbox;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected $table = 'inbox_messages';

    protected $fillable = [
        'sender_id',
        'parent_id',
        'thread_id',
        'subject',
        'body',
        'sender_deleted_at',
        'tenant_id',
        'tenant_type',
    ];

    protected function casts(): array
    {
        return [
            'sender_deleted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

    public function tenant(): MorphTo
    {
        return $this->morphTo();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(FilamentInboxServiceProvider::getUserModel(), 'sender_id');
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
        return $this->belongsToMany(FilamentInboxServiceProvider::getUserModel(), 'inbox_message_recipients', 'message_id', 'recipient_id')
            ->withPivot('read_at', 'starred_at', 'deleted_at')
            ->withTimestamps();
    }

    public function notifyRecipients(): void
    {
        $sender = $this->sender ?? $this->belongsTo(FilamentInboxServiceProvider::getUserModel(), 'sender_id')->first();
        $isReply = $this->parent_id !== null;

        $title = $isReply
            ? __('filament-inbox::messages.replied', ['name' => $sender->name, 'subject' => $this->subject])
            : __('filament-inbox::messages.new_message_from', ['name' => $sender->name]);

        foreach ($this->messageRecipients()->with('recipient')->get() as $mr) {
            Notification::make()
                ->title($title)
                ->body(Str::limit(strip_tags($this->body), 80))
                ->icon($isReply ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-envelope')
                ->actions([
                    Action::make('view')
                        ->label(__('filament-inbox::messages.inbox'))
                        ->url(Inbox::getUrl())
                        ->markAsRead(),
                ])
                ->sendToDatabase($mr->recipient);
        }
    }
}
