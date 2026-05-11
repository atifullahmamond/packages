<?php

namespace Atifullahmamond\FilamentMeet;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource;
use Atifullahmamond\FilamentMeet\Filament\Widgets\MeetingStatsWidget;

class FilamentMeetPlugin implements Plugin
{
    protected bool $hasAnalyticsWidget = true;
    protected bool $hasNotifications = true;
    protected ?string $jitsiDomain = null;
    protected ?string $jitsiJwtAppId = null;
    protected ?string $jitsiJwtAppSecret = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());
        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-meet';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MeetingResource::class,
        ]);

        if ($this->hasAnalyticsWidget) {
            $panel->widgets([
                MeetingStatsWidget::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function analyticsWidget(bool $condition = true): static
    {
        $this->hasAnalyticsWidget = $condition;
        return $this;
    }

    public function notifications(bool $condition = true): static
    {
        $this->hasNotifications = $condition;
        return $this;
    }

    public function jitsiDomain(string $domain): static
    {
        $this->jitsiDomain = $domain;
        return $this;
    }

    public function jitsiJwt(string $appId, string $appSecret): static
    {
        $this->jitsiJwtAppId = $appId;
        $this->jitsiJwtAppSecret = $appSecret;
        return $this;
    }

    public function getJitsiDomain(): string
    {
        return $this->jitsiDomain ?? config('filament-meet.jitsi_domain', 'meet.jit.si');
    }

    public function getJitsiJwtAppId(): ?string
    {
        return $this->jitsiJwtAppId ?? config('filament-meet.jitsi_jwt_app_id');
    }

    public function getJitsiJwtAppSecret(): ?string
    {
        return $this->jitsiJwtAppSecret ?? config('filament-meet.jitsi_jwt_app_secret');
    }

    public function hasJwt(): bool
    {
        return $this->getJitsiJwtAppId() !== null && $this->getJitsiJwtAppSecret() !== null;
    }
}
