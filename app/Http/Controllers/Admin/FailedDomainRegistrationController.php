<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\RegisterDomainAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManualRegisterDomainRequest;
use App\Models\Contact;
use App\Models\Country;
use App\Models\FailedDomainRegistration;
use App\Models\User;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class FailedDomainRegistrationController extends Controller
{
    public function __construct(
        private readonly RegisterDomainAction $registerDomainAction
    ) {}

    /**
     * Display list of failed domain registrations
     */
    public function index(Request $request): View|Factory
    {
        abort_if(Gate::denies('failed_registration_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = FailedDomainRegistration::query()
            ->with(['order.user', 'orderItem'])->latest();

        // Filter by status if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $failedRegistrations = $query->get();

        return view('admin.failed-registrations.index', [
            'failedRegistrations' => $failedRegistrations,
            'selectedStatus' => $request->status ?? '',
        ]);
    }

    /**
     * Show details of a specific failed registration
     */
    public function show(FailedDomainRegistration $failedDomainRegistration): View|Factory
    {
        abort_if(Gate::denies('failed_registration_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $failedDomainRegistration->load(['order.user', 'orderItem']);

        return view('admin.failed-registrations.show', [
            'failedRegistration' => $failedDomainRegistration,
        ]);
    }

    /**
     * Manually trigger retry for a failed registration
     */
    public function retry(FailedDomainRegistration $failedDomainRegistration): RedirectResponse
    {
        abort_if(Gate::denies('failed_registration_retry'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Check if we can retry
        if (! $failedDomainRegistration->canRetry()) {
            return back()
                ->with('error', 'This domain registration cannot be retried. It may have already been resolved or abandoned.');
        }

        $failedDomainRegistration->loadMissing(['order', 'orderItem']);

        try {
            // Attempt registration
            $contactIds = $failedDomainRegistration->contact_ids ?? [];
            $result = $this->registerDomainAction->handle(
                $failedDomainRegistration->domain_name,
                $contactIds,
                $failedDomainRegistration->orderItem->years,
                [], // Use default nameservers
                true, // Use single contact
                $failedDomainRegistration->order->user_id
            );

            if ($result['success']) {
                // Registration succeeded - update order item and mark as resolved
                if (isset($result['domain_id'])) {
                    $failedDomainRegistration->orderItem->update([
                        'domain_id' => $result['domain_id'],
                    ]);
                }

                $failedDomainRegistration->markResolved();

                Log::info('Manual domain registration retry succeeded', [
                    'failed_registration_id' => $failedDomainRegistration->id,
                    'domain' => $failedDomainRegistration->domain_name,
                    'domain_id' => $result['domain_id'] ?? null,
                    'admin_user_id' => auth()->id(),
                ]);

                return to_route('admin.failed-registrations.index')
                    ->with('success', sprintf('Domain %s has been successfully registered!', $failedDomainRegistration->domain_name));
            }

            // Registration failed - increment retry count and update failure reason
            $failedDomainRegistration->incrementRetryCount();
            $failedDomainRegistration->update([
                'failure_reason' => $result['message'] ?? 'Registration failed',
            ]);

            Log::warning('Manual domain registration retry failed', [
                'failed_registration_id' => $failedDomainRegistration->id,
                'domain' => $failedDomainRegistration->domain_name,
                'error' => $result['message'] ?? 'Unknown error',
                'admin_user_id' => auth()->id(),
            ]);

            return back()
                ->with('error', 'Registration failed: '.($result['message'] ?? 'Unknown error'));

        } catch (Exception $exception) {
            // Exception during retry
            $failedDomainRegistration->incrementRetryCount();
            $failedDomainRegistration->update([
                'failure_reason' => $exception->getMessage(),
            ]);

            Log::error('Exception during manual domain registration retry', [
                'failed_registration_id' => $failedDomainRegistration->id,
                'domain' => $failedDomainRegistration->domain_name,
                'error' => $exception->getMessage(),
                'admin_user_id' => auth()->id(),
            ]);

            return back()
                ->with('error', 'An error occurred: '.$exception->getMessage());
        }
    }

    /**
     * Show form for manual domain registration
     */
    public function manualRegisterForm(): View|Factory
    {
        abort_if(Gate::denies('failed_registration_retry'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $users = User::query()->orderBy('first_name')->get();
        $countries = Country::query()->orderBy('name')->get();
        $contacts = Contact::query()->orderBy('first_name')->get();

        return view('admin.failed-registrations.manual-register', [
            'users' => $users,
            'countries' => $countries,
            'contacts' => $contacts,
        ]);
    }

    /**
     * Process manual domain registration
     */
    public function manualRegisterStore(ManualRegisterDomainRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('failed_registration_retry'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validated();

        try {
            // Prepare contact data
            $contacts = [
                'registrant' => $validated['registrant_contact_id'],
                'admin' => $validated['admin_contact_id'],
                'technical' => $validated['technical_contact_id'],
                'billing' => $validated['billing_contact_id'],
            ];

            // Prepare nameservers
            $nameservers = array_filter([
                $validated['nameserver_1'] ?? null,
                $validated['nameserver_2'] ?? null,
                $validated['nameserver_3'] ?? null,
                $validated['nameserver_4'] ?? null,
            ]);

            // Attempt registration
            $result = $this->registerDomainAction->handle(
                $validated['domain_name'],
                $contacts,
                $validated['years'],
                $nameservers,
                false, // Don't use single contact for manual registration
                $validated['user_id']
            );

            if ($result['success']) {
                Log::info('Manual domain registration succeeded', [
                    'domain' => $validated['domain_name'],
                    'user_id' => $validated['user_id'],
                    'domain_id' => $result['domain_id'] ?? null,
                    'admin_user_id' => auth()->id(),
                ]);

                return to_route('admin.domains.index')
                    ->with('success', sprintf('Domain %s has been successfully registered!', $validated['domain_name']));
            }

            Log::warning('Manual domain registration failed', [
                'domain' => $validated['domain_name'],
                'error' => $result['message'] ?? 'Unknown error',
                'admin_user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Registration failed: '.($result['message'] ?? 'Unknown error'));

        } catch (Exception $exception) {
            Log::error('Exception during manual domain registration', [
                'domain' => $validated['domain_name'],
                'error' => $exception->getMessage(),
                'admin_user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'An error occurred: '.$exception->getMessage());
        }
    }
}
