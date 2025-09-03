<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domains\ReactivateDomainAction;
use App\Actions\Domains\ToggleDomainLockAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainLockRequest;
use App\Http\Requests\Admin\ReactivateDomainRequest;
use App\Models\Contact;
use App\Models\Domain;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DomainOperationsController extends Controller
{
    public function index(Domain $domain)
    {
        abort_if(Gate::denies('domain_transfer'), 403);
        $contacts = Contact::where('user_id', auth()->id())->get();

        return view('admin.domains.transfer', ['domain' => $domain, 'contacts' => $contacts]);
    }

    public function initiate(Request $request, Domain $domain)
    {
        abort_if(Gate::denies('domain_transfer'), 403);

        $request->validate([
            'auth_code' => 'required|string',
            'registrant_contact_id' => 'required|exists:contacts,id',
            'admin_contact_id' => 'required|exists:contacts,id',
            'tech_contact_id' => 'required|exists:contacts,id',
            'billing_contact_id' => 'required|exists:contacts,id',
        ]);

        try {
            return redirect()->route('admin.domains.index')->with('success', 'Domain transfer initiated successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Failed to initiate domain transfer: '.$e->getMessage()]);
        }
    }

    public function toggleLock(DomainLockRequest $request, Domain $domain, ToggleDomainLockAction $action)
    {
        try {
            $result = $action->execute($domain, $request->boolean('lock'));

            if (! $result['success']) {
                return back()->withErrors(['error' => $result['message'] ?? 'Failed to update domain lock status']);
            }

            $message = $request->boolean('lock') ? 'Domain locked successfully' : 'Domain unlocked successfully';

            return back()->with('success', $message);

        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Failed to update domain lock status: '.$e->getMessage()]);
        }
    }

    public function reactivate(ReactivateDomainRequest $request, ReactivateDomainAction $action)
    {
        try {
            $domain = Domain::where('name', $request->validated('domain'))->firstOrFail();
            
            $result = $action->handle($domain);

            if (! $result['success']) {
                return back()->withErrors(['error' => $result['message'] ?? 'Failed to reactivate domain']);
            }

            return back()->with('success', $result['message'] ?? 'Domain reactivated successfully');

        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Failed to reactivate domain: '.$e->getMessage()]);
        }
    }
}
