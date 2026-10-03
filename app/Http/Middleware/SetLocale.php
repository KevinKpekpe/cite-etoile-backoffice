<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales for the platform.
     *
     * @var array<string>
     */
    public const SUPPORTED = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $platformLocale = Cache::remember('setting.platform.locale', 3600, function (): string {
            return (string) (Setting::query()
                ->where('setting_group', 'platform')
                ->where('setting_key', 'locale')
                ->value('value') ?? config('app.locale', 'fr'));
        });

        if (! in_array($platformLocale, self::SUPPORTED, true)) {
            $platformLocale = 'fr';
        }

        $accountLocale = $request->user()?->locale;
        $sessionLocale = $request->session()->get('locale');
        $preferredLocale = $accountLocale ?? $sessionLocale;
        $locale = in_array($preferredLocale, self::SUPPORTED, true) ? $preferredLocale : $platformLocale;

        App::setLocale($locale);

        return $next($request);
    }
}
