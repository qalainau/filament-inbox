<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use FilamentInbox\Models\MessageRecipient;
use Illuminate\Database\Eloquent\Collection;

class Trash extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Trash;

    protected static string|\UnitEnum|null $navigationGroup = 'Messages';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament-inbox::pages.inbox';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MessageRecipient::query()
                    ->where('recipient_id', auth()->id())
                    ->whereNotNull('deleted_at')
                    ->with('message.sender')
            )
            ->defaultSort('deleted_at', 'desc')
            ->columns([
                TextColumn::make('message.sender.name')
                    ->label('From')
                    ->searchable(),

                TextColumn::make('message.subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('restore')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color('success')
                    ->action(fn (MessageRecipient $record) => $record->update(['deleted_at' => null])),

                Action::make('permanentlyDelete')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Permanently Delete')
                    ->modalDescription('This cannot be undone.')
                    ->action(fn (MessageRecipient $record) => $record->delete()),
            ])
            ->toolbarActions([
                BulkAction::make('restore')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each(fn (MessageRecipient $record) => $record->update(['deleted_at' => null]))),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('emptyTrash')
                ->label('Empty Trash')
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Empty Trash')
                ->modalDescription('Permanently delete all trashed messages? This cannot be undone.')
                ->action(function (): void {
                    MessageRecipient::query()
                        ->where('recipient_id', auth()->id())
                        ->whereNotNull('deleted_at')
                        ->delete();

                    Notification::make()
                        ->title('Trash emptied')
                        ->success()
                        ->send();
                }),
        ];
    }
}
