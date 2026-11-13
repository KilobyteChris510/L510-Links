<?php

namespace App\Policies;

use App\Models\LinkGroup;
use App\Models\User;

class LinkGroupPolicy
{
    /**
     * Any authenticated user may list link groups; queries must be scoped to their own.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LinkGroup $linkGroup): bool
    {
        return $linkGroup->user()->is($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LinkGroup $linkGroup): bool
    {
        return $linkGroup->user()->is($user);
    }

    public function delete(User $user, LinkGroup $linkGroup): bool
    {
        return $linkGroup->user()->is($user);
    }

    public function restore(User $user, LinkGroup $linkGroup): bool
    {
        return $linkGroup->user()->is($user);
    }

    /**
     * Permanent deletion would free the slug, which may already be written to an NFC card.
     */
    public function forceDelete(User $user, LinkGroup $linkGroup): bool
    {
        return false;
    }
}
