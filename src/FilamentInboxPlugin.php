<?php

namespace FilamentInbox;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use FilamentInbox\Models\Message;
use FilamentInbox\Pages\Inbox;
use FilamentInbox\Pages\SentMessages;
use FilamentInbox\Pages\StarredMessages;
use FilamentInbox\Pages\Trash;
use FilamentInbox\Pages\ViewMessage;
use FilamentInbox\Pages\ViewSentMessage;
use FilamentInbox\Widgets\InboxStatsWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
        if (Filament::hasTenancy()) {
            $this->registerTenantScopes();
            $this->registerTenantObserver();
        }
    }

    /**
     * Get available recipients for the current user, scoped by tenant if active.
     *
     * @return Collection<int|string, string>
     */
    public static function getRecipientOptions(): Collection
    {
        $userModel = FilamentInboxServiceProvider::getUserModel();

        $query = $userModel::where('id', '!=', auth()->id());

        if (Filament::hasTenancy() && ($tenant = Filament::getTenant())) {
            $userIds = static::getTenantUserIds($tenant);

            if ($userIds !== null) {
                $query->whereIn('id', $userIds);
            }
        }

        return $query->pluck('name', 'id');
    }

    /**
     * Get user IDs belonging to a tenant.
     *
     * @return \Illuminate\Support\Collection<int, mixed>|null
     */
    protected static function getTenantUserIds(Model $tenant): ?Collection
    {
        $relationship = config('filament-inbox.tenant_users_relationship');

        if ($relationship && method_exists($tenant, $relationship)) {
            return $tenant->{$relationship}()->pluck('id');
        }

        foreach (['members', 'users'] as $method) {
            if (method_exists($tenant, $method)) {
                return $tenant->{$method}()->pluck('id');
            }
        }

        return null;
    }

    protected function registerTenantScopes(): void
    {
        Message::addGlobalScope('tenant', function (Builder $query) {
            $tenant = Filament::getTenant();

            if ($tenant) {
                $query->where('tenant_type', $tenant->getMorphClass())
                    ->where('tenant_id', $tenant->getKey());
            }
        });
    }

    protected function registerTenantObserver(): void
    {
        Message::creating(function (Message $message) {
            $tenant = Filament::getTenant();

            if ($tenant && ! $message->tenant_id) {
                $message->tenant_id = $tenant->getKey();
                $message->tenant_type = $tenant->getMorphClass();
            }
        });
    }
}
