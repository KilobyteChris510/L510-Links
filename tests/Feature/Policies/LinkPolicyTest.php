<?php

namespace Tests\Feature\Policies;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_owners_can_manage_links_in_their_groups(): void
    {
        $owner = User::factory()->create();
        $group = LinkGroup::factory()->for($owner)->create();
        $link = Link::factory()->for($group)->create();

        $this->assertTrue($owner->can('create', [Link::class, $group]));
        $this->assertTrue($owner->can('view', $link));
        $this->assertTrue($owner->can('update', $link));
        $this->assertTrue($owner->can('delete', $link));
    }

    public function test_users_cannot_manage_links_in_another_users_group(): void
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->for($group)->create();
        $intruder = User::factory()->create();
        LinkGroup::factory()->for($intruder)->create();

        $this->assertFalse($intruder->can('create', [Link::class, $group]));
        $this->assertFalse($intruder->can('view', $link));
        $this->assertFalse($intruder->can('update', $link));
        $this->assertFalse($intruder->can('delete', $link));
    }

    public function test_links_in_soft_deleted_groups_cannot_be_managed(): void
    {
        $owner = User::factory()->create();
        $group = LinkGroup::factory()->for($owner)->create();
        $link = Link::factory()->for($group)->create();

        $group->delete();
        $link->refresh();

        $this->assertFalse($owner->can('create', [Link::class, $group]));
        $this->assertFalse($owner->can('update', $link));
        $this->assertFalse($owner->can('delete', $link));
    }
}
