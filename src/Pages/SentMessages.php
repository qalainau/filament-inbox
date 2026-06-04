<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FilamentInbox\FilamentInboxPlugin;
use FilamentInbox\Models\Message;

class SentMessages extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = null;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::PaperAirplane;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament-inbox::pages.inbox';

    public static function getNavigationLabel(): string
    {
        return __('filament-inbox::messages.sent');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-inbox::messages.navigation_group');
    }

    public function getTitle(): string
    {
        return __('filament-inbox::messages.sent');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Message::query()
                    ->where('sender_id', auth()->id())
                    ->whereNull('sender_deleted_at')
                    ->with(['recipients', 'messageRecipients'])
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('recipients_list')
                    ->label(__('filament-inbox::messages.to'))
                    ->state(fn (Message $record): string => $record->recipients->pluck('name')->join(', '))
                    ->formatStateUsing(fn (string $state, Message $record) => $record->recipients->count() > 1
                        ? FilamentInboxPlugin::renderStackedAvatarsWithNames($record->recipients)
                        : FilamentInboxPlugin::renderAvatarWithName($state, $record->recipients->first())
                    ),

                TextColumn::make('subject')
                    ->label(__('filament-inbox::messages.subject'))
                    ->searchable()
                    ->limit(60),

                TextColumn::make('read_status')
                    ->label(__('filament-inbox::messages.status'))
                    ->state(function (Message $record): string {
                        $total = $record->messageRecipients->count();
                        $read = $record->messageRecipients->whereNotNull('read_at')->count();

                        if ($read === 0) {
                            return __('filament-inbox::messages.unread');
                        }

                        if ($read === $total) {
                            return __('filament-inbox::messages.read');
                        }

                        return __('filament-inbox::messages.read_count', ['read' => $read, 'total' => $total]);
                    })
                    ->icon(function (Message $record): Heroicon {
                        $total = $record->messageRecipients->count();
                        $read = $record->messageRecipients->whereNotNull('read_at')->count();

                        if ($read === $total) {
                            return Heroicon::CheckCircle;
                        }

                        if ($read > 0) {
                            return Heroicon::OutlinedCheckCircle;
                        }

                        return Heroicon::OutlinedClock;
                    })
                    ->color(function (Message $record): string {
                        $total = $record->messageRecipients->count();
                        $read = $record->messageRecipients->whereNotNull('read_at')->count();

                        if ($read === $total) {
                            return 'success';
                        }

                        if ($read > 0) {
                            return 'warning';
                        }

                        return 'gray';
                    })
                    ->badge(),

                TextColumn::make('created_at')
                    ->label(__('filament-inbox::messages.sent_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->emptyStateHeading(__('filament-inbox::messages.empty_box'))
            ->filters([
                SelectFilter::make('recipient')
                    ->label(__('filament-inbox::messages.to'))
                    ->relationship('recipients', 'name'),
            ])
            ->recordUrl(fn (Message $record): string => ViewSentMessage::getUrl(['record' => $record->id]))
            ->recordActions([
                Action::make('delete')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament-inbox::messages.remove_from_sent'))
                    ->action(fn (Message $record) => $record->update(['sender_deleted_at' => now()])),
            ]);
    }
}
