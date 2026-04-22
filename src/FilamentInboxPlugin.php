<?php

namespace FilamentInbox;

use Filament\Contracts\Plugin;
use Filament\Panel;
use FilamentInbox\Pages\Inbox;
use FilamentInbox\Pages\SentMessages;
use FilamentInbox\Pages\StarredMessages;
use FilamentInbox\Pages\Trash;
use FilamentInbox\Pages\ViewMessage;
use FilamentInbox\Pages\ViewSentMessage;
use FilamentInbox\Widgets\InboxStatsWidget;

class FilamentInboxPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-inbox';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                Inbox::class,
                ViewMessage::class,
                SentMessages::class,
                StarredMessages::class,
                Trash::class,
                ViewSentMessage::class,
            ])
            ->widgets([
                InboxStatsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
