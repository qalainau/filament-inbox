<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use FilamentInbox\Models\MessageRecipient;

class StarredMessages extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Starred';

    protected static ?string $navigationLabel = 'Starred';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Star;

    protected static string|\UnitEnum|null $navigationGroup = 'Messages';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament-inbox::pages.inbox';

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
                    ->label('From')
                    ->searchable(),

                TextColumn::make('message.subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('starred_at')
                    ->label('Starred At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('unstar')
                    ->icon(Heroicon::Star)
                    ->color('warning')
                    ->action(fn (MessageRecipient $record) => $record->update(['starred_at' => null])),

                Action::make('moveToTrash')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (MessageRecipient $record) => $record->update(['deleted_at' => now()])),
            ])
            ->recordUrl(fn (MessageRecipient $record): string => ViewMessage::getUrl(['record' => $record->id]));
    }
}
