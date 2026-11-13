<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_group_management_routes(): void
    {
        $group = LinkGroup::factory()->create();

        $this->get(route('groups.create'))->assertRedirect(route('login'));
        $this->post(route('groups.store'), ['name' => 'Acme'])->assertRedirect(route('login'));
        $this->get(route('groups.edit', $group))->assertRedirect(route('login'));
        $this->put(route('groups.update', $group), ['name' => 'Changed'])->assertRedirect(route('login'));
        $this->delete(route('groups.destroy', $group))->assertRedirect(route('login'));

        $this->assertDatabaseCount('link_groups', 1);
        $this->assertSame($group->name, $group->fresh()->name);
        $this->assertNotSoftDeleted($group);
    }

    public function test_dashboard_lists_the_users_groups(): void
    {
        $user = User::factory()->create();
        $active = LinkGroup::factory()->for($user)->create(['name' => 'Acme Sales', 'description' => 'Our sales team']);
        $inactive = LinkGroup::factory()->for($user)->inactive()->create(['name' => 'Old Campaign']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Create Link Group')
            ->assertSee('Acme Sales')
            ->assertSee('Our sales team')
            ->assertSee($active->publicUrl())
            ->assertSee('Old Campaign')
            ->assertSee($inactive->publicUrl())
            ->assertSee('Active')
            ->assertSee('Inactive')
            ->assertSee('Copy link')
            ->assertSee(route('groups.edit', $active))
            ->assertSee(route('groups.destroy', $inactive));
    }

    public function test_dashboard_shows_an_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No link groups yet')
            ->assertSee(route('groups.create'));
    }

    public function test_dashboard_hides_other_users_and_deleted_groups(): void
    {
        $user = User::factory()->create();
        LinkGroup::factory()->for($user)->create(['name' => 'Mine']);
        LinkGroup::factory()->create(['name' => 'Someone Elses']);
        LinkGroup::factory()->for($user)->create(['name' => 'Deleted One'])->delete();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Someone Elses')
            ->assertDontSee('Deleted One');
    }

    public function test_dashboard_paginates_groups(): void
    {
        $user = User::factory()->create();
        LinkGroup::factory()->for($user)->count(12)->create();

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertCount(10, $response->viewData('groups'));
        $response->assertSee('?page=2', false);
    }

    public function test_create_form_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('groups.create'))
            ->assertOk()
            ->assertSee('Create link group')
            ->assertSee('name="name"', false)
            ->assertDontSee('name="slug"', false);
    }

    public function test_users_can_create_groups(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Acme Sales',
            'description' => 'Contact links for the sales team',
            'is_active' => '1',
        ]);

        $group = LinkGroup::sole();
        $response->assertRedirect(route('groups.edit', $group))->assertSessionHas('status', 'Link group created.');

        $this->assertTrue($group->user->is($user));
        $this->assertSame('acme-sales', $group->slug);
        $this->assertSame('Contact links for the sales team', $group->description);
        $this->assertTrue($group->is_active);
    }

    public function test_users_can_create_inactive_groups(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('groups.store'), ['name' => 'Draft', 'is_active' => '0'])
            ->assertSessionHasNoErrors();

        $this->assertFalse(LinkGroup::sole()->is_active);
    }

    public function test_invalid_submissions_produce_validation_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('groups.create'))
            ->post(route('groups.store'), [
                'name' => '',
                'description' => str_repeat('a', 1001),
                'is_active' => 'maybe',
            ])
            ->assertRedirect(route('groups.create'))
            ->assertSessionHasErrors(['name', 'description', 'is_active']);

        $this->actingAs($user)
            ->post(route('groups.store'), ['name' => str_repeat('a', 256)])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('link_groups', 0);
    }

    public function test_duplicate_names_produce_unique_slugs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('groups.store'), ['name' => 'Acme']);
        $this->actingAs($user)->post(route('groups.store'), ['name' => 'Acme']);
        $this->actingAs(User::factory()->create())->post(route('groups.store'), ['name' => 'ACME']);

        $this->assertSame(['acme', 'acme-2', 'acme-3'], LinkGroup::orderBy('id')->pluck('slug')->all());
    }

    public function test_concurrent_slug_collisions_are_retried(): void
    {
        $user = User::factory()->create();
        LinkGroup::factory()->create(['name' => 'Acme']);

        // Simulate a concurrent request winning the race: the first insert attempt
        // uses a slug that is already taken, so the unique index rejects it.
        $raced = false;
        LinkGroup::creating(function (LinkGroup $group) use (&$raced) {
            if (! $raced) {
                $raced = true;
                $group->slug = 'acme';
            }
        });

        $this->actingAs($user)
            ->post(route('groups.store'), ['name' => 'Acme'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertTrue($raced);
        $this->assertSame('acme-2', $user->linkGroups()->sole()->slug);
    }

    public function test_slug_retries_are_bounded(): void
    {
        LinkGroup::factory()->create(['name' => 'Acme']);
        LinkGroup::creating(fn (LinkGroup $group) => $group->slug = 'acme');

        $this->expectException(UniqueConstraintViolationException::class);

        LinkGroup::factory()->for(User::factory())->make(['name' => 'Acme'])->saveWithUniqueSlug();
    }

    public function test_edit_form_shows_the_permanent_url(): void
    {
        $user = User::factory()->create();
        $group = LinkGroup::factory()->for($user)->create(['name' => 'Acme']);

        $this->actingAs($user)
            ->get(route('groups.edit', $group))
            ->assertOk()
            ->assertSee('Permanent public URL')
            ->assertSee(url('/p/acme'))
            ->assertSee('Copy link')
            ->assertSee('View public page')
            ->assertDontSee('name="slug"', false);
    }

    public function test_users_can_update_their_groups_without_changing_the_slug(): void
    {
        $user = User::factory()->create();
        $group = LinkGroup::factory()->for($user)->create(['name' => 'Acme']);

        $this->actingAs($user)
            ->from(route('groups.edit', $group))
            ->put(route('groups.update', $group), [
                'name' => 'Acme Renamed',
                'description' => 'New description',
                'is_active' => '1',
            ])
            ->assertRedirect(route('groups.edit', $group))
            ->assertSessionHas('status', 'Link group updated.');

        $group->refresh();
        $this->assertSame('Acme Renamed', $group->name);
        $this->assertSame('New description', $group->description);
        $this->assertSame('acme', $group->slug);
        $this->assertSame(url('/p/acme'), $group->publicUrl());
    }

    public function test_updates_are_validated(): void
    {
        $user = User::factory()->create();
        $group = LinkGroup::factory()->for($user)->create(['name' => 'Acme']);

        $this->actingAs($user)
            ->put(route('groups.update', $group), ['name' => '', 'is_active' => 'maybe'])
            ->assertSessionHasErrors(['name', 'is_active']);

        $this->assertSame('Acme', $group->fresh()->name);
    }

    public function test_users_can_deactivate_and_activate_groups(): void
    {
        $user = User::factory()->create();
        $group = LinkGroup::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('groups.update', $group), ['is_active' => '0'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Link group deactivated.');

        $this->assertFalse($group->fresh()->is_active);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee($group->name)
            ->assertSee('Activate');

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('groups.update', $group), ['is_active' => '1'])
            ->assertSessionHas('status', 'Link group activated.');

        $this->assertTrue($group->fresh()->is_active);
    }

    public function test_users_can_soft_delete_their_groups(): void
    {
        $user = User::factory()->create();
        $group = LinkGroup::factory()->for($user)->create(['name' => 'Acme']);
        Link::factory()->for($group)->count(3)->create();

        $this->actingAs($user)
            ->delete(route('groups.destroy', $group))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Link group deleted.');

        $this->assertSoftDeleted($group);
        $this->assertSame(3, Link::where('link_group_id', $group->id)->count());

        $this->actingAs($user)->get(route('groups.edit', $group))->assertNotFound();

        $this->actingAs($user)->post(route('groups.store'), ['name' => 'Acme']);
        $this->assertSame('acme-2', $user->linkGroups()->sole()->slug);
    }

    public function test_users_cannot_access_another_users_groups(): void
    {
        $owner = User::factory()->create();
        $group = LinkGroup::factory()->for($owner)->create(['name' => 'Acme', 'is_active' => true]);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('groups.edit', $group))->assertNotFound();
        $this->actingAs($intruder)->put(route('groups.update', $group), ['name' => 'Hijacked'])->assertNotFound();
        $this->actingAs($intruder)->put(route('groups.update', $group), ['is_active' => '0'])->assertNotFound();
        $this->actingAs($intruder)->delete(route('groups.destroy', $group))->assertNotFound();

        $group->refresh();
        $this->assertSame('Acme', $group->name);
        $this->assertTrue($group->is_active);
        $this->assertNotSoftDeleted($group);
    }

    public function test_submissions_cannot_set_ownership_or_slug(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Acme',
            'slug' => 'custom-slug',
            'user_id' => $other->id,
        ]);

        $group = LinkGroup::sole();
        $this->assertTrue($group->user->is($user));
        $this->assertSame('acme', $group->slug);

        $this->actingAs($user)->put(route('groups.update', $group), [
            'name' => 'Acme',
            'slug' => 'changed-slug',
            'user_id' => $other->id,
        ])->assertSessionHasNoErrors();

        $group->refresh();
        $this->assertTrue($group->user->is($user));
        $this->assertSame('acme', $group->slug);
    }
}
