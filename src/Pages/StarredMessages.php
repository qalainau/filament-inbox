<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use FilamentInbox\Events\MessageStarred;
use FilamentInbox\Events\MessageTrashed;
use FilamentInbox\Models\MessageRecipient;

class StarredMessages extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = null;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Star;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament-inbox::pages.inbox';

    public static function getNavigationLabel(): string
    {
        return __('filament-inbox::messages.starred');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-inbox::messages.navigation_group');
    }

    public function getTitle(): string
    {
        return __('filament-inbox::messages.starred');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MessageRecipient::query()
                    ->where('recipient_id', auth()->id())
                    ->whereNull('deleted_at')
                    ->whereNotNull('starred_at')
                    ->with('message.sender')
            )
            ->defaultSort('starred_at', 'desc')
            ->columns([
                TextColumn::make('message.sender.name')
                    ->label(__('filament-inbox::messages.from'))
                    ->searchable(),

                TextColumn::make('message.subject')
                    ->label(__('filament-inbox::messages.subject'))
                    ->searchable()
                    ->limit(60),

                TextColumn::make('starred_at')
                    ->label(__('filament-inbox::messages.starred_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('unstar')
                    ->icon(Heroicon::Star)
                    ->color('warning')
                    ->action(function (MessageRecipient $record): void {
                        $record->update(['starred_at' => null]);
                        MessageStarred::dispatch($record->fresh(), false);
                    }),

                Action::make('moveToTrash')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (MessageRecipient $record): void {
                        $record->update(['deleted_at' => now()]);
                        MessageTrashed::dispatch($record->fresh());
                    }),
            ])
            ->recordUrl(fn (MessageRecipient $record): string => ViewMessage::getUrl(['record' => $record->id]));
    }
}
