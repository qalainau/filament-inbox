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
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '01_create_messages_table',
                '02_create_message_recipients_table',
                '04_add_tenant_to_messages_table',
            ])
            ->runsMigrations();
    }

    /**
     * Resolve the configured user model class.
     */
    public static function getUserModel(): string
    {
        return config('filament-inbox.user_model')
            ?? config('auth.providers.users.model');
    }

    /**
     * Resolve the configured users table name.
     */
    public static function getUsersTable(): string
    {
        if ($table = config('filament-inbox.users_table')) {
            return $table;
        }

        return (new (static::getUserModel()))->getTable();
    }
}
