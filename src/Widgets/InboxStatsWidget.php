<?php

namespace FilamentInbox\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use FilamentInbox\Models\MessageRecipient;

class InboxStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        return [
            Stat::make('Unread Messages', MessageRecipient::query()
                ->where('recipient_id', $userId)
                ->whereNull('read_at')
                ->whereNull('deleted_at')
                ->count())
                ->icon(Heroicon::Envelope)
                ->color('primary'),

            Stat::make('Total Messages', MessageRecipient::query()
                ->where('recipient_id', $userId)
                ->whereNull('deleted_at')
                ->count())
                ->icon(Heroicon::InboxStack)
                ->color('gray'),

            Stat::make('Starred', MessageRecipient::query()
                ->where('recipient_id', $userId)
                ->whereNotNull('starred_at')
                ->whereNull('deleted_at')
                ->count())
                ->icon(Heroicon::Star)
                ->color('warning'),
        ];
    }
}
