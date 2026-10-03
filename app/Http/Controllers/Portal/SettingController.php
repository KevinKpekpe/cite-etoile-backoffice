<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer;

        $settingsQuery = Setting::query()->where('setting_group', 'portal')->pluck('value', 'setting_key');
        $currentLocale = App::getLocale();

        $notifications = session('portal_notifications', [
            'email_reminders' => true,
            'email_receipts' => true,
            'email_updates' => true,
        ]);

        return view('portal.settings.index', [
            'user' => $user,
            'customer' => $customer,
            'currentLocale' => $currentLocale,
            'portalEnabled' => filter_var($settingsQuery->get('enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'allowDocumentDownload' => filter_var($settingsQuery->get('allow_document_download', '1'), FILTER_VALIDATE_BOOLEAN),
            'allowOnlinePayment' => filter_var($settingsQuery->get('allow_online_payment', '0'), FILTER_VALIDATE_BOOLEAN),
            'notifications' => $notifications,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:fr,en'],
            'email_reminders' => ['nullable', 'boolean'],
            'email_receipts' => ['nullable', 'boolean'],
            'email_updates' => ['nullable', 'boolean'],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);
        session(['locale' => $validated['locale']]);
        App::setLocale($validated['locale']);

        $notifications = [
            'email_reminders' => $request->boolean('email_reminders'),
            'email_receipts' => $request->boolean('email_receipts'),
            'email_updates' => $request->boolean('email_updates'),
        ];

        session(['portal_notifications' => $notifications]);

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'portal.settings.updated',
            'entity_type' => $request->user()::class,
            'entity_id' => $request->user()->id,
            'new_values' => [
                'locale' => $validated['locale'],
                'notifications' => $notifications,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', __('Vos préférences ont été mises à jour avec succès.'));
    }
}
