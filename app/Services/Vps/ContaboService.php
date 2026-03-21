<?php

declare(strict_types=1);

namespace App\Services\Vps;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Contabo API Service
 *
 * Wraps the Contabo REST API for use in Laravel (Blade) applications.
 * Covers: Authentication, Instances, Instance Actions, Snapshots,
 *         Images, Object Storage, Private Networks, Secrets, Tags, Users.
 *
 * Configuration (add to config/services.php or .env):
 *   CONTABO_CLIENT_ID
 *   CONTABO_CLIENT_SECRET
 *   CONTABO_API_USER       (your Contabo login email)
 *   CONTABO_API_PASSWORD   (API password set in Customer Control Panel)
 */
class ContaboService
{
    protected string $authUrl = 'https://auth.contabo.com/auth/realms/contabo/protocol/openid-connect/token';

    protected string $baseUrl = 'https://api.contabo.com/v1';

    protected string $tokenCacheKey = 'contabo_access_token';

    // ─────────────────────────────────────────────
    // Auth
    // ─────────────────────────────────────────────

    /**
     * Fetch (or return cached) OAuth2 access token.
     */
    public function getAccessToken(): string
    {
        return Cache::remember($this->tokenCacheKey, 280, function () {
            $response = Http::asForm()->post($this->authUrl, [
                'client_id' => config('contabo.client_id'),
                'client_secret' => config('contabo.client_secret'),
                'username' => config('contabo.api_user'),
                'password' => config('contabo.api_password'),
                'grant_type' => 'password',
            ]);

            $this->assertSuccess($response, 'Authentication failed');

            return $response->json('access_token');
        });
    }

    /**
     * Force a fresh token (e.g. after a 401).
     */
    public function refreshToken(): string
    {
        Cache::forget($this->tokenCacheKey);

        return $this->getAccessToken();
    }

    // ─────────────────────────────────────────────
    // Instances
    // ─────────────────────────────────────────────

    /**
     * List all instances with optional filters.
     *
     * @param array{
     *   page?: int,
     *   size?: int,
     *   name?: string,
     *   displayName?: string,
     *   dataCenter?: string,
     *   region?: string,
     *   status?: string,
     *   productIds?: string,
     *   productTypes?: string,
     *   search?: string,
     * } $filters
     *
     * @throws ConnectionException
     */
    public function listInstances(array $filters = []): array
    {
        $response = $this->client()->get('/compute/instances', $filters);
        $this->assertSuccess($response, 'List instances');

        return $response->json();
    }

    /**
     * Get a single instance by ID.
     *
     * @throws ConnectionException
     */
    public function getInstance(int $instanceId): array
    {
        $response = $this->client()->get('/compute/instances/'.$instanceId);
        $this->assertSuccess($response, 'Get instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * Create a new instance.
     *
     * @param array{
     *   imageId?: string,
     *   productId?: string,
     *   region?: string,
     *   sshKeys?: int[],
     *   rootPassword?: int,
     *   userData?: string,
     *   license?: string,
     *   period: int,
     *   displayName?: string,
     *   defaultUser?: string,
     *   addOns?: array,
     *   applicationId?: string,
     * } $payload
     *
     * @throws ConnectionException
     */
    public function createInstance(array $payload): array
    {
        $response = $this->client()->post('/compute/instances', $payload);
        $this->assertSuccess($response, 'Create instance');

        return $response->json('data.0');
    }

    /**
     * Update the display name of an instance.
     *
     * @throws ConnectionException
     */
    public function updateInstance(int $instanceId, string $displayName): array
    {
        $response = $this->client()->patch('/compute/instances/'.$instanceId, [
            'displayName' => $displayName,
        ]);
        $this->assertSuccess($response, 'Update instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * Reinstall an instance with a new image.
     *
     * @param array{
     *   imageId: string,
     *   sshKeys?: int[],
     *   rootPassword?: int,
     *   userData?: string,
     *   defaultUser?: string,
     *   applicationId?: string,
     * } $payload
     *
     * @throws ConnectionException
     */
    public function reinstallInstance(int $instanceId, array $payload): array
    {
        $response = $this->client()->put('/compute/instances/'.$instanceId, $payload);
        $this->assertSuccess($response, 'Reinstall instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * Cancel (schedule deletion of) an instance.
     *
     * @throws ConnectionException
     */
    public function cancelInstance(int $instanceId, ?string $cancelDate = null): array
    {
        $response = $this->client()->post(sprintf('/compute/instances/%d/cancel', $instanceId), [
            'cancelDate' => $cancelDate,
        ]);
        $this->assertSuccess($response, 'Cancel instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * Upgrade an instance with add-ons (e.g. private networking, backup).
     *
     * @param  array{privateNetworking?: array, backup?: array}  $addOns
     *
     * @throws ConnectionException
     */
    public function upgradeInstance(int $instanceId, array $addOns): array
    {
        $response = $this->client()->post(sprintf('/compute/instances/%d/upgrade', $instanceId), $addOns);
        $this->assertSuccess($response, 'Upgrade instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * List instance audit history.
     *
     * @throws ConnectionException
     */
    public function listInstanceAudits(array $filters = []): array
    {
        $response = $this->client()->get('/compute/instances/audits', $filters);
        $this->assertSuccess($response, 'List instance audits');

        return $response->json();
    }

    /**
     * Start an instance.
     *
     * @throws ConnectionException
     */
    public function startInstance(int $instanceId): array
    {
        return $this->instanceAction($instanceId, 'start');
    }

    /**
     * Stop (force power-off) an instance.
     *
     * @throws ConnectionException
     */
    public function stopInstance(int $instanceId): array
    {
        return $this->instanceAction($instanceId, 'stop');
    }

    /**
     * Restart an instance.
     *
     * @throws ConnectionException
     */
    public function restartInstance(int $instanceId): array
    {
        return $this->instanceAction($instanceId, 'restart');
    }

    /**
     * Gracefully shut down an instance via ACPI.
     *
     * @throws ConnectionException
     */
    public function shutdownInstance(int $instanceId): array
    {
        return $this->instanceAction($instanceId, 'shutdown');
    }

    /**
     * Boot into rescue mode.
     *
     * @param  array{rootPassword?: int, sshKeys?: int[], userData?: string}  $payload
     *
     * @throws ConnectionException
     */
    public function rescueInstance(int $instanceId, array $payload = []): array
    {
        $response = $this->client()->post(
            sprintf('/compute/instances/%d/actions/rescue', $instanceId),
            $payload
        );
        $this->assertSuccess($response, 'Rescue instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * Reset the password of an instance.
     *
     * @param  array{sshKeys?: int[], rootPassword?: int, userData?: string}  $payload
     *
     * @throws ConnectionException
     */
    public function resetInstancePassword(int $instanceId, array $payload): array
    {
        $response = $this->client()->post(
            sprintf('/compute/instances/%d/actions/resetPassword', $instanceId),
            $payload
        );
        $this->assertSuccess($response, 'Reset password for instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * List snapshots for an instance.
     *
     * @throws ConnectionException
     */
    public function listSnapshots(int $instanceId, array $filters = []): array
    {
        $response = $this->client()->get(sprintf('/compute/instances/%d/snapshots', $instanceId), $filters);
        $this->assertSuccess($response, 'List snapshots for instance #'.$instanceId);

        return $response->json();
    }

    /**
     * Create a snapshot for an instance.
     *
     * @throws ConnectionException
     */
    public function createSnapshot(int $instanceId, string $name, string $description = ''): array
    {
        $response = $this->client()->post(sprintf('/compute/instances/%d/snapshots', $instanceId), [
            'name' => $name,
            'description' => $description,
        ]);
        $this->assertSuccess($response, 'Create snapshot for instance #'.$instanceId);

        return $response->json('data.0');
    }

    /**
     * Get a specific snapshot.
     *
     * @throws ConnectionException
     */
    public function getSnapshot(int $instanceId, string $snapshotId): array
    {
        $response = $this->client()->get(sprintf('/compute/instances/%d/snapshots/%s', $instanceId, $snapshotId));
        $this->assertSuccess($response, 'Get snapshot '.$snapshotId);

        return $response->json('data.0');
    }

    /**
     * Update a snapshot's name or description.
     *
     * @throws ConnectionException
     */
    public function updateSnapshot(int $instanceId, string $snapshotId, array $payload): array
    {
        $response = $this->client()->patch(
            sprintf('/compute/instances/%d/snapshots/%s', $instanceId, $snapshotId),
            $payload
        );
        $this->assertSuccess($response, 'Update snapshot '.$snapshotId);

        return $response->json('data.0');
    }

    /**
     * Delete a snapshot.
     *
     * @throws ConnectionException
     */
    public function deleteSnapshot(int $instanceId, string $snapshotId): bool
    {
        $response = $this->client()->delete(
            sprintf('/compute/instances/%d/snapshots/%s', $instanceId, $snapshotId)
        );
        $this->assertSuccess($response, 'Delete snapshot '.$snapshotId);

        return true;
    }

    /**
     * Revert an instance to a snapshot.
     *
     * @throws ConnectionException
     */
    public function revertSnapshot(int $instanceId, string $snapshotId): array
    {
        $response = $this->client()->post(
            sprintf('/compute/instances/%d/snapshots/%s/rollback', $instanceId, $snapshotId)
        );
        $this->assertSuccess($response, sprintf('Revert instance #%d to snapshot %s', $instanceId, $snapshotId));

        return $response->json('data.0');
    }

    /**
     * List all available images (standard + custom).
     *
     * @throws ConnectionException
     */
    public function listImages(array $filters = []): array
    {
        $response = $this->client()->get('/compute/images', $filters);
        $this->assertSuccess($response, 'List images');

        return $response->json();
    }

    /**
     * Get details of a specific image.
     *
     * @throws ConnectionException
     */
    public function getImage(string $imageId): array
    {
        $response = $this->client()->get('/compute/images/'.$imageId);
        $this->assertSuccess($response, 'Get image '.$imageId);

        return $response->json('data.0');
    }

    /**
     * Upload a custom image.
     *
     * @param  array{name: string, description?: string, url: string, osType: string, version: string}  $payload
     *
     * @throws ConnectionException
     */
    public function createImage(array $payload): array
    {
        $response = $this->client()->post('/compute/images', $payload);
        $this->assertSuccess($response, 'Create custom image');

        return $response->json('data.0');
    }

    /**
     * Update the name of a custom image.
     *
     * @throws ConnectionException
     */
    public function updateImage(string $imageId, string $name): array
    {
        $response = $this->client()->patch('/compute/images/'.$imageId, ['name' => $name]);
        $this->assertSuccess($response, 'Update image '.$imageId);

        return $response->json('data.0');
    }

    /**
     * Delete a custom image.
     *
     * @throws ConnectionException
     */
    public function deleteImage(string $imageId): bool
    {
        $response = $this->client()->delete('/compute/images/'.$imageId);
        $this->assertSuccess($response, 'Delete image '.$imageId);

        return true;
    }

    /**
     * Get custom image usage statistics.
     *
     * @throws ConnectionException
     */
    public function getImageStats(): array
    {
        $response = $this->client()->get('/compute/images/stats');
        $this->assertSuccess($response, 'Get image stats');

        return $response->json();
    }

    /**
     * List all object storages.
     *
     * @throws ConnectionException
     */
    public function listObjectStorages(array $filters = []): array
    {
        $response = $this->client()->get('/object-storages', $filters);
        $this->assertSuccess($response, 'List object storages');

        return $response->json();
    }

    /**
     * Get a specific object storage.
     *
     * @throws ConnectionException
     */
    public function getObjectStorage(string $objectStorageId): array
    {
        $response = $this->client()->get('/object-storages/'.$objectStorageId);
        $this->assertSuccess($response, 'Get object storage '.$objectStorageId);

        return $response->json('data.0');
    }

    /**
     * Create a new object storage.
     *
     * @param  array{region: string, totalPurchasedSpaceTB: int, displayName?: string}  $payload
     *
     * @throws ConnectionException
     */
    public function createObjectStorage(array $payload): array
    {
        $response = $this->client()->post('/object-storages', $payload);
        $this->assertSuccess($response, 'Create object storage');

        return $response->json('data.0');
    }

    /**
     * Update the display name of an object storage.
     *
     * @throws ConnectionException
     */
    public function updateObjectStorage(string $objectStorageId, string $displayName): array
    {
        $response = $this->client()->patch('/object-storages/'.$objectStorageId, [
            'displayName' => $displayName,
        ]);
        $this->assertSuccess($response, 'Update object storage '.$objectStorageId);

        return $response->json('data.0');
    }

    /**
     * Cancel an object storage.
     *
     * @throws ConnectionException
     */
    public function cancelObjectStorage(string $objectStorageId): array
    {
        $response = $this->client()->patch(sprintf('/object-storages/%s/cancel', $objectStorageId));
        $this->assertSuccess($response, 'Cancel object storage '.$objectStorageId);

        return $response->json('data.0');
    }

    /**
     * Get usage statistics for an object storage.
     *
     * @throws ConnectionException
     */
    public function getObjectStorageStats(string $objectStorageId): array
    {
        $response = $this->client()->get(sprintf('/object-storages/%s/stats', $objectStorageId));
        $this->assertSuccess($response, 'Object storage stats '.$objectStorageId);

        return $response->json();
    }

    /**
     * List all private networks.
     *
     * @throws ConnectionException
     */
    public function listPrivateNetworks(array $filters = []): array
    {
        $response = $this->client()->get('/private-networks', $filters);
        $this->assertSuccess($response, 'List private networks');

        return $response->json();
    }

    /**
     * Get a specific private network.
     */
    public function getPrivateNetwork(string $privateNetworkId): array
    {
        $response = $this->client()->get('/private-networks/'.$privateNetworkId);
        $this->assertSuccess($response, 'Get private network '.$privateNetworkId);

        return $response->json('data.0');
    }

    /**
     * Create a new private network.
     *
     * @param  array{name: string, description?: string, region: string}  $payload
     *
     * @throws ConnectionException
     */
    public function createPrivateNetwork(array $payload): array
    {
        $response = $this->client()->post('/private-networks', $payload);
        $this->assertSuccess($response, 'Create private network');

        return $response->json('data.0');
    }

    /**
     * Update a private network.
     *
     * @throws ConnectionException
     */
    public function updatePrivateNetwork(string $privateNetworkId, array $payload): array
    {
        $response = $this->client()->patch('/private-networks/'.$privateNetworkId, $payload);
        $this->assertSuccess($response, 'Update private network '.$privateNetworkId);

        return $response->json('data.0');
    }

    /**
     * Delete a private network.
     *
     * @throws ConnectionException
     */
    public function deletePrivateNetwork(string $privateNetworkId): bool
    {
        $response = $this->client()->delete('/private-networks/'.$privateNetworkId);
        $this->assertSuccess($response, 'Delete private network '.$privateNetworkId);

        return true;
    }

    /**
     * Assign an instance to a private network.
     *
     * @throws ConnectionException
     */
    public function assignInstanceToPrivateNetwork(string $privateNetworkId, int $instanceId): array
    {
        $response = $this->client()->post(sprintf('/private-networks/%s/instances', $privateNetworkId), [
            'instanceId' => $instanceId,
        ]);
        $this->assertSuccess($response, sprintf('Assign instance #%d to private network %s', $instanceId, $privateNetworkId));

        return $response->json('data.0');
    }

    /**
     * Remove an instance from a private network.
     *
     * @throws ConnectionException
     */
    public function removeInstanceFromPrivateNetwork(string $privateNetworkId, int $instanceId): bool
    {
        $response = $this->client()->delete(
            sprintf('/private-networks/%s/instances/%d', $privateNetworkId, $instanceId)
        );
        $this->assertSuccess($response, sprintf('Remove instance #%d from private network %s', $instanceId, $privateNetworkId));

        return true;
    }

    /**
     * List all secrets (SSH keys / passwords).
     *
     * @throws ConnectionException
     */
    public function listSecrets(array $filters = []): array
    {
        $response = $this->client()->get('/secrets', $filters);
        $this->assertSuccess($response, 'List secrets');

        return $response->json();
    }

    /**
     * Get a specific secret.
     *
     * @throws ConnectionException
     */
    public function getSecret(int $secretId): array
    {
        $response = $this->client()->get('/secrets/'.$secretId);
        $this->assertSuccess($response, 'Get secret #'.$secretId);

        return $response->json('data.0');
    }

    /**
     * Create a new secret.
     *
     * @param  array{name: string, type: 'ssh'|'password', value: string}  $payload
     *
     * @throws ConnectionException
     */
    public function createSecret(array $payload): array
    {
        $response = $this->client()->post('/secrets', $payload);
        $this->assertSuccess($response, 'Create secret');

        return $response->json('data.0');
    }

    /**
     * Update a secret's name or value.
     *
     * @throws ConnectionException
     */
    public function updateSecret(int $secretId, array $payload): array
    {
        $response = $this->client()->patch('/secrets/'.$secretId, $payload);
        $this->assertSuccess($response, 'Update secret #'.$secretId);

        return $response->json('data.0');
    }

    /**
     * Delete a secret.
     *
     * @throws ConnectionException
     */
    public function deleteSecret(int $secretId): bool
    {
        $response = $this->client()->delete('/secrets/'.$secretId);
        $this->assertSuccess($response, 'Delete secret #'.$secretId);

        return true;
    }

    /**
     * List all tags.
     *
     * @throws ConnectionException
     */
    public function listTags(array $filters = []): array
    {
        $response = $this->client()->get('/tags', $filters);
        $this->assertSuccess($response, 'List tags');

        return $response->json();
    }

    /**
     * Get a specific tag.
     *
     * @throws ConnectionException
     */
    public function getTag(int $tagId): array
    {
        $response = $this->client()->get('/tags/'.$tagId);
        $this->assertSuccess($response, 'Get tag #'.$tagId);

        return $response->json('data.0');
    }

    /**
     * Create a new tag.
     *
     * @param  array{name: string, color?: string}  $payload
     *
     * @throws ConnectionException
     */
    public function createTag(array $payload): array
    {
        $response = $this->client()->post('/tags', $payload);
        $this->assertSuccess($response, 'Create tag');

        return $response->json('data.0');
    }

    /**
     * Update a tag.
     *
     * @throws ConnectionException
     */
    public function updateTag(int $tagId, array $payload): array
    {
        $response = $this->client()->patch('/tags/'.$tagId, $payload);
        $this->assertSuccess($response, 'Update tag #'.$tagId);

        return $response->json('data.0');
    }

    /**
     * Delete a tag.
     *
     * @throws ConnectionException
     */
    public function deleteTag(int $tagId): bool
    {
        $response = $this->client()->delete('/tags/'.$tagId);
        $this->assertSuccess($response, 'Delete tag #'.$tagId);

        return true;
    }

    /**
     * List tag assignments.
     *
     * @throws ConnectionException
     */
    public function listTagAssignments(int $tagId, array $filters = []): array
    {
        $response = $this->client()->get(sprintf('/tags/%d/assignments', $tagId), $filters);
        $this->assertSuccess($response, 'List assignments for tag #'.$tagId);

        return $response->json();
    }

    /**
     * Assign a tag to a resource.
     *
     * @param  string  $resourceType  e.g. 'instance', 'image', 'object-storage'
     * @param  string  $resourceId  The ID of the resource
     *
     * @throws ConnectionException
     */
    public function assignTag(int $tagId, string $resourceType, string $resourceId): array
    {
        $response = $this->client()->post(sprintf('/tags/%d/assignments/%s/%s', $tagId, $resourceType, $resourceId));
        $this->assertSuccess($response, sprintf('Assign tag #%d to %s/%s', $tagId, $resourceType, $resourceId));

        return $response->json('data.0');
    }

    /**
     * Remove a tag assignment from a resource.
     *
     * @throws ConnectionException
     */
    public function removeTagAssignment(int $tagId, string $resourceType, string $resourceId): bool
    {
        $response = $this->client()->delete(sprintf('/tags/%d/assignments/%s/%s', $tagId, $resourceType, $resourceId));
        $this->assertSuccess($response, sprintf('Remove tag #%d from %s/%s', $tagId, $resourceType, $resourceId));

        return true;
    }

    /**
     * List all users.
     *
     * @throws ConnectionException
     */
    public function listUsers(array $filters = []): array
    {
        $response = $this->client()->get('/users', $filters);
        $this->assertSuccess($response, 'List users');

        return $response->json();
    }

    /**
     * Get a specific user.
     *
     * @throws ConnectionException
     */
    public function getUser(string $userId): array
    {
        $response = $this->client()->get('/users/'.$userId);
        $this->assertSuccess($response, 'Get user '.$userId);

        return $response->json('data.0');
    }

    /**
     * Get the authenticated client (current user info).
     *
     * @throws ConnectionException
     */
    public function getClient(): array
    {
        $response = $this->client()->get('/users/client');
        $this->assertSuccess($response, 'Get client');

        return $response->json('data.0');
    }

    /**
     * Create a new user.
     *
     * @param array{
     *   firstName: string,
     *   lastName: string,
     *   email: string,
     *   enabled?: bool,
     *   totp?: bool,
     *   admin?: bool,
     *   accessAllResources?: bool,
     *   roles?: int[],
     * } $payload
     *
     * @throws ConnectionException
     */
    public function createUser(array $payload): array
    {
        $response = $this->client()->post('/users', $payload);
        $this->assertSuccess($response, 'Create user');

        return $response->json('data.0');
    }

    /**
     * Update a user.
     *
     * @throws ConnectionException
     */
    public function updateUser(string $userId, array $payload): array
    {
        $response = $this->client()->patch('/users/'.$userId, $payload);
        $this->assertSuccess($response, 'Update user '.$userId);

        return $response->json('data.0');
    }

    /**
     * Delete a user.
     *
     * @throws ConnectionException
     */
    public function deleteUser(string $userId): bool
    {
        $response = $this->client()->delete('/users/'.$userId);
        $this->assertSuccess($response, 'Delete user '.$userId);

        return true;
    }

    /**
     * Send a password reset email to a user.
     *
     * @throws ConnectionException
     */
    public function sendPasswordResetEmail(string $userId): bool
    {
        $response = $this->client()->post(sprintf('/users/%s/resetPassword', $userId));
        $this->assertSuccess($response, 'Password reset for user '.$userId);

        return true;
    }

    /**
     * Resend the email verification for a user.
     *
     * @throws ConnectionException
     */
    public function resendEmailVerification(string $userId): bool
    {
        $response = $this->client()->post(sprintf('/users/%s/resendEmailVerification', $userId));
        $this->assertSuccess($response, 'Resend email verification for user '.$userId);

        return true;
    }

    /**
     * Get the S3 object storage credentials for a user.
     *
     * @throws ConnectionException
     */
    public function getUserObjectStorageCredentials(string $userId): array
    {
        $response = $this->client()->get(sprintf('/users/%s/object-storages/credentials', $userId));
        $this->assertSuccess($response, 'Get S3 credentials for user '.$userId);

        return $response->json();
    }

    /**
     * List all roles.
     *
     * @throws ConnectionException
     */
    public function listRoles(array $filters = []): array
    {
        $response = $this->client()->get('/roles', $filters);
        $this->assertSuccess($response, 'List roles');

        return $response->json();
    }

    /**
     * Get a specific role.
     *
     * @throws ConnectionException
     */
    public function getRole(int $roleId): array
    {
        $response = $this->client()->get('/roles/'.$roleId);
        $this->assertSuccess($response, 'Get role #'.$roleId);

        return $response->json('data.0');
    }

    /**
     * Create a new role.
     *
     * @param  array{name: string, admin?: bool, accessAllResources?: bool, permissions?: array}  $payload
     *
     * @throws ConnectionException
     */
    public function createRole(array $payload): array
    {
        $response = $this->client()->post('/roles', $payload);
        $this->assertSuccess($response, 'Create role');

        return $response->json('data.0');
    }

    /**
     * Update a role.
     *
     * @throws ConnectionException
     */
    public function updateRole(int $roleId, array $payload): array
    {
        $response = $this->client()->put('/roles/'.$roleId, $payload);
        $this->assertSuccess($response, 'Update role #'.$roleId);

        return $response->json('data.0');
    }

    /**
     * Delete a role.
     *
     * @throws ConnectionException
     */
    public function deleteRole(int $roleId): bool
    {
        $response = $this->client()->delete('/roles/'.$roleId);
        $this->assertSuccess($response, 'Delete role #'.$roleId);

        return true;
    }

    /**
     * List available API permissions.
     *
     * @throws ConnectionException
     */
    public function listApiPermissions(): array
    {
        $response = $this->client()->get('/roles/api-permissions');
        $this->assertSuccess($response, 'List API permissions');

        return $response->json();
    }

    // ─────────────────────────────────────────────
    // HTTP Client Helper
    // ─────────────────────────────────────────────

    protected function client(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->withHeaders([
                'x-request-id' => (string) Str::uuid(),
                'Content-Type' => 'application/json',
            ])
            ->baseUrl($this->baseUrl);
    }

    protected function assertSuccess(Response $response, string $context = 'Request'): void
    {
        if ($response->failed()) {
            $message = $response->json('message') ?? $response->body();
            throw new RuntimeException(sprintf('%s: [%d] %s', $context, $response->status(), $message));
        }
    }

    /**
     * @throws ConnectionException
     */
    protected function instanceAction(int $instanceId, string $action): array
    {
        $response = $this->client()->post(sprintf('/compute/instances/%d/actions/%s', $instanceId, $action));
        $this->assertSuccess($response, ucfirst($action).(' instance #'.$instanceId));

        return $response->json('data.0');
    }
}
