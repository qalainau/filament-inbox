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
use FilamentInbox\Events\MessageRestored;
use FilamentInbox\FilamentInboxPlugin;
use FilamentInbox\Models\MessageRecipient;
use Illuminate\Database\Eloquent\Collection;

class Trash extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Trash;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament-inbox::pages.inbox';

    public static function getNavigationLabel(): string
    {
        return __('filament-inbox::messages.trash');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-inbox::messages.navigation_group');
    }

    public function getTitle(): string
    {
        return __('filament-inbox::messages.trash');
    }

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
                    ->label(__('filament-inbox::messages.from'))
                    ->searchable()
                    ->formatStateUsing(fn (string $state, MessageRecipient $record) => FilamentInboxPlugin::renderAvatarWithName($state, $record->message->sender)),

                TextColumn::make('message.subject')
                    ->label(__('filament-inbox::messages.subject'))
                    ->searchable()
                    ->limit(60),

                TextColumn::make('deleted_at')
                    ->label(__('filament-inbox::messages.deleted_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('restore')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color('success')
                    ->action(function (MessageRecipient $record): void {
                        $record->update(['deleted_at' => null]);
                        MessageRestored::dispatch($record->fresh());
                    }),

                Action::make('permanentlyDelete')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament-inbox::messages.delete_permanently'))
                    ->action(fn (MessageRecipient $record) => $record->delete()),
            ])
            ->toolbarActions([
                BulkAction::make('restore')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each(function (MessageRecipient $record): void {
                        $record->update(['deleted_at' => null]);
                        MessageRestored::dispatch($record->fresh());
                    })),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('emptyTrash')
                ->label(__('filament-inbox::messages.empty_trash'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('filament-inbox::messages.empty_trash'))
                ->action(function (): void {
                    MessageRecipient::query()
                        ->where('recipient_id', auth()->id())
                        ->whereNotNull('deleted_at')
                        ->delete();

                    Notification::make()
                        ->title(__('filament-inbox::messages.trash_emptied'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
