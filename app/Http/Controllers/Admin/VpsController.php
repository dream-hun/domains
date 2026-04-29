<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Vps\AssignVpsToSubscriptionAction;
use App\Actions\Vps\CancelVpsAction;
use App\Actions\Vps\ChangeVpsDisplayNameAction;
use App\Actions\Vps\CreateVpsSnapshotAction;
use App\Actions\Vps\DeleteVpsSnapshotAction;
use App\Actions\Vps\ExtendVpsStorageAction;
use App\Actions\Vps\MoveVpsRegionAction;
use App\Actions\Vps\OrderVpsLicenseAction;
use App\Actions\Vps\ReinstallVpsAction;
use App\Actions\Vps\RescueVpsAction;
use App\Actions\Vps\ResetVpsCredentialsAction;
use App\Actions\Vps\RestartVpsAction;
use App\Actions\Vps\RestoreVpsSnapshotAction;
use App\Actions\Vps\ShutdownVpsAction;
use App\Actions\Vps\StartVpsAction;
use App\Actions\Vps\UpgradeVpsAction;
use App\Enums\Vps\VpsInstanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignVpsRequest;
use App\Http\Requests\Admin\ChangeVpsDisplayNameRequest;
use App\Http\Requests\Admin\CreateVpsSnapshotRequest;
use App\Http\Requests\Admin\ExtendVpsStorageRequest;
use App\Http\Requests\Admin\MoveVpsRegionRequest;
use App\Http\Requests\Admin\OrderVpsLicenseRequest;
use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class VpsController extends Controller
{
    public function __construct(private readonly ContaboService $contaboService) {}

    public function index(Request $request): View|Factory
    {
        $this->authorizeAdmin();
        abort_if(Gate::denies('vps_access'), Response::HTTP_FORBIDDEN);

        $instances = new LengthAwarePaginator([], 0, 10);
        $errorMessage = '';

        try {
            $page = (int) $request->query('page', 1);
            $perPage = 10;

            $assignedSubscriptions = Subscription::query()
                ->whereNotNull('provider_resource_id')
                ->with('user', 'plan')
                ->get()
                ->keyBy(fn (Subscription $sub): int => (int) $sub->provider_resource_id);

            $apiResponse = $this->contaboService->listInstances(['page' => $page, 'size' => $perPage]);
            $pagination = $apiResponse['_pagination'] ?? [];
            $totalElements = (int) ($pagination['totalElements'] ?? 0);

            $items = collect($apiResponse['data'] ?? [])->map(function (array $apiInstance) use ($assignedSubscriptions): array {
                $status = VpsInstanceStatus::tryFrom(mb_strtolower($apiInstance['status'] ?? '')) ?? VpsInstanceStatus::Unknown;
                $subscription = $assignedSubscriptions->get($apiInstance['instanceId']);

                return [
                    'instance_id' => $apiInstance['instanceId'],
                    'name' => $apiInstance['name'] ?? 'N/A',
                    'display_name' => $apiInstance['displayName'] ?? '',
                    'product_type' => $apiInstance['productType'] ?? 'N/A',
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'status_color' => $status->color(),
                    'status_icon' => $status->icon(),
                    'ip_address' => $apiInstance['ipConfig']['v4']['ip'] ?? 'N/A',
                    'assigned' => $subscription !== null,
                    'subscription_uuid' => $subscription?->uuid,
                    'subscription_id' => $subscription?->id,
                    'user_name' => $subscription?->user?->name,
                    'plan_name' => $subscription?->plan?->name,
                ];
            })->all();

            $instances = new LengthAwarePaginator($items, $totalElements, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to load VPS instances', ['error' => $runtimeException->getMessage()]);
            $errorMessage = 'Failed to load VPS instances. Please try again.';
        }

        return view('admin.vps.index', [
            'instances' => $instances,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function show(Subscription $subscription): View|Factory
    {
        abort_if(Gate::denies('vps_show'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $instance = [];
        $snapshots = [];
        $backups = [];
        $errorMessage = '';

        try {
            if (! $subscription->provider_resource_id) {
                $errorMessage = 'No VPS instance linked to this subscription.';

                return view('admin.vps.show', [
                    'subscription' => $subscription,
                    'instance' => $instance,
                    'snapshots' => $snapshots,
                    'backups' => $backups,
                    'errorMessage' => $errorMessage,
                ]);
            }

            $apiInstance = $this->contaboService->getInstance((int) $subscription->provider_resource_id);
            $status = VpsInstanceStatus::tryFrom(mb_strtolower($apiInstance['status'] ?? '')) ?? VpsInstanceStatus::Unknown;

            $instance = [
                'instance_id' => $apiInstance['instanceId'],
                'name' => $apiInstance['name'] ?? 'N/A',
                'display_name' => $apiInstance['displayName'] ?? '',
                'product_type' => $apiInstance['productType'] ?? 'N/A',
                'default_user' => $apiInstance['defaultUser'] ?? 'N/A',
                'status' => $status->value,
                'status_label' => $status->label(),
                'status_color' => $status->color(),
                'status_icon' => $status->icon(),
                'ip_v4' => $apiInstance['ipConfig']['v4']['ip'] ?? 'N/A',
                'ip_v6' => $apiInstance['ipConfig']['v6']['ip'] ?? 'N/A',
                'image_id' => $apiInstance['imageId'] ?? '',
                'os_type' => $apiInstance['osType'] ?? 'N/A',
                'cpu_cores' => $apiInstance['cpuCores'] ?? 'N/A',
                'ram_mb' => $apiInstance['ramMb'] ?? 'N/A',
                'disk_mb' => $apiInstance['diskMb'] ?? 'N/A',
                'vnc_url' => $apiInstance['vncUrl'] ?? null,
                'created_date' => $apiInstance['createdDate'] ?? 'N/A',
                'cancel_date' => $apiInstance['cancelDate'] ?? null,
            ];

            $maxSnapshots = $apiInstance['addOns']['maxSnapshots'] ?? null;

            if (Gate::allows('vps_snapshot_access')) {
                $snapshotResponse = $this->contaboService->listSnapshots((int) $subscription->provider_resource_id);
                $snapshots = $snapshotResponse['data'] ?? [];
            }

            if (Gate::allows('vps_backup_access')) {
                try {
                    $backupResponse = $this->contaboService->listInstanceBackups((int) $subscription->provider_resource_id);
                    $backups = $backupResponse['data'] ?? [];
                } catch (RuntimeException) {
                    // Backups may not be activated — fail silently
                }
            }
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to load VPS instance', ['error' => $runtimeException->getMessage()]);
            $errorMessage = 'Failed to load VPS instance details.';
        }

        return view('admin.vps.show', [
            'subscription' => $subscription,
            'instance' => $instance,
            'snapshots' => $snapshots,
            'backups' => $backups,
            'maxSnapshots' => $maxSnapshots ?? null,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function assign(): View|Factory
    {
        $this->authorizeAdmin();
        abort_if(Gate::denies('vps_assign'), Response::HTTP_FORBIDDEN);

        $unassignedSubscriptions = [];
        $unassignedInstances = [];
        $errorMessage = '';

        try {
            $unassignedSubscriptions = Subscription::query()
                ->with('user', 'plan')
                ->get()
                ->map(fn (Subscription $sub): array => [
                    'id' => $sub->id,
                    'uuid' => $sub->uuid,
                    'domain' => $sub->domain ?? 'N/A',
                    'plan_name' => $sub->plan?->name ?? 'N/A',
                    'user_name' => $sub->user?->name ?? 'N/A',
                    'is_assigned' => $sub->provider_resource_id !== null,
                    'current_instance_id' => $sub->provider_resource_id,
                ])
                ->toArray();

            $allApiInstances = collect($this->contaboService->listAllInstances());

            $assignedInstanceIds = Subscription::query()
                ->whereNotNull('provider_resource_id')
                ->pluck('provider_resource_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->flip();

            $unassignedInstances = $allApiInstances
                ->map(fn (array $instance): array => [
                    'instanceId' => $instance['instanceId'],
                    'name' => $instance['name'] ?? 'N/A',
                    'displayName' => $instance['displayName'] ?? '',
                    'status' => $instance['status'] ?? 'unknown',
                    'ipAddress' => $instance['ipConfig']['v4']['ip'] ?? 'N/A',
                    'is_assigned' => $assignedInstanceIds->has($instance['instanceId']),
                ])
                ->values()
                ->all();
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to load VPS assignment data', ['error' => $runtimeException->getMessage()]);
            $errorMessage = 'Failed to load data. Please try again.';
        }

        return view('admin.vps.assign', [
            'unassignedSubscriptions' => $unassignedSubscriptions,
            'unassignedInstances' => $unassignedInstances,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function showInstance(int $instanceId): View|Factory
    {
        $this->authorizeAdmin();

        $instance = [];
        $errorMessage = '';

        try {
            $instance = $this->contaboService->getInstance($instanceId);
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to load VPS instance', ['error' => $runtimeException->getMessage()]);
            $errorMessage = 'Failed to load VPS instance details.';
        }

        return view('admin.vps.show-instance', [
            'instanceId' => $instanceId,
            'instance' => $instance,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function storeAssignment(AssignVpsRequest $request, AssignVpsToSubscriptionAction $action): RedirectResponse
    {
        $this->authorizeAdmin();

        $subscription = Subscription::query()->findOrFail($request->validated('subscription_id'));
        $result = $action->execute($subscription, (int) $request->validated('instance_id'));

        if ($result['success']) {
            return redirect()->route('admin.vps.index')->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function start(Subscription $subscription, StartVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_start'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success']) {
            $redirect->with('pending_refresh', true);
        }

        return $redirect;
    }

    public function restart(Subscription $subscription, RestartVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_restart'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success']) {
            $redirect->with('pending_refresh', true);
        }

        return $redirect;
    }

    public function shutdown(Subscription $subscription, ShutdownVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_shutdown'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success']) {
            $redirect->with('pending_refresh', true);
        }

        return $redirect;
    }

    public function reinstall(Request $request, Subscription $subscription, ReinstallVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_reinstall'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription, $request->only(['imageId']));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function rescue(Subscription $subscription, RescueVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_rescue'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success'] && isset($result['password'])) {
            $redirect->with('generated_password', $result['password']);
        }

        return $redirect;
    }

    public function resetCredentials(Subscription $subscription, ResetVpsCredentialsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_reset_credentials'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success'] && isset($result['password'])) {
            $redirect->with('generated_password', $result['password']);
        }

        return $redirect;
    }

    public function changeDisplayName(ChangeVpsDisplayNameRequest $request, Subscription $subscription, ChangeVpsDisplayNameAction $action): RedirectResponse
    {
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription, $request->validated('display_name'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function createSnapshot(CreateVpsSnapshotRequest $request, Subscription $subscription, CreateVpsSnapshotAction $action): RedirectResponse
    {
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute(
            $subscription,
            $request->validated('name'),
            $request->validated('description') ?? '',
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function deleteSnapshot(Subscription $subscription, string $snapshotId, DeleteVpsSnapshotAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_snapshot_delete'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription, $snapshotId);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function restoreSnapshot(Subscription $subscription, string $snapshotId, RestoreVpsSnapshotAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_backup_restore'), Response::HTTP_FORBIDDEN);
        $this->authorizeSubscriptionOwner($subscription);

        $result = $action->execute($subscription, $snapshotId);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function upgrade(Subscription $subscription, UpgradeVpsAction $action): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_if(Gate::denies('vps_upgrade'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function orderLicense(OrderVpsLicenseRequest $request, Subscription $subscription, OrderVpsLicenseAction $action): RedirectResponse
    {
        $this->authorizeAdmin();

        $result = $action->execute($subscription, $request->validated('license_type'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function extendStorage(ExtendVpsStorageRequest $request, Subscription $subscription, ExtendVpsStorageAction $action): RedirectResponse
    {
        $this->authorizeAdmin();

        $result = $action->execute($subscription, (int) $request->validated('storage_gb'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function moveRegion(MoveVpsRegionRequest $request, Subscription $subscription, MoveVpsRegionAction $action): RedirectResponse
    {
        $this->authorizeAdmin();

        $result = $action->execute($subscription, $request->validated('target_region'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(Subscription $subscription, CancelVpsAction $action): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_if(Gate::denies('vps_cancel'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function authorizeSubscriptionOwner(Subscription $subscription): void
    {
        abort_if(! auth()->user()?->isAdmin() && $subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);
    }

    private function authorizeAdmin(): void
    {
        abort_if(! auth()->user()?->isAdmin(), Response::HTTP_FORBIDDEN);
    }
}
