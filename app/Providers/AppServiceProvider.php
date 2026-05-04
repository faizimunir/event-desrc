<?php

namespace App\Providers;

use App\Models\Media;
use App\Observers\MediaObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn () => ImageManager::gd());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Media::observe(MediaObserver::class);
        $this->registerNavbarViewComposer();
        $this->configureDefaults();
        $this->configureActiveRoleGate();
        $this->registerCanAsBladeDirectives();
        $this->configureVitePreload();
    }

    /**
     * Nonaktifkan preload untuk CSS agar tidak ada warning "preloaded but not used"
     * dan tidak memboroskan memory/bandwidth. CSS tetap dimuat via <link rel="stylesheet">.
     */
    protected function configureVitePreload(): void
    {
        Vite::usePreloadTagAttributes(function (string $src, string $url, ?array $chunk, ?array $manifest) {
            if (str_ends_with($url, '.css')) {
                return false;
            }

            return [];
        });
    }

    protected function registerNavbarViewComposer(): void
    {
        View::composer('partials.navbar', function ($view) {
            $sessionId = session()->getId();
            $userId = auth()->id();
            \App\Models\Order::enforceExpiredDraftsForVisitor($sessionId, $userId);
            \App\Models\Order::enforceExpiredPaymentWindowsForVisitor($sessionId, $userId);

            $count = \App\Models\Order::query()
                ->forCurrentVisitor($sessionId, $userId)
                ->excludeAbandonedDraftTimeout()
                ->pendingPayment()
                ->count();
            $view->with('pendingOrdersCount', $count);
        });
    }

    protected function registerCanAsBladeDirectives(): void
    {
        Blade::if('canAs', function (string $permission) {
            return auth()->check() && auth()->user()->canAs($permission);
        });
        Blade::if('cannotAs', function (string $permission) {
            return ! auth()->check() || ! auth()->user()->canAs($permission);
        });

        Blade::if('activeRole', function (string $role) {
            return auth()->check() && auth()->user()->isActiveRole($role);
        });
        Blade::if('activeAnyRole', function (...$roles) {
            if (! auth()->check()) {
                return false;
            }
            $active = session('active_role');

            return $active && in_array($active, $roles, true);
        });
    }

    /**
     * Gate::before: intercept permission strings (verb.entity) → use canAs().
     * Policy abilities (update, delete, etc.) fall through → policy runs.
     *
     * PENTING: Di dalam Policy, WAJIB pakai canAs() bukan can().
     * Contoh: return $user->canAs('update.rider');
     * Kalau pakai $user->can(), Spatie cek SEMUA role → bom waktu.
     *
     * Standar:
     * - UI button: @canAs('update.rider') — jangan @can
     * - Policy: @can('update', $model) OK, tapi di Policy::update() panggil canAs()
     */
    protected function configureActiveRoleGate(): void
    {
        Gate::before(function ($user, string $ability) {
            if (! $user || ! method_exists($user, 'canAs')) {
                return null;
            }
            if (str_contains($ability, '.')) {
                return $user->canAs($ability);
            }

            return null;
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->letters()
                ->numbers()
            : null
        );
    }
}
