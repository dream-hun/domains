<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

final class DomainPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Domain $domain): bool
    {
        // User can view their own domains or admin can view all
        return $user->id === $domain->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Domain $domain): bool
    {
        // User can update their own domains or admin can update all
        return $user->id === $domain->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can renew the model.
     */
    public function renew(User $user, Domain $domain): bool
    {
        // User can renew their own domains or admin can renew all
        return $user->id === $domain->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can transfer the model.
     */
    public function transfer(User $user, Domain $domain): bool
    {
        // User can transfer their own domains or admin can transfer all
        return $user->id === $domain->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update nameservers for the model.
     */
    public function updateNameservers(User $user, Domain $domain): bool
    {
        // User can update nameservers for their own domains or admin can update all
        return $user->id === $domain->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Domain $domain): bool
    {
        // Only admin can delete domains
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Domain $domain): bool
    {
        // Only admin can restore domains
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Domain $domain): bool
    {
        // Only admin can force delete domains
        return $user->isAdmin();
    }
}
