<?php

namespace Tests\Feature\Policies;

use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkGroupPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_list_and_create_link_groups(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('viewAny', LinkGroup::class));
        $this->assertTrue($user->can('create', LinkGroup::class));
    }

    public function test_owners_can_manage_their_own_groups(): void
    {
        $owner = User::factory()->create();
        $group = LinkGroup::factory()->for($owner)->create();

        $this->assertTrue($owner->can('view', $group));
        $this->assertTrue($owner->can('update', $group));
        $this->assertTrue($owner->can('delete', $group));
        $this->assertTrue($owner->can('restore', $group));
    }

    public function test_users_cannot_access_another_users_groups(): void
    {
        $group = LinkGroup::factory()->create();
        $intruder = User::factory()->create();

        $this->assertFalse($intruder->can('view', $group));
        $this->assertFalse($intruder->can('update', $group));
        $this->assertFalse($intruder->can('delete', $group));
        $this->assertFalse($intruder->can('restore', $group));
    }

    public function test_nobody_can_permanently_delete_a_group(): void
    {
        $owner = User::factory()->create();
        $group = LinkGroup::factory()->for($owner)->create();

        $this->assertFalse($owner->can('forceDelete', $group));
    }
}
