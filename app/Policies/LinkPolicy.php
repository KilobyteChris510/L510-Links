<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Links are authorized through their group's owner. Links in soft-deleted groups
 * resolve no group and are therefore denied. Denials are 404s, like LinkGroupPolicy.
 */
class LinkPolicy
{
    public function viewAny(User $user, LinkGroup $linkGroup): Response
    {
        return $this->ownsGroup($user, $linkGroup);
    }

    public function create(User $user, LinkGroup $linkGroup): Response
    {
        return $this->ownsGroup($user, $linkGroup);
    }

    public function reorder(User $user, LinkGroup $linkGroup): Response
    {
        return $this->ownsGroup($user, $linkGroup);
    }

    public function view(User $user, Link $link): Response
    {
        return $this->ownsGroup($user, $link->linkGroup);
    }

    public function update(User $user, Link $link): Response
    {
        return $this->ownsGroup($user, $link->linkGroup);
    }

    public function delete(User $user, Link $link): Response
    {
        return $this->ownsGroup($user, $link->linkGroup);
    }

    private function ownsGroup(User $user, ?LinkGroup $linkGroup): Response
    {
        return $linkGroup && ! $linkGroup->trashed() && $linkGroup->user()->is($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
