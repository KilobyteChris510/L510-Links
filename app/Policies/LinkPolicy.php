<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;

/**
 * Links are authorized through their group's owner. Links in soft-deleted groups
 * resolve no group and are therefore denied.
 */
class LinkPolicy
{
    public function create(User $user, LinkGroup $linkGroup): bool
    {
        return ! $linkGroup->trashed() && $linkGroup->user()->is($user);
    }

    public function view(User $user, Link $link): bool
    {
        return $this->ownsGroup($user, $link);
    }

    public function update(User $user, Link $link): bool
    {
        return $this->ownsGroup($user, $link);
    }

    public function delete(User $user, Link $link): bool
    {
        return $this->ownsGroup($user, $link);
    }

    private function ownsGroup(User $user, Link $link): bool
    {
        return $link->linkGroup?->user()->is($user) ?? false;
    }
}
