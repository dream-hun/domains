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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class VpsController extends Controller
{
    public function __construct(private readonly ContaboService $contaboService)
    {
        abort_if(! auth()->user()?->isAdmin(), Response::HTTP_FORBIDDEN);
    }

    public function index(): View|Factory
    {
        abort_if(Gate::denies('vps_access'), Response::HTTP_FORBIDDEN);

        $instances = [];
        $errorMessage = '';

        try {
            $subscriptions = Subscription::query()
                ->whereNotNull('provider_resource_id')
                ->with('user', 'plan')
                ->get();

            $apiResponse = $this->contaboService->listInstances();
            $apiInstances = collect($apiResponse['data'] ?? []);

            $instances = $subscriptions->map(function (Subscription $subscription) use ($apiInstances): ?array {
                $apiInstance = $apiInstances->firstWhere('instanceId', (int) $subscription->provider_resource_id);

                if (! $apiInstance) {
                    return null;
                }

                $status = VpsInstanceStatus::tryFrom(mb_strtolower($apiInstance['status'] ?? '')) ?? VpsInstanceStatus::Unknown;
                $ipAddresses = collect($apiInstance['ipConfig']['v4']['ip'] ?? [])->implode(', ');

                return [
                    'subscription_uuid' => $subscription->uuid,
                    'subscription_id' => $subscription->id,
                    'instance_id' => $apiInstance['instanceId'],
                    'name' => $apiInstance['name'] ?? 'N/A',
                    'display_name' => $apiInstance['displayName'] ?? '',
                    'product_type' => $apiInstance['productType'] ?? 'N/A',
                    'default_user' => $apiInstance['defaultUser'] ?? 'N/A',
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'status_color' => $status->color(),
                    'status_icon' => $status->icon(),
                    'ip_address' => $ipAddresses ?: ($apiInstance['ipConfig']['v4']['ip'] ?? 'N/A'),
                    'region' => $apiInstance['region'] ?? 'N/A',
                    'user_name' => $subscription->user?->name ?? 'N/A',
                    'user_id' => $subscription->user_id,
                    'plan_name' => $subscription->plan?->name ?? 'N/A',
                ];
            })->filter()->values()->toArray();
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
                'region' => $apiInstance['region'] ?? 'N/A',
                'data_center' => $apiInstance['dataCenter'] ?? 'N/A',
                'image_id' => $apiInstance['imageId'] ?? '',
                'os_type' => $apiInstance['osType'] ?? 'N/A',
                'cpu_cores' => $apiInstance['cpuCores'] ?? 'N/A',
                'ram_mb' => $apiInstance['ramMb'] ?? 'N/A',
                'disk_mb' => $apiInstance['diskMb'] ?? 'N/A',
                'vnc_url' => $apiInstance['vncUrl'] ?? null,
                'created_date' => $apiInstance['createdDate'] ?? 'N/A',
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
        abort_if(Gate::denies('vps_assign'), Response::HTTP_FORBIDDEN);

        $unassignedSubscriptions = [];
        $unassignedInstances = [];
        $errorMessage = '';

        try {
            $unassignedSubscriptions = Subscription::query()
                ->whereNull('provider_resource_id')
                ->with('user', 'plan')
                ->get()
                ->map(fn (Subscription $sub): array => [
                    'id' => $sub->id,
                    'uuid' => $sub->uuid,
                    'domain' => $sub->domain ?? 'N/A',
                    'plan_name' => $sub->plan?->name ?? 'N/A',
                    'user_name' => $sub->user?->name ?? 'N/A',
                ])
                ->toArray();

            $apiResponse = $this->contaboService->listInstances();
            $allApiInstances = collect($apiResponse['data'] ?? []);

            $assignedInstanceIds = Subscription::query()
                ->whereNotNull('provider_resource_id')
                ->pluck('provider_resource_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $unassignedInstances = $allApiInstances
                ->reject(fn (array $instance): bool => in_array($instance['instanceId'], $assignedInstanceIds, true))
                ->map(fn (array $instance): array => [
                    'instanceId' => $instance['instanceId'],
                    'name' => $instance['name'] ?? 'N/A',
                    'displayName' => $instance['displayName'] ?? '',
                    'status' => $instance['status'] ?? 'unknown',
                    'ipAddress' => $instance['ipConfig']['v4']['ip'] ?? 'N/A',
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

    public function storeAssignment(AssignVpsRequest $request, AssignVpsToSubscriptionAction $action): RedirectResponse
    {
        $subscription = Subscription::query()->findOrFail($request->validated('subscription_id'));
        $result = $action->execute($subscription, (int) $request->validated('instance_id'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function start(Subscription $subscription, StartVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_start'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function restart(Subscription $subscription, RestartVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_restart'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function shutdown(Subscription $subscription, ShutdownVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_shutdown'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function reinstall(Request $request, Subscription $subscription, ReinstallVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_reinstall'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription, $request->only(['imageId']));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function rescue(Subscription $subscription, RescueVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_rescue'), Response::HTTP_FORBIDDEN);

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

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success'] && isset($result['password'])) {
            $redirect->with('generated_password', $result['password']);
        }

        return $redirect;
    }

    public function changeDisplayName(ChangeVpsDisplayNameRequest $request, Subscription $subscription, ChangeVpsDisplayNameAction $action): RedirectResponse
    {
        $result = $action->execute($subscription, $request->validated('display_name'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function createSnapshot(CreateVpsSnapshotRequest $request, Subscription $subscription, CreateVpsSnapshotAction $action): RedirectResponse
    {
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

        $result = $action->execute($subscription, $snapshotId);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function restoreSnapshot(Subscription $subscription, string $snapshotId, RestoreVpsSnapshotAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_backup_restore'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription, $snapshotId);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function upgrade(Subscription $subscription, UpgradeVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_upgrade'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function orderLicense(OrderVpsLicenseRequest $request, Subscription $subscription, OrderVpsLicenseAction $action): RedirectResponse
    {
        $result = $action->execute($subscription, $request->validated('license_type'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function extendStorage(ExtendVpsStorageRequest $request, Subscription $subscription, ExtendVpsStorageAction $action): RedirectResponse
    {
        $result = $action->execute($subscription, (int) $request->validated('storage_gb'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function moveRegion(MoveVpsRegionRequest $request, Subscription $subscription, MoveVpsRegionAction $action): RedirectResponse
    {
        $result = $action->execute($subscription, $request->validated('target_region'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(Subscription $subscription, CancelVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_cancel'), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
