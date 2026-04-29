<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Actions\Vps\ChangeVpsDisplayNameAction;
use App\Actions\Vps\CreateVpsSnapshotAction;
use App\Actions\Vps\DeleteVpsSnapshotAction;
use App\Actions\Vps\ReinstallVpsAction;
use App\Actions\Vps\RescueVpsAction;
use App\Actions\Vps\ResetVpsCredentialsAction;
use App\Actions\Vps\RestartVpsAction;
use App\Actions\Vps\RestoreVpsSnapshotAction;
use App\Actions\Vps\ShutdownVpsAction;
use App\Actions\Vps\StartVpsAction;
use App\Enums\Vps\VpsInstanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeVpsDisplayNameRequest;
use App\Http\Requests\Admin\CreateVpsSnapshotRequest;
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
    public function __construct(private readonly ContaboService $contaboService) {}

    public function index(): View|Factory
    {
        abort_if(Gate::denies('vps_access'), Response::HTTP_FORBIDDEN);

        $instances = [];
        $errorMessage = '';

        try {
            $subscriptions = Subscription::query()
                ->where('user_id', auth()->id())
                ->whereNotNull('provider_resource_id')
                ->with('user', 'plan')
                ->get();

            if ($subscriptions->isEmpty()) {
                return view('user.vps.index', [
                    'instances' => $instances,
                    'errorMessage' => $errorMessage,
                ]);
            }

            $apiInstances = collect($this->contaboService->listAllInstances());

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
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'status_color' => $status->color(),
                    'status_icon' => $status->icon(),
                    'ip_address' => $ipAddresses ?: ($apiInstance['ipConfig']['v4']['ip'] ?? 'N/A'),
                    'plan_name' => $subscription->plan?->name ?? 'N/A',
                ];
            })->filter()->values()->toArray();
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to load VPS instances', ['error' => $runtimeException->getMessage()]);
            $errorMessage = 'Failed to load VPS instances. Please try again.';
        }

        return view('user.vps.index', [
            'instances' => $instances,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function show(Subscription $subscription): View|Factory
    {
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $subscription->load(['plan', 'planPrice.currency']);

        $instance = [];
        $snapshots = [];
        $backups = [];
        $backupError = null;
        $errorMessage = '';

        try {
            if (! $subscription->provider_resource_id) {
                $errorMessage = 'No VPS instance linked to this subscription yet. It will be assigned shortly.';

                return view('user.vps.show', [
                    'subscription' => $subscription,
                    'instance' => $instance,
                    'snapshots' => $snapshots,
                    'backups' => $backups,
                    'backupError' => $backupError,
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
                $backupResponse = $this->contaboService->listInstanceBackups((int) $subscription->provider_resource_id);
                $backups = $backupResponse['data'] ?? [];
            }
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to load VPS instance', ['error' => $runtimeException->getMessage()]);
            $errorMessage = 'Failed to load VPS instance details.';
        }

        return view('user.vps.show', [
            'subscription' => $subscription,
            'instance' => $instance,
            'snapshots' => $snapshots,
            'backups' => $backups,
            'backupError' => $backupError,
            'maxSnapshots' => $maxSnapshots ?? null,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function start(Subscription $subscription, StartVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_start'), Response::HTTP_FORBIDDEN);
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

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
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

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
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success']) {
            $redirect->with('pending_refresh', true);
        }

        return $redirect;
    }

    public function rescue(Subscription $subscription, RescueVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_rescue'), Response::HTTP_FORBIDDEN);
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

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
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription);

        $redirect = back()->with($result['success'] ? 'success' : 'error', $result['message']);

        if ($result['success'] && isset($result['password'])) {
            $redirect->with('generated_password', $result['password']);
        }

        return $redirect;
    }

    public function changeDisplayName(ChangeVpsDisplayNameRequest $request, Subscription $subscription, ChangeVpsDisplayNameAction $action): RedirectResponse
    {
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription, $request->validated('display_name'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function createSnapshot(CreateVpsSnapshotRequest $request, Subscription $subscription, CreateVpsSnapshotAction $action): RedirectResponse
    {
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

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
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription, $snapshotId);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function restoreSnapshot(Subscription $subscription, string $snapshotId, RestoreVpsSnapshotAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_backup_restore'), Response::HTTP_FORBIDDEN);
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription, $snapshotId);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function reinstall(Request $request, Subscription $subscription, ReinstallVpsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('vps_reinstall'), Response::HTTP_FORBIDDEN);
        abort_if($subscription->user_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $result = $action->execute($subscription, $request->only(['imageId']));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
