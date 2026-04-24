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
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

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

        return $query->get()->mapWithKeys(function ($user) {
            $avatar = static::renderAvatar($user);
            $name = e($user->name);
            $email = e($user->email);

            $html = '<div style="display:flex;align-items:center;gap:0.75rem;">'
                .$avatar
                .'<div style="display:flex;flex-direction:column;">'
                .'<span style="font-size:0.875rem;font-weight:500;">'.$name.'</span>'
                .'<span style="font-size:0.75rem;color:#6b7280;">'.$email.'</span>'
                .'</div></div>';

            return [$user->id => $html];
        });
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

    /**
     * Render an avatar HTML element for a user.
     */
    public static function renderAvatar(Model $user, int $size = 32): string
    {
        $avatarUrl = null;

        if ($user instanceof HasAvatar) {
            $avatarUrl = $user->getFilamentAvatarUrl();
        } elseif (isset($user->avatar_url)) {
            $avatarUrl = $user->avatar_url;
        }

        if (! $avatarUrl) {
            $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=7F9CF5&background=EBF4FF&size='.$size;
        }

        return '<img src="'.e($avatarUrl).'" alt="'.e($user->name).'" style="width:'.$size.'px;height:'.$size.'px;border-radius:9999px;object-fit:cover;flex-shrink:0;" />';
    }

    /**
     * Render an avatar + name HTML element for a user name, using the sender model.
     */
    public static function renderAvatarWithName(string $name, ?Model $user = null): HtmlString
    {
        $avatar = $user
            ? static::renderAvatar($user)
            : static::renderInitialsAvatar($name);

        return new HtmlString(
            '<div style="display:flex;align-items:center;gap:0.5rem;">'
            .$avatar
            .'<span>'.e($name).'</span>'
            .'</div>'
        );
    }

    /**
     * Render an initials-only avatar (no user model needed).
     */
    public static function renderInitialsAvatar(string $name, int $size = 32): string
    {
        $url = 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF&size='.$size;

        return '<img src="'.e($url).'" alt="'.e($name).'" style="width:'.$size.'px;height:'.$size.'px;border-radius:9999px;object-fit:cover;flex-shrink:0;" />';
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
