<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use FilamentInbox\Models\Message;
use FilamentInbox\Models\MessageRecipient;
use Illuminate\Database\Eloquent\Collection;

class ViewMessage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'messages/{record}';

    protected string $view = 'filament-inbox::pages.view-message';

    public int $recipientRecordId;

    public ?Message $message = null;

    /** @var Collection<int, Message> */
    public Collection $threadMessages;

    protected ?MessageRecipient $recipientRecord = null;

    public function mount(int|MessageRecipient $record): void
    {
        $mr = is_int($record) ? MessageRecipient::findOrFail($record) : $record;

        abort_unless($mr->recipient_id === auth()->id(), 403);

        $this->recipientRecordId = $mr->id;
        $this->recipientRecord = $mr;
        $this->message = $mr->message->load('sender', 'recipients');

        if ($mr->read_at === null) {
            $mr->update(['read_at' => now()]);
        }

        $this->loadThreadMessages();
    }

    public function getTitle(): string
    {
        return $this->message->subject;
    }

    public function getRecipientRecord(): MessageRecipient
    {
        return $this->recipientRecord ??= MessageRecipient::findOrFail($this->recipientRecordId);
    }

    protected function loadThreadMessages(): void
    {
        $rootId = $this->message->thread_id ?? $this->message->id;

        $this->threadMessages = Message::query()
            ->where(function ($query) use ($rootId) {
                $query->where('id', $rootId)
                    ->orWhere('thread_id', $rootId);
            })
            ->with('sender', 'recipients')
            ->orderBy('created_at')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        $mr = $this->getRecipientRecord();

        return [
            Action::make('back')
                ->label('Back to Inbox')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(Inbox::getUrl()),

            Action::make('reply')
                ->label('Reply')
                ->icon(Heroicon::ArrowUturnLeft)
                ->color('primary')
                ->schema([
                    RichEditor::make('body')
                        ->required()
                        ->fileAttachmentsDirectory('inbox-attachments')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $this->sendReply($data['body'], replyAll: false);
                }),

            Action::make('replyAll')
                ->label('Reply All')
                ->icon(Heroicon::ArrowUturnLeft)
                ->color('gray')
                ->visible(fn (): bool => $this->message->recipients->count() > 1)
                ->schema([
                    RichEditor::make('body')
                        ->required()
                        ->fileAttachmentsDirectory('inbox-attachments')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $this->sendReply($data['body'], replyAll: true);
                }),

            Action::make('star')
                ->label(fn () => $mr->fresh()->starred_at ? 'Unstar' : 'Star')
                ->icon(fn () => $mr->fresh()->starred_at ? Heroicon::Star : Heroicon::OutlinedStar)
                ->color('warning')
                ->action(function () use ($mr): void {
                    $mr->update([
                        'starred_at' => $mr->starred_at ? null : now(),
                    ]);
                }),

            Action::make('trash')
                ->label('Trash')
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () use ($mr): void {
                    $mr->update(['deleted_at' => now()]);

                    Notification::make()
                        ->title('Message moved to trash')
                        ->success()
                        ->send();

                    $this->redirect(Inbox::getUrl());
                }),
        ];
    }

    protected function sendReply(string $body, bool $replyAll): void
    {
        $subject = preg_replace('/^(Re: )+/', 'Re: ', 'Re: '.$this->message->subject);

        $reply = Message::create([
            'sender_id' => auth()->id(),
            'parent_id' => $this->message->id,
            'thread_id' => $this->message->thread_id ?? $this->message->id,
            'subject' => $subject,
            'body' => $body,
        ]);

        if ($replyAll) {
            $recipientIds = $this->message->recipients
                ->pluck('id')
                ->push($this->message->sender_id)
                ->unique()
                ->reject(fn ($id) => $id === auth()->id());
        } else {
            $recipientIds = collect([$this->message->sender_id]);
        }

        foreach ($recipientIds as $recipientId) {
            MessageRecipient::create([
                'message_id' => $reply->id,
                'recipient_id' => $recipientId,
            ]);
        }

        $reply->notifyRecipients();

        Notification::make()
            ->title('Reply sent')
            ->success()
            ->send();

        $this->loadThreadMessages();
    }
}
