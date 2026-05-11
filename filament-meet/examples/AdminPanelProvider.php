<?php

/**
 * ============================================================================
 * Register FilamentMeetPlugin here (required for CRUD routes)
 * ============================================================================
 *
 * Filament registers resources when its route file loads (before Laravel’s
 * `booted()` callbacks finish). Attach the plugin in panel() via ->plugins(...)
 * so URLs like filament.{panel}.resources.meetings.* exist.
 */

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServletEvents;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Atifullahmamond\FilamentMeet\FilamentMeetPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Violet,
            ])

            // ----------------------------------------------------------------
            // Register filament-meet
            // ----------------------------------------------------------------
            ->plugins([

                FilamentMeetPlugin::make()

                    // Optional: use your own Jitsi server instead of meet.jit.si
                    // ->jitsiDomain('meet.yourdomain.com')

                    // Optional: enable JWT for private Jitsi servers
                    // ->jitsiJwt(
                    //     appId:     env('JITSI_JWT_APP_ID'),
                    //     appSecret: env('JITSI_JWT_APP_SECRET')
                    // )

                    // Optional: disable the stats widget (default: enabled)
                    // ->analyticsWidget(false)

                    // Optional: disable invitation notifications (default: enabled)
                    // ->notifications(false)
                ,

            ])

            // ----------------------------------------------------------------
            // Standard Filament panel setup
            // ----------------------------------------------------------------
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServletEvents::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

/**
 * ============================================================================
 * REQUIRED .env additions
 * ============================================================================
 *
 * # Jitsi (public server — no changes needed for meet.jit.si)
 * JITSI_DOMAIN=meet.jit.si
 *
 * # Only needed for private Jitsi with JWT:
 * JITSI_JWT_APP_ID=your_app_id
 * JITSI_JWT_APP_SECRET=your_app_secret
 *
 * # Broadcasting (Reverb or Pusher)
 * BROADCAST_DRIVER=reverb
 * REVERB_APP_ID=your_reverb_app_id
 * REVERB_APP_KEY=your_reverb_app_key
 * REVERB_APP_SECRET=your_reverb_app_secret
 *
 * # Optional features
 * FILAMENT_MEET_BROADCASTING=true
 * FILAMENT_MEET_AI_SUMMARY=false
 *
 * ============================================================================
 * INSTALLATION STEPS
 * ============================================================================
 *
 * 1. Add the plugin to your composer.json and run:
 *       composer require atifullahmamond/filament-meet
 *
 * 2. Publish assets and migrate:
 *       php artisan filament-meet:install
 *       php artisan migrate
 *
 * 3. (Optional) Publish views to customize:
 *       php artisan vendor:publish --tag=filament-meet-views
 *
 * 4. Register FilamentMeetPlugin in your Panel Provider (see above).
 *
 * 5. Livewire v4: the package registers `filament-meet::meeting-room` via
 *    Livewire::addNamespace('filament-meet', classNamespace: 'Atifullahmamond\\FilamentMeet\\Livewire')
 *    in FilamentMeetServiceProvider (do not use Livewire::component with `::` in the name).
 *
 * 6. Ensure your User model uses Notifiable trait for notifications.
 *
 * 7. Configure broadcasting (Reverb recommended for Laravel 11+):
 *       php artisan install:broadcasting
 *
 * ============================================================================
 */
