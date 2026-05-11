# filament-meet

Real-time video meetings (Jitsi Meet) inside **Filament v5** — similar to Google Meet in your admin panel.

**Browse on GitHub:** [atifullahmamond/packages → `filament-meet`](https://github.com/atifullahmamond/packages/tree/main/filament-meet)  
The package sits in a **monorepo** folder (not the repository root), so follow the steps below instead of pasting the monorepo URL straight into Composer.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.2 |
| Laravel | ^11 \| ^12 \| ^13 |
| Filament | ^5.0 |
| Livewire | ^4.0 |
| Node + npm | For **`npm install` / `npm run build`** (meeting room Vite bundles) |

---

## Installation

This code lives under **`filament-meet/`** in the **[packages](https://github.com/atifullahmamond/packages)** monorepo. Pick **one** Composer method below.

### Method A — Install from GitHub online (ZIP, no clone)

Composer cannot depend on a monorepo subfolder via a plain `vcs` URL. Use an inline **`package`** repository that pulls the **`main`** branch ZIP, plus a dev constraint:

Add to **`composer.json`**:

```json
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "atifullahmamond/filament-meet",
            "version": "dev-main",
            "dist": {
                "type": "zip",
                "url": "https://github.com/atifullahmamond/packages/archive/refs/heads/main.zip"
            },
            "autoload": {
                "psr-4": {
                    "Atifullahmamond\\FilamentMeet\\": "filament-meet/src"
                }
            },
            "require": {
                "php": "^8.2",
                "filament/filament": "^5.0",
                "laravel/framework": "^11.0|^12.0|^13.0",
                "livewire/livewire": "^4.0"
            },
            "extra": {
                "laravel": {
                    "providers": [
                        "Atifullahmamond\\FilamentMeet\\FilamentMeetServiceProvider"
                    ]
                }
            }
        }
    }
],
"require": {
    "atifullahmamond/filament-meet": "dev-main@dev"
}
```

`dev-main@dev` allows this ZIP source without forcing `"minimum-stability": "dev"` on your whole app.

```bash
composer update atifullahmamond/filament-meet
```

**Fork / branch:** change `main` in the **`url`** to your branch (`.../archive/refs/heads/<branch>.zip`). For tagged releases use `.../archive/refs/tags/<tag>.zip` and set `"version"` in the snippet to match (e.g. `1.0.0`) so Composer can upgrade predictably.

Laravel discovers **`FilamentMeetServiceProvider`** from `extra` for routes, migrations, Livewire, and config. **`FilamentMeetPlugin` still must be added in your Panel provider** (below) — Filament builds resource URLs when the route file loads, which is **before** `app()->booted()`, so attaching the plugin only in a ServiceProvider **`boot`** hook leaves routes like **`filament.admin.resources.meetings.index` undefined**.

---

### Filament Panel — register the plugin (required)

In your **`PanelProvider`** (usually `AdminPanelProvider`):

```php
use Atifullahmamond\FilamentMeet\FilamentMeetPlugin;

return $panel
    // ...
    ->plugins([
        FilamentMeetPlugin::make(),
    ]);
```

Options (JWT domain, widgets, etc.) remain on **`FilamentMeetPlugin::make()`**. See **`examples/AdminPanelProvider.php`**.

### Method B — Clone + path repository

```bash
git clone https://github.com/atifullahmamond/packages.git
```

Then in `composer.json` point a **`path`** repo at **`.../packages/filament-meet`** (the folder that contains this package’s `composer.json`). Run `composer update atifullahmamond/filament-meet`.

### Method C — Packagist (when published)

```bash
composer require atifullahmamond/filament-meet
```

(No custom `repositories` block.) Until the package appears on Packagist, **`composer require` will fail** — use **Method A** instead.

---

### Step 4 — Publish & migrate

One command publishes **config + migrations** (you can skip this and rely on bundled defaults / loaded migrations):

```bash
php artisan filament-meet:install
php artisan migrate
```

**Prefer raw publish tags?**

```bash
php artisan vendor:publish --tag=filament-meet-migrations
php artisan vendor:publish --tag=filament-meet-config   # optional
php artisan migrate
```

**Skip publish entirely:** run `php artisan migrate` — migrations ship with the package and are loaded automatically.

---

### Step 4b — Frontend assets (Tailwind / Vite)

The meeting room Blade file uses **Tailwind utility classes**. Your app’s CSS build must **see** those class names, or the layout (and Jitsi iframe height) will look broken.

In **`resources/css/app.css`** (Tailwind v4), add a **`@source`** line so Tailwind scans this package’s Blade files.

**Normal install** (package only under `vendor/`):

```css
@source '../../vendor/atifullahmamond/filament-meet/resources/**/*.blade.php';
```

**Same repo as your app** (e.g. `packages/filament-meet` next to `app/`), use that path instead (or in addition):

```css
@source '../../packages/filament-meet/resources/**/*.blade.php';
```

Then rebuild assets:

```bash
npm run build
# or during development:
npm run dev
```

The package also ships **minimal fallback CSS** in the meeting layout (`.meet-app`, `#jitsi-container`, etc.) so Jitsi can mount even without every utility — but you should still add `@source` and run **`npm run build`** for the full UI.

---

### Step 5 — Configuration (optional)

Unless you change Jitsi, routes, or broadcasting, you **do not need** a published config: `config/filament-meet.php` is merged by the ServiceProvider using `.env`.

When you tune settings (after `filament-meet:install` or `vendor:publish --tag=filament-meet-config`):

| Variable | Purpose |
|----------|---------|
| `JITSI_DOMAIN` | Jitsi host (default `meet.jit.si`) |
| `JITSI_JWT_APP_ID` / `JITSI_JWT_APP_SECRET` | Only for **your own** Jitsi with JWT auth |
| `FILAMENT_MEET_ROUTE_PREFIX` | URL prefix for the meeting room (default `meet`) |
| `FILAMENT_MEET_BROADCASTING` | `true`/`false` for Laravel broadcast events |
| `FILAMENT_MEET_OPEN_JOIN` | Default `true`: any authenticated user may join scheduled/active meetings via `/meet/room/{uuid}`; set `false` for invite-only (host + synced participants only) |

**Who can create meetings:** only users the policy treats as admins — see **Step 7** (Spatie **`admin`** role and/or **`is_admin`**).

**Public `meet.jit.si` (default):** Jitsi shows a banner such as “embedding … is only meant for demo … disconnect in 5 minutes” — that policy comes from **their** hosting, not from this plugin. You cannot remove it while still using **`meet.jit.si`** for iframe embedding. For real use, point **`JITSI_DOMAIN`** at **your own Jitsi Meet deployment** ([self-host](https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-quickstart)) or **[Jitsi as a Service (8x8 Jaas)](https://jaas.8x8.vc)** and set JWT if your deployment requires it.

**Optional — override views:**

```bash
php artisan vendor:publish --tag=filament-meet-views
```

---

### Step 6 — Broadcasting (presence channel for live participant list)

Realtime updates use Laravel Echo on the **`presence` channel** named `meeting.{meeting.uuid}`.

In **`routes/channels.php`** add authorization (adjust `User` if your model differs):

```php
use Illuminate\Support\Facades\Broadcast;
use Atifullahmamond\FilamentMeet\Models\Meeting;
use Atifullahmamond\FilamentMeet\Services\MeetingService;

Broadcast::channel('meeting.{uuid}', function ($user, string $uuid) {
    $meeting = Meeting::query()->where('uuid', $uuid)->first();

    if (! $meeting) {
        return false;
    }

    if (! app(MeetingService::class)->canJoin($meeting, $user)) {
        return false;
    }

    return [
        'id'   => $user->id,
        'name' => $user->name ?? $user->email,
    ];
});
```

Then configure **Reverb**, **Pusher**, or another Laravel broadcaster:

- Enable broadcasting in `.env` (e.g. `BROADCAST_CONNECTION=reverb`).
- Finish **Laravel Echo** + **`VITE_*`** variables per [Laravel broadcasting](https://laravel.com/docs/broadcasting) and [Installing Laravel Reverb](https://laravel.com/docs/reverb).

If broadcasting is disabled, participant lists **still partially update** via Livewire polling, but Echo events won’t run.

---

### Step 7 — User model, `is_admin` migration & notifications

**Notifications**

- Your **`App\Models\User`** (or **`auth.providers.users.model`**) must use **`Illuminate\Notifications\Notifiable`** for mail / database invitations.
- For queued notifications (`ShouldQueue`), run a queue worker (**`php artisan queue:work`**) and keep **`QUEUE_CONNECTION`** non‑`sync` in production.

**Admin-only meeting creation**

The **`MeetingPolicy`** treats a user as an admin if either:

1. **`hasRole('admin')`** (e.g. [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)), or  
2. **`is_admin`** is truthy on the user (attribute / column).

This package **does not** migrate your **`users`** table. If you rely on **`is_admin`**, add the column yourself.

```bash
php artisan make:migration add_is_admin_to_users_table --table=users
```

Use this migration body (adjust class name/path to match the file Artisan created):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
```

Then **`php artisan migrate`**.

On **`App\Models\User`**, cast **`is_admin`** to boolean and allow mass assignment only if your app sets it safely (Filament/forms/seeders):

```php
protected function casts(): array
{
    return [
        // ... existing casts
        'is_admin' => 'boolean',
    ];
}
```

(Or add **`is_admin`** to **`$casts`** / **`$fillable`** in the classic style.)

Set **`is_admin = true`** in the DB (or Filament User resource / seeder) for users who should see **Create** on meetings.

---

### Step 8 — Verify URLs

Authenticated users reach the embedded room at:

`https://your-app.example/{route_prefix}/room/{meeting_uuid}`  

Default **`route_prefix`** = `meet`, so **`/meet/room/{uuid}`**.

Create a meeting from the Filament **Meetings** resource and use **Join** or the invitation link.

---

### Step 9 — Frontend assets — Vite (required for the meeting room)

The meeting layout calls **`@vite(['resources/css/app.css', 'resources/js/app.js'])`**. Until Vite outputs a manifest, Laravel throws **`ViteManifestNotFoundException`** (**`public/build/manifest.json`** missing).

In your Laravel app root, run:

```bash
npm install
npm run build
```

Then reload **`/meet/room/{uuid}`**. On deploy/CI you must run **`npm run build`** (or equivalent) so **`public/build/`** exists in production.

**Local development:** you can keep **`npm run dev`** running instead of rebuilding every change; Laravel will use **`public/build/hot`**. Stop the dev server without ever running **`npm run build`** and you will see the manifest error again.

Ensure **`resources/js/app.js`** and **`resources/css/app.css`** exist (default Laravel installs include them).

---

### Step 10 — Clear caches

```bash
php artisan optimize:clear
```

---

## Directory Tree

```
filament-meet/
├── composer.json
├── config/
│   └── filament-meet.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_meetings_table.php
│       ├── 2024_01_01_000002_create_meeting_user_table.php
│       └── 2024_01_01_000003_create_meeting_logs_table.php
├── examples/
│   └── AdminPanelProvider.php            ← Plugin + optional JWT/widget examples
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── meeting.blade.php         ← Full-screen meeting layout
│       ├── livewire/
│       │   └── meeting-room.blade.php    ← Meeting room UI + Alpine.js
│       └── meeting-room.blade.php        ← Controller entry point view
└── src/
    ├── FilamentMeetPlugin.php            ← Filament v5 Plugin class
    ├── FilamentMeetServiceProvider.php   ← Laravel ServiceProvider
    ├── Enums/
    │   └── MeetingStatus.php             ← Scheduled/Active/Ended/Cancelled
    ├── Events/
    │   ├── MeetingEnded.php
    │   ├── MeetingStarted.php
    │   ├── ParticipantJoined.php
    │   └── ParticipantLeft.php
    ├── Exceptions/
    │   └── MeetingException.php
    ├── Filament/
    │   ├── Resources/
    │   │   ├── MeetingResource.php       ← Full CRUD resource
    │   │   └── MeetingResource/
    │   │       └── Pages/
    │   │           ├── CreateMeeting.php
    │   │           ├── EditMeeting.php
    │   │           ├── ListMeetings.php
    │   │           └── ViewMeeting.php
    │   └── Widgets/
    │       └── MeetingStatsWidget.php    ← Analytics widget
    ├── Http/
    │   └── Controllers/
    │       └── MeetingRoomController.php
    ├── Jobs/
    │   └── MeetingAISummary.php          ← AI summary stub
    ├── Livewire/
    │   └── MeetingRoom.php               ← Full Livewire v4 component
    ├── Models/
    │   ├── Meeting.php
    │   ├── MeetingLog.php
    │   └── MeetingParticipant.php        ← Pivot model
    ├── Notifications/
    │   ├── MeetingInvited.php
    │   └── MeetingReminder.php
    ├── Policies/
    │   └── MeetingPolicy.php
    └── Services/
        └── MeetingService.php            ← All business logic
```

## Shipping updates (maintainers)

Your **source of truth** is the package folder in the monorepo (this directory). To ship what you built locally:

1. **Commit and push** under `filament-meet/` on your Git branch (e.g. `main` on [atifullahmamond/packages](https://github.com/atifullahmamond/packages)).
2. **Consumers on ZIP / `dev-main`** (README Method A): run  
   `composer update atifullahmamond/filament-meet`  
   so Composer pulls a fresh archive.
3. **Consumers on `path` + symlink**: no Composer update needed; edits are live.
4. **Packagist / semver**: after you publish the package, tag a release (e.g. `filament-meet/v1.0.1` or your monorepo’s tag scheme) and bump **`require`** in apps to that version, then `composer update`.

After upgrading the PHP package, remind users (or your own apps) to **`npm run build`** if Blade/CSS in the package changed, and to keep the **Tailwind `@source`** line in **`resources/css/app.css`** (Step 4b).

---

## Links

| | |
|--|--|
| **GitHub (this package)** | [packages/tree/main/filament-meet](https://github.com/atifullahmamond/packages/tree/main/filament-meet) |
| **Packagist** (if published) | [`atifullahmamond/filament-meet`](https://packagist.org/packages/atifullahmamond/filament-meet) |
| **Issues** | [atifullahmamond/packages/issues](https://github.com/atifullahmamond/packages/issues) |
| **PHP namespace** | `Atifullahmamond\FilamentMeet\` |

## Publish readiness (maintainer checklist)

- [x] `composer.json`: name, license, keywords, `$providers`, autoload — OK (omit **`version`** in `composer.json` when using Git tags for releases; Packagist reads tags).
- [x] **`LICENSE`** (MIT) at package root — present.
- [x] **Translations path** registered — `resources/lang/en/filament-meet.php`.
- [x] **GitHub** — source under [atifullahmamond/packages/filament-meet](https://github.com/atifullahmamond/packages/tree/main/filament-meet) (matches `composer.json` `homepage` / `support`).
- [ ] **`git tag` releases** on GitHub, e.g. `v1.0.0`.
- [ ] **Packagist** submit + hook for auto‑update after push.
- [ ] **Smoke test** another Laravel app via `composer require atifullahmamond/filament-meet` (not only `path`).
- [ ] **README Step 4b** — Tailwind `@source` + `npm run build` documented for meeting room UI.
- Optional: **CHANGELOG.md**, PHPUnit/Pest tests, GitHub Actions CI.

## Feature Checklist

- [x] Full CRUD Filament v5 Resource (List, Create, Edit, View, Infolist)
- [x] Jitsi Meet embedded via External API (iframe, full controls)
- [x] Alpine.js control bar (mic, camera, screen share, chat, leave/end)
- [x] Secure room IDs (UUID + HMAC-SHA256 — never guessable)
- [x] JWT generation for private Jitsi servers
- [x] Real-time participant list (Laravel Echo + Presence Channels)
- [x] Meeting status machine (Scheduled → Active → Ended)
- [x] Role-based policies (admin / host / participant)
- [x] Email + Filament database notifications (invited, reminder)
- [x] Analytics widget (total, active, participants today, avg duration)
- [x] AI summary job stub
- [x] Recording URL field
- [x] Soft deletes
- [x] Meeting logs
- [x] Dark/light mode compatible
- [x] Mobile responsive
