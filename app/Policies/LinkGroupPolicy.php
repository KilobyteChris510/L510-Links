<?php

namespace App\Policies;

use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Non-owners are denied with a 404 so the existence of other users' groups is not revealed.
 */
class LinkGroupPolicy
{
    /**
     * Any authenticated user may list link groups; queries must be scoped to their own.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LinkGroup $linkGroup): Response
    {
        return $this->owner($user, $linkGroup);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LinkGroup $linkGroup): Response
    {
        return $this->owner($user, $linkGroup);
    }

    public function delete(User $user, LinkGroup $linkGroup): Response
    {
        return $this->owner($user, $linkGroup);
    }

    public function restore(User $user, LinkGroup $linkGroup): Response
    {
        return $this->owner($user, $linkGroup);
    }

    /**
     * Permanent deletion would free the slug, which may already be written to an NFC card.
     */
    public function forceDelete(User $user, LinkGroup $linkGroup): bool
    {
        return false;
    }

    private function owner(User $user, LinkGroup $linkGroup): Response
    {
        return $linkGroup->user()->is($user) ? Response::allow() : Response::denyAsNotFound();
    }
}
