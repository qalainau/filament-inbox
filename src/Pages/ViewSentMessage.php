<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use FilamentInbox\Models\Message;
use Illuminate\Database\Eloquent\Collection;

class ViewSentMessage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'sent-messages/{record}';

    protected string $view = 'filament-inbox::pages.view-sent-message';

    public int $messageId;

    public ?Message $message = null;

    /** @var Collection<int, Message> */
    public Collection $threadMessages;

    public function mount(int|Message $record): void
    {
        $msg = is_int($record) ? Message::findOrFail($record) : $record;

        abort_unless($msg->sender_id === auth()->id(), 403);

        $this->messageId = $msg->id;
        $this->message = $msg->load('sender', 'recipients');

        $this->loadThreadMessages();
    }

    public function getTitle(): string
    {
        return $this->message->subject;
    }

    protected function loadThreadMessages(): void
    {
        $rootId = $this->message->thread_id ?? $this->message->id;

        $this->threadMessages = Message::query()
            ->where(function ($query) use ($rootId) {
                $query->where('id', $rootId)
                    ->orWhere('thread_id', $rootId);
            })
            ->with('sender', 'recipients', 'messageRecipients.recipient')
            ->orderBy('created_at')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('filament-inbox::messages.back_to_sent'))
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(SentMessages::getUrl()),

            Action::make('delete')
                ->label(__('filament-inbox::messages.delete'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->message->update(['sender_deleted_at' => now()]);

                    Notification::make()
                        ->title(__('filament-inbox::messages.message_removed_from_sent'))
                        ->success()
                        ->send();

                    $this->redirect(SentMessages::getUrl());
                }),
        ];
    }
}
