<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FilamentInbox\Models\Message;
use FilamentInbox\Models\MessageRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Inbox extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Inbox;

    protected static string|\UnitEnum|null $navigationGroup = 'Messages';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament-inbox::pages.inbox';

    public static function getNavigationBadge(): ?string
    {
        $count = MessageRecipient::query()
            ->where('recipient_id', auth()->id())
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MessageRecipient::query()
                    ->where('recipient_id', auth()->id())
                    ->whereNull('deleted_at')
                    ->with('message.sender')
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('starred')
                    ->label('')
                    ->state(fn (MessageRecipient $record): bool => $record->starred_at !== null)
                    ->boolean()
                    ->trueIcon(Heroicon::Star)
                    ->falseIcon(Heroicon::OutlinedStar)
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->action(fn (MessageRecipient $record) => $record->update([
                        'starred_at' => $record->starred_at ? null : now(),
                    ])),

                TextColumn::make('message.sender.name')
                    ->label('From')
                    ->searchable()
                    ->weight(fn (MessageRecipient $record): string => $record->read_at === null ? 'bold' : 'normal'),

                TextColumn::make('message.subject')
                    ->label('Subject')
                    ->searchable()
                    ->weight(fn (MessageRecipient $record): string => $record->read_at === null ? 'bold' : 'normal')
                    ->limit(60),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sender')
                    ->label('From')
                    ->relationship('message.sender', 'name'),

                Filter::make('is_unread')
                    ->label('Unread Only')
                    ->query(fn (Builder $query) => $query->whereNull('read_at')),

                Filter::make('is_starred')
                    ->label('Starred Only')
                    ->query(fn (Builder $query) => $query->whereNotNull('starred_at')),
            ])
            ->recordActions([
                Action::make('markAsRead')
                    ->icon(Heroicon::EnvelopeOpen)
                    ->color('gray')
                    ->action(function (MessageRecipient $record): void {
                        if ($record->read_at === null) {
                            $record->update(['read_at' => now()]);
                        }
                    }),

                Action::make('moveToTrash')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (MessageRecipient $record) => $record->update(['deleted_at' => now()])),
            ])
            ->toolbarActions([
                BulkAction::make('markAsRead')
                    ->icon(Heroicon::EnvelopeOpen)
                    ->action(fn (Collection $records) => $records->each(fn (MessageRecipient $record) => $record->update(['read_at' => now()]))),

                BulkAction::make('moveToTrash')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => $records->each(fn (MessageRecipient $record) => $record->update(['deleted_at' => now()]))),
            ])
            ->recordUrl(fn (MessageRecipient $record): string => ViewMessage::getUrl(['record' => $record->id]));
    }

    protected function getHeaderActions(): array
    {
        $userModel = config('auth.providers.users.model');

        return [
            Action::make('compose')
                ->label('Compose')
                ->icon(Heroicon::PencilSquare)
                ->color('primary')
                ->modalHeading('Compose Message')
                ->schema([
                    Select::make('recipient_ids')
                        ->label('To')
                        ->multiple()
                        ->options(fn () => $userModel::where('id', '!=', auth()->id())->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('subject')
                        ->required()
                        ->maxLength(255),

                    RichEditor::make('body')
                        ->required()
                        ->fileAttachmentsDirectory('inbox-attachments')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $message = Message::create([
                        'sender_id' => auth()->id(),
                        'subject' => $data['subject'],
                        'body' => $data['body'],
                    ]);

                    foreach ($data['recipient_ids'] as $recipientId) {
                        MessageRecipient::create([
                            'message_id' => $message->id,
                            'recipient_id' => $recipientId,
                        ]);
                    }

                    $message->notifyRecipients();

                    Notification::make()
                        ->title('Message sent successfully')
                        ->success()
                        ->send();
                }),
        ];
    }
}
