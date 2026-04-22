<?php

namespace FilamentInbox\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FilamentInbox\Models\Message;

class SentMessages extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Sent';

    protected static ?string $navigationLabel = 'Sent';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::PaperAirplane;

    protected static string|\UnitEnum|null $navigationGroup = 'Messages';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament-inbox::pages.inbox';

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
                    ->label('To')
                    ->state(fn (Message $record): string => $record->recipients->pluck('name')->join(', '))
                    ->limit(40),

                TextColumn::make('subject')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('read_status')
                    ->label('Status')
                    ->state(function (Message $record): string {
                        $total = $record->messageRecipients->count();
                        $read = $record->messageRecipients->whereNotNull('read_at')->count();

                        if ($read === 0) {
                            return 'Unread';
                        }

                        if ($read === $total) {
                            return 'Read';
                        }

                        return "Read {$read}/{$total}";
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
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('recipient')
                    ->label('To')
                    ->relationship('recipients', 'name'),
            ])
            ->recordUrl(fn (Message $record): string => ViewSentMessage::getUrl(['record' => $record->id]))
            ->recordActions([
                Action::make('delete')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove from Sent')
                    ->action(fn (Message $record) => $record->update(['sender_deleted_at' => now()])),
            ]);
    }
}
