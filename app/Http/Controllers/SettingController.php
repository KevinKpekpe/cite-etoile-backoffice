<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $settings = Setting::query()->orderBy('setting_group')->orderBy('setting_key')->get()
            ->keyBy(fn (Setting $setting): string => "{$setting->setting_group}.{$setting->setting_key}");

        return view('settings.index', compact('settings'));
    }

    public function update(UpdateSettingsRequest $request, SettingService $settings): RedirectResponse
    {
        $values = $request->validated();
        $values['finance']['payment_methods'] = json_encode($values['finance']['payment_methods'], JSON_THROW_ON_ERROR);

        // Cast portal booleans properly (unchecked checkboxes are absent from the payload)
        $values['portal']['allow_profile_edit'] = $values['portal']['allow_profile_edit'] ?? '0';
        $values['portal']['show_payment_history'] = $values['portal']['show_payment_history'] ?? '0';

        $flat = collect($values)
            ->flatMap(fn (array $group, string $groupName) => collect($group)
                ->mapWithKeys(fn ($value, string $key) => ["{$groupName}.{$key}" => (string) $value]))
            ->all();

        $settings->updateMany($flat, $request->user());

        // Bust the locale cache when platform settings are saved
        Cache::forget('setting.platform.locale');

        if (isset($values['platform']['locale'])) {
            $newLocale = (string) $values['platform']['locale'];
            session(['locale' => $newLocale]);
            $request->user()?->update(['locale' => $newLocale]);
            App::setLocale($newLocale);
        }

        return back()->with('status', __('Paramètres mis à jour.'));
    }
}
