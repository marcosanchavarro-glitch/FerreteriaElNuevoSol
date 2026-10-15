<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\User;
use App\Policies\ContentPolicy;
use App\Services\Content\SiteSettings;
use App\Services\Storefront\PublicCategories;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('demo.enabled')) {
            config(['mail.default' => 'array']);
            Event::listen(MessageSending::class, fn () => false);
            Event::listen(NotificationSending::class, fn () => false);
        }
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        RateLimiter::for('demo-access', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        View::composer(['storefront.cart', 'storefront.checkout', 'storefront.confirmation'], function (\Illuminate\View\View $view): void {
            $categories = app(PublicCategories::class);
            $view->with('categoryRows', $categories->rows())->with('navCategories', $categories->all()->whereNull('parent_id'));
        });
        Model::preventSilentlyDiscardingAttributes();
        View::composer('layouts.storefront', function (\Illuminate\View\View $view): void {
            if (! array_key_exists('categoryRows', $view->getData())) {
                $categories = app(PublicCategories::class);
                $view->with('categoryRows', $categories->rows())->with('navCategories', $categories->all()->whereNull('parent_id'));
            }
            $view->with('siteSettings', app(SiteSettings::class)->all());
            $view->with('unreadNotifications', auth()->user()?->can('access-account')
                ? auth()->user()->notifications()->visible()->wherePivotNull('read_at')->count() : 0);
        });
        ResetPassword::createUrlUsing(
            fn (User $user, string $token): string => rtrim(config('app.url'), '/').route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)
        );
        Gate::define('access-admin', fn (User $user): bool => $user->active && in_array($user->role, [UserRole::Admin, UserRole::Seller], true));
        Gate::define('access-account', fn (User $user): bool => $user->active && $user->role === UserRole::Customer);
        Gate::policy(HomepageSection::class, ContentPolicy::class);
        Gate::policy(Banner::class, ContentPolicy::class);
        Gate::define('view-activity', fn (User $user): bool => $user->active && $user->role === UserRole::Admin);
        Gate::define('manage-settings', fn (User $user): bool => $user->active && $user->role === UserRole::Admin);
        Gate::define('manage-roles', fn (User $user): bool => $user->active && $user->role === UserRole::Admin);
        RateLimiter::for('authentication', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-recovery', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
