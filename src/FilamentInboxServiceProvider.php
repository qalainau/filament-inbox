<?php

namespace FilamentInbox;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentInboxServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-inbox';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasMigrations([
                '01_create_messages_table',
                '02_create_message_recipients_table',
            ])
            ->runsMigrations();
    }
}
