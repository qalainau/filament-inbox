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
            Stat::make(__('filament-inbox::messages.unread_messages'), MessageRecipient::query()
                ->where('recipient_id', $userId)
                ->whereNull('read_at')
                ->whereNull('deleted_at')
                ->count())
                ->icon(Heroicon::Envelope)
                ->color('primary'),

            Stat::make(__('filament-inbox::messages.total_messages'), MessageRecipient::query()
                ->where('recipient_id', $userId)
                ->whereNull('deleted_at')
                ->count())
                ->icon(Heroicon::InboxStack)
                ->color('gray'),

            Stat::make(__('filament-inbox::messages.starred'), MessageRecipient::query()
                ->where('recipient_id', $userId)
                ->whereNotNull('starred_at')
                ->whereNull('deleted_at')
                ->count())
                ->icon(Heroicon::Star)
                ->color('warning'),
        ];
    }
}
