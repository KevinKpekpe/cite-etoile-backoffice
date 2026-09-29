<?php

namespace App\Http\Controllers;

use App\Mail\UserCredentialsMail;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\UserCredentialsMarkdownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerPortalController extends Controller
{
    /**
     * Create a portal account for a customer who does not have one yet.
     */
    public function createAccess(
        Request $request,
        Customer $customer,
        UserCredentialsMarkdownService $markdownService,
    ): RedirectResponse {
        abort_unless($request->user()?->can('customers.update'), 403);
        abort_if($customer->user_id !== null, 422, 'Ce client possède déjà un accès au portail.');

        $temporaryPassword = Str::password(12);

        DB::transaction(function () use ($customer, $temporaryPassword, $markdownService): void {
            $email = $customer->email ?: strtolower(Str::slug($customer->customer_number)).'@client.cite-etoile.cd';

            $customerRole = Role::query()->where('name', 'customer')->first();

            $clientUser = User::query()->create([
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $email,
                'phone' => $customer->phone,
                'password' => Hash::make($temporaryPassword),
                'avatar_path' => $customer->avatar_path,
                'status' => 'active',
                'must_change_password' => true,
            ]);

            if ($customerRole !== null) {
                $clientUser->roles()->syncWithoutDetaching([$customerRole->id]);
            }

            $customer->update(['user_id' => $clientUser->id]);

            $markdown = $markdownService->generate($clientUser, $temporaryPassword, 'Client Portail');
            session()->flash('user_credentials_markdown', $markdown);
            session()->flash('temporary_password', $temporaryPassword);

            try {
                Mail::to($clientUser->email)->send(new UserCredentialsMail($clientUser, $temporaryPassword, $markdown, 'Client Portail'));
            } catch (\Throwable) {
                // Silently ignore mail failures (offline driver)
            }
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', __('Accès au portail créé. Les identifiants ont été envoyés au client.'));
    }

    /**
     * Reset the portal password and send new credentials to the customer.
     */
    public function resetPassword(
        Request $request,
        Customer $customer,
        UserCredentialsMarkdownService $markdownService,
    ): RedirectResponse {
        abort_unless($request->user()?->can('customers.update'), 403);
        abort_if($customer->user_id === null, 422, 'Ce client ne possède pas encore d\'accès au portail.');

        $temporaryPassword = Str::password(12);
        $clientUser = $customer->user;

        DB::transaction(function () use ($clientUser, $temporaryPassword, $markdownService): void {
            $clientUser->update([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
            ]);

            $markdown = $markdownService->generate($clientUser, $temporaryPassword, 'Client Portail');
            session()->flash('user_credentials_markdown', $markdown);
            session()->flash('temporary_password', $temporaryPassword);

            try {
                Mail::to($clientUser->email)->send(new UserCredentialsMail($clientUser, $temporaryPassword, $markdown, 'Client Portail'));
            } catch (\Throwable) {
                // Silently ignore mail failures (offline driver)
            }
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', __('Mot de passe réinitialisé. Le client recevra ses nouveaux identifiants par e-mail.'));
    }

    /**
     * Resend the access credentials email without generating a new password.
     */
    public function resendCredentials(
        Request $request,
        Customer $customer,
        UserCredentialsMarkdownService $markdownService,
    ): RedirectResponse {
        abort_unless($request->user()?->can('customers.update'), 403);
        abort_if($customer->user_id === null, 422, 'Ce client ne possède pas encore d\'accès au portail.');

        $clientUser = $customer->user;
        $temporaryPassword = Str::password(12);

        DB::transaction(function () use ($clientUser, $temporaryPassword, $markdownService): void {
            $clientUser->update([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
            ]);

            $markdown = $markdownService->generate($clientUser, $temporaryPassword, 'Client Portail');
            session()->flash('user_credentials_markdown', $markdown);
            session()->flash('temporary_password', $temporaryPassword);

            try {
                Mail::to($clientUser->email)->send(new UserCredentialsMail($clientUser, $temporaryPassword, $markdown, 'Client Portail'));
            } catch (\Throwable) {
                // Silently ignore mail failures (offline driver)
            }
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', __('Les identifiants ont été renvoyés au client par e-mail.'));
    }

    /**
     * Suspend or reactivate the customer's portal account.
     */
    public function toggleStatus(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()?->can('customers.update'), 403);
        abort_if($customer->user_id === null, 422, 'Ce client ne possède pas encore d\'accès au portail.');

        $clientUser = $customer->user;
        $isSuspended = $clientUser->status === 'suspended';

        $clientUser->update(['status' => $isSuspended ? 'active' : 'suspended']);

        $message = $isSuspended
            ? __('Accès au portail réactivé.')
            : __('Accès au portail suspendu.');

        return redirect()->route('customers.show', $customer)->with('status', $message);
    }
}
