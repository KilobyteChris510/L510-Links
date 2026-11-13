<?php

namespace Tests\Feature\Models;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class LinkGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_own_multiple_link_groups(): void
    {
        $user = User::factory()->create();
        LinkGroup::factory()->count(3)->for($user)->create();
        LinkGroup::factory()->create();

        $this->assertCount(3, $user->linkGroups);
        $this->assertTrue($user->linkGroups->every(fn (LinkGroup $group) => $group->user_id === $user->id));
    }

    public function test_a_link_group_belongs_to_its_user(): void
    {
        $user = User::factory()->create();
        $group = LinkGroup::factory()->for($user)->create();

        $this->assertTrue($group->user->is($user));
    }

    public function test_a_link_group_contains_multiple_links(): void
    {
        $group = LinkGroup::factory()->create();
        Link::factory()->count(4)->for($group)->create();
        Link::factory()->create();

        $this->assertCount(4, $group->links);
        $this->assertTrue($group->links->first()->linkGroup->is($group));
    }

    public function test_links_are_ordered_by_sort_order_then_id(): void
    {
        $group = LinkGroup::factory()->create();
        $third = Link::factory()->for($group)->create(['sort_order' => 2]);
        $first = Link::factory()->for($group)->create(['sort_order' => 0]);
        $secondA = Link::factory()->for($group)->create(['sort_order' => 1]);
        $secondB = Link::factory()->for($group)->create(['sort_order' => 1]);

        $this->assertSame(
            [$first->id, $secondA->id, $secondB->id, $third->id],
            $group->links()->pluck('id')->all(),
        );
    }

    public function test_new_groups_get_defaults(): void
    {
        $group = User::factory()->create()->linkGroups()->create(['name' => 'Defaults']);

        $this->assertSame(LinkGroup::DEFAULT_THEME, $group->fresh()->theme);
        $this->assertTrue($group->fresh()->is_active);
    }

    public function test_slug_is_generated_from_the_name(): void
    {
        $group = User::factory()->create()->linkGroups()->create(['name' => 'Acme & Sons Co.']);

        $this->assertSame('acme-sons-co', $group->slug);
    }

    public function test_slugs_are_unique_when_names_collide(): void
    {
        $user = User::factory()->create();

        $slugs = collect(range(1, 3))
            ->map(fn () => $user->linkGroups()->create(['name' => 'Level Five'])->slug)
            ->all();

        $this->assertSame(['level-five', 'level-five-2', 'level-five-3'], $slugs);
    }

    public function test_slugs_of_soft_deleted_groups_are_not_reused(): void
    {
        $user = User::factory()->create();
        $original = $user->linkGroups()->create(['name' => 'Retired']);
        $original->delete();

        $replacement = $user->linkGroups()->create(['name' => 'Retired']);

        $this->assertSame('retired', $original->slug);
        $this->assertSame('retired-2', $replacement->slug);
    }

    public function test_a_random_slug_is_used_when_the_name_has_no_slug_characters(): void
    {
        $group = User::factory()->create()->linkGroups()->create(['name' => '!!!']);

        $this->assertMatchesRegularExpression('/^[a-z0-9]{8}$/', $group->slug);
    }

    public function test_long_names_produce_slugs_within_the_column_limit(): void
    {
        $group = User::factory()->create()->linkGroups()->create(['name' => str_repeat('very long name ', 15)]);

        $this->assertLessThanOrEqual(LinkGroup::SLUG_MAX_LENGTH, strlen($group->slug));
        $this->assertStringEndsNotWith('-', $group->slug);
    }

    public function test_the_database_rejects_duplicate_slugs(): void
    {
        $existing = LinkGroup::factory()->create();

        $this->expectException(QueryException::class);

        LinkGroup::query()->insert([
            'user_id' => $existing->user_id,
            'name' => 'Duplicate',
            'slug' => $existing->slug,
        ]);
    }

    public function test_slug_is_not_mass_assignable(): void
    {
        $group = User::factory()->create()->linkGroups()->create(['name' => 'Original']);

        $group->update(['name' => 'Renamed', 'slug' => 'hijacked']);

        $this->assertSame('original', $group->fresh()->slug);
        $this->assertSame('Renamed', $group->fresh()->name);
    }

    public function test_slug_cannot_be_changed_after_creation(): void
    {
        $group = LinkGroup::factory()->create();
        $slug = $group->slug;

        try {
            $group->slug = 'something-else';
            $group->save();
            $this->fail('Changing the slug should throw.');
        } catch (LogicException) {
            //
        }

        $this->assertSame($slug, $group->fresh()->slug);
    }

    public function test_soft_deleted_groups_are_excluded_from_normal_queries(): void
    {
        $user = User::factory()->create();
        $kept = LinkGroup::factory()->for($user)->create();
        $deleted = LinkGroup::factory()->for($user)->create();

        $deleted->delete();

        $this->assertSame([$kept->id], LinkGroup::pluck('id')->all());
        $this->assertSame([$kept->id], $user->linkGroups()->pluck('id')->all());
        $this->assertCount(2, LinkGroup::withTrashed()->get());
    }

    public function test_soft_deleting_a_group_keeps_its_links_for_restoration(): void
    {
        $group = LinkGroup::factory()->create();
        Link::factory()->count(2)->for($group)->create();

        $group->delete();
        $this->assertSame(2, Link::where('link_group_id', $group->id)->count());

        $group->restore();
        $this->assertCount(2, $group->fresh()->links);
    }

    public function test_force_deleting_a_group_removes_its_links(): void
    {
        $group = LinkGroup::factory()->create();
        Link::factory()->count(2)->for($group)->create();

        $group->forceDelete();

        $this->assertSame(0, Link::count());
    }

    public function test_a_user_with_link_groups_cannot_be_hard_deleted(): void
    {
        $user = User::factory()->create();
        LinkGroup::factory()->for($user)->create();

        $this->expectException(QueryException::class);

        $user->delete();
    }
}
