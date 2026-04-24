<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use FilamentInbox\Events\MessageForwarded;
use FilamentInbox\Events\MessageRead;
use FilamentInbox\Events\MessageSent;
use FilamentInbox\Events\MessageStarred;
use FilamentInbox\Events\MessageTrashed;
use FilamentInbox\FilamentInboxPlugin;
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
            MessageRead::dispatch($mr->fresh());
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
            ->with('sender', 'recipients', 'messageRecipients')
            ->orderBy('created_at')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        $mr = $this->getRecipientRecord();

        return [
            Action::make('back')
                ->label(__('filament-inbox::messages.back_to_inbox'))
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(Inbox::getUrl()),

            Action::make('reply')
                ->label(__('filament-inbox::messages.reply'))
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
                ->label(__('filament-inbox::messages.reply_all'))
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

            Action::make('forward')
                ->label(__('filament-inbox::messages.forward'))
                ->icon(Heroicon::ArrowUturnRight)
                ->color('gray')
                ->modalHeading(__('filament-inbox::messages.forward_message'))
                ->schema([
                    Select::make('recipient_ids')
                        ->label(__('filament-inbox::messages.recipient_to'))
                        ->multiple()
                        ->options(fn () => FilamentInboxPlugin::getRecipientOptions())
                        ->searchable()
                        ->preload()
                        ->required(),

                    RichEditor::make('body')
                        ->fileAttachmentsDirectory('inbox-attachments')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $this->forwardMessage($data['recipient_ids'], $data['body'] ?? '');
                }),

            Action::make('star')
                ->label(fn () => $mr->fresh()->starred_at ? __('filament-inbox::messages.unstar') : __('filament-inbox::messages.star'))
                ->icon(fn () => $mr->fresh()->starred_at ? Heroicon::Star : Heroicon::OutlinedStar)
                ->color('warning')
                ->action(function () use ($mr): void {
                    $wasStarred = $mr->starred_at !== null;
                    $mr->update([
                        'starred_at' => $wasStarred ? null : now(),
                    ]);
                    MessageStarred::dispatch($mr->fresh(), ! $wasStarred);
                }),

            Action::make('trash')
                ->label(__('filament-inbox::messages.move_to_trash'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () use ($mr): void {
                    $mr->update(['deleted_at' => now()]);
                    MessageTrashed::dispatch($mr->fresh());

                    Notification::make()
                        ->title(__('filament-inbox::messages.message_trashed'))
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
            $recipientIds = $this->message->messageRecipients()
                ->pluck('recipient_id')
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
        MessageSent::dispatch($reply);

        Notification::make()
            ->title(__('filament-inbox::messages.reply_sent'))
            ->success()
            ->send();

        $this->loadThreadMessages();
    }

    protected function forwardMessage(array $recipientIds, string $additionalBody): void
    {
        $originalSender = $this->message->sender;
        $originalRecipients = $this->message->recipients->pluck('name')->join(', ');

        $forwardHeader = '<br><br>'
            .__('filament-inbox::messages.forwarded_message_header').'<br>'
            .__('filament-inbox::messages.forwarded_from', ['name' => $originalSender->name]).'<br>'
            .__('filament-inbox::messages.forwarded_date', ['date' => $this->message->created_at->format('M j, Y g:i A')]).'<br>'
            .__('filament-inbox::messages.forwarded_subject', ['subject' => $this->message->subject]).'<br>'
            .__('filament-inbox::messages.forwarded_to', ['names' => $originalRecipients]).'<br><br>'
            .$this->message->body;

        $body = $additionalBody
            ? $additionalBody.$forwardHeader
            : $forwardHeader;

        $forward = Message::create([
            'sender_id' => auth()->id(),
            'subject' => 'Fwd: '.preg_replace('/^(Fwd: )+/', '', $this->message->subject),
            'body' => $body,
        ]);

        foreach ($recipientIds as $recipientId) {
            MessageRecipient::create([
                'message_id' => $forward->id,
                'recipient_id' => $recipientId,
            ]);
        }

        $forward->notifyRecipients();
        MessageSent::dispatch($forward);
        MessageForwarded::dispatch($forward, $this->message);

        Notification::make()
            ->title(__('filament-inbox::messages.message_forwarded'))
            ->success()
            ->send();
    }
}
