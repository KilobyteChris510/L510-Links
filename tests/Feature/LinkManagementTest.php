<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private LinkGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->group = LinkGroup::factory()->for($this->owner)->create(['name' => 'Acme']);
    }

    /**
     * @return array<int, Link>
     */
    private function makeLinks(int $count, ?LinkGroup $group = null): array
    {
        $links = [];

        for ($i = 0; $i < $count; $i++) {
            $links[] = Link::factory()->for($group ?? $this->group)->create([
                'title' => 'Link '.($i + 1),
                'sort_order' => $i,
            ]);
        }

        return $links;
    }

    private function orderedTitles(?LinkGroup $group = null): array
    {
        return ($group ?? $this->group)->links()->pluck('title')->all();
    }

    public function test_owners_can_list_their_groups_links_in_order(): void
    {
        [$first, $second] = $this->makeLinks(2);
        $second->forceFill(['sort_order' => -1])->save();
        Link::factory()->for($this->group)->inactive()->create(['title' => 'Hidden one', 'sort_order' => 5]);

        $this->actingAs($this->owner)
            ->get(route('groups.links.index', $this->group))
            ->assertOk()
            ->assertSee('Acme')
            ->assertSee($this->group->publicUrl())
            ->assertSee(route('groups.edit', $this->group))
            ->assertSee(route('dashboard'))
            ->assertSeeInOrder(['Link 2', 'Link 1', 'Hidden one'])
            ->assertSee('Inactive')
            ->assertSee('Drag to reorder')
            ->assertSee('Move Link 1 up');
    }

    public function test_empty_groups_show_an_empty_state(): void
    {
        $this->actingAs($this->owner)
            ->get(route('groups.links.index', $this->group))
            ->assertOk()
            ->assertSee('No links yet')
            ->assertSee('Add link');
    }

    public function test_link_text_is_escaped(): void
    {
        Link::factory()->for($this->group)->create([
            'title' => '<script>alert("t")</script>',
            'description' => '<img src=x onerror=alert(1)>',
        ]);

        $this->actingAs($this->owner)
            ->get(route('groups.links.index', $this->group))
            ->assertOk()
            ->assertDontSee('<script>alert("t")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_dashboard_and_edit_page_link_to_link_management(): void
    {
        $url = route('groups.links.index', $this->group);

        $this->actingAs($this->owner)->get(route('dashboard'))->assertSee('Manage links')->assertSee($url);
        $this->actingAs($this->owner)->get(route('groups.edit', $this->group))->assertSee('Manage links')->assertSee($url);
    }

    public function test_guests_cannot_access_link_management(): void
    {
        [$link] = $this->makeLinks(1);

        $this->get(route('groups.links.index', $this->group))->assertRedirect(route('login'));
        $this->post(route('groups.links.store', $this->group), ['title' => 'X', 'url' => 'https://x.test'])->assertRedirect(route('login'));
        $this->patch(route('groups.links.update', [$this->group, $link]), ['title' => 'X'])->assertRedirect(route('login'));
        $this->delete(route('groups.links.destroy', [$this->group, $link]))->assertRedirect(route('login'));
        $this->patchJson(route('groups.links.order', $this->group), ['links' => [$link->id]])->assertUnauthorized();

        $this->assertSame('Link 1', $link->fresh()->title);
    }

    public function test_other_users_get_not_found_for_every_operation(): void
    {
        [$a, $b] = $this->makeLinks(2);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('groups.links.index', $this->group))->assertNotFound();
        $this->actingAs($intruder)->post(route('groups.links.store', $this->group), ['title' => 'X', 'url' => 'https://x.test'])->assertNotFound();
        $this->actingAs($intruder)->patch(route('groups.links.update', [$this->group, $a]), ['title' => 'Hijacked'])->assertNotFound();
        $this->actingAs($intruder)->patch(route('groups.links.update', [$this->group, $a]), ['is_active' => '0'])->assertNotFound();
        $this->actingAs($intruder)->delete(route('groups.links.destroy', [$this->group, $a]))->assertNotFound();
        $this->actingAs($intruder)->patchJson(route('groups.links.order', $this->group), ['links' => [$b->id, $a->id]])->assertNotFound();

        $this->assertSame(['Link 1', 'Link 2'], $this->orderedTitles());
        $this->assertTrue($a->fresh()->is_active);
        $this->assertSame(2, $this->group->links()->count());
    }

    public function test_users_can_create_links_appended_to_the_end(): void
    {
        $this->makeLinks(2);
        $this->group->links()->first()->forceFill(['sort_order' => 7])->save();

        $this->actingAs($this->owner)
            ->post(route('groups.links.store', $this->group), [
                'title' => 'Book a meeting',
                'url' => 'https://example.com/book',
                'description' => 'Thirty minutes',
                'is_active' => '1',
            ])
            ->assertRedirect(route('groups.links.index', $this->group))
            ->assertSessionHas('status', 'Link added.');

        $link = Link::where('title', 'Book a meeting')->sole();
        $this->assertTrue($link->linkGroup->is($this->group));
        $this->assertSame('https://example.com/book', $link->url);
        $this->assertSame('Thirty minutes', $link->description);
        $this->assertTrue($link->is_active);
        $this->assertSame(8, $link->sort_order);
        $this->assertSame('Book a meeting', last($this->orderedTitles()));
    }

    public function test_first_link_starts_at_position_zero_and_can_be_inactive(): void
    {
        $this->actingAs($this->owner)->post(route('groups.links.store', $this->group), [
            'title' => 'Draft', 'url' => 'https://example.com', 'is_active' => '0',
        ])->assertSessionHasNoErrors();

        $link = $this->group->links()->sole();
        $this->assertSame(0, $link->sort_order);
        $this->assertFalse($link->is_active);
    }

    public function test_urls_without_a_scheme_are_normalized_to_https(): void
    {
        $this->actingAs($this->owner)->post(route('groups.links.store', $this->group), [
            'title' => 'Site', 'url' => 'www.example.com/about?x=1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://www.example.com/about?x=1', $this->group->links()->sole()->url);
    }

    public function test_valid_urls_are_stored_unchanged(): void
    {
        foreach (['http://example.com', 'HTTPS://Example.com/Path?Q=1#frag'] as $url) {
            $this->actingAs($this->owner)->post(route('groups.links.store', $this->group), [
                'title' => 'Site', 'url' => $url,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(['http://example.com', 'HTTPS://Example.com/Path?Q=1#frag'], $this->group->links()->pluck('url')->all());
    }

    public function test_invalid_and_unsafe_urls_are_rejected(): void
    {
        $urls = [
            'not a url',
            'javascript:alert(1)',
            'JavaScript:alert(document.cookie)',
            'data:text/html,<script>alert(1)</script>',
            'ftp://example.com/file',
            'mailto:someone@example.com',
            'https://'.str_repeat('a', 2050).'.com',
        ];

        foreach ($urls as $url) {
            $this->actingAs($this->owner)
                ->post(route('groups.links.store', $this->group), ['title' => 'Bad', 'url' => $url])
                ->assertSessionHasErrorsIn('createLink', 'url');
        }

        $this->assertSame(0, $this->group->links()->count());
    }

    public function test_other_invalid_input_is_rejected_and_input_is_preserved(): void
    {
        $this->actingAs($this->owner)
            ->from(route('groups.links.index', $this->group))
            ->post(route('groups.links.store', $this->group), [
                'title' => '',
                'url' => 'https://example.com',
                'description' => str_repeat('a', 1001),
                'is_active' => 'maybe',
            ])
            ->assertRedirect(route('groups.links.index', $this->group))
            ->assertSessionHasErrorsIn('createLink', ['title', 'description', 'is_active'])
            ->assertSessionHasInput('url', 'https://example.com');

        $this->actingAs($this->owner)
            ->post(route('groups.links.store', $this->group), ['title' => str_repeat('a', 256), 'url' => 'https://example.com'])
            ->assertSessionHasErrorsIn('createLink', 'title');

        $this->assertSame(0, $this->group->links()->count());
    }

    public function test_store_ignores_submitted_group_and_sort_order(): void
    {
        $otherGroup = LinkGroup::factory()->create();

        $this->actingAs($this->owner)->post(route('groups.links.store', $this->group), [
            'title' => 'Site',
            'url' => 'https://example.com',
            'link_group_id' => $otherGroup->id,
            'sort_order' => 99,
        ])->assertSessionHasNoErrors();

        $link = Link::sole();
        $this->assertSame($this->group->id, $link->link_group_id);
        $this->assertSame(0, $link->sort_order);
    }

    public function test_users_can_edit_links_without_changing_group_or_position(): void
    {
        [, $link] = $this->makeLinks(3);
        $otherGroup = LinkGroup::factory()->for($this->owner)->create();

        $this->actingAs($this->owner)
            ->patch(route('groups.links.update', [$this->group, $link]), [
                'title' => 'Renamed',
                'url' => 'https://example.org/new',
                'description' => 'Updated',
                'is_active' => '1',
                'link_group_id' => $otherGroup->id,
                'sort_order' => 50,
            ])
            ->assertRedirect(route('groups.links.index', $this->group))
            ->assertSessionHas('status', 'Link updated.');

        $link->refresh();
        $this->assertSame('Renamed', $link->title);
        $this->assertSame('https://example.org/new', $link->url);
        $this->assertSame('Updated', $link->description);
        $this->assertSame($this->group->id, $link->link_group_id);
        $this->assertSame(1, $link->sort_order);
        $this->assertSame(['Link 1', 'Renamed', 'Link 3'], $this->orderedTitles());
    }

    public function test_updates_are_validated(): void
    {
        [$link] = $this->makeLinks(1);

        $this->actingAs($this->owner)
            ->patch(route('groups.links.update', [$this->group, $link]), ['title' => '', 'url' => 'javascript:alert(1)', 'link_id' => $link->id])
            ->assertSessionHasErrorsIn('updateLink', ['title', 'url']);

        $this->assertSame('Link 1', $link->fresh()->title);
    }

    public function test_users_can_disable_and_enable_links(): void
    {
        [$link] = $this->makeLinks(1);

        $this->actingAs($this->owner)
            ->patch(route('groups.links.update', [$this->group, $link]), ['is_active' => '0'])
            ->assertSessionHas('status', 'Link disabled.');
        $this->assertFalse($link->fresh()->is_active);

        $this->actingAs($this->owner)->get(route('groups.links.index', $this->group))->assertSee('Link 1')->assertSee('Enable');

        $this->actingAs($this->owner)
            ->patch(route('groups.links.update', [$this->group, $link]), ['is_active' => '1'])
            ->assertSessionHas('status', 'Link enabled.');
        $this->assertTrue($link->fresh()->is_active);
    }

    public function test_users_can_delete_a_link_without_affecting_others(): void
    {
        [$a, $b, $c] = $this->makeLinks(3);
        $other = $this->makeLinks(1, LinkGroup::factory()->for($this->owner)->create())[0];

        $this->actingAs($this->owner)
            ->delete(route('groups.links.destroy', [$this->group, $b]))
            ->assertRedirect(route('groups.links.index', $this->group))
            ->assertSessionHas('status', 'Link deleted.');

        $this->assertModelMissing($b);
        $this->assertModelExists($other);
        $this->assertSame(['Link 1', 'Link 3'], $this->orderedTitles());
        $this->assertSame([0, 2], $this->group->links()->pluck('sort_order')->all());
    }

    public function test_links_can_be_reordered(): void
    {
        [$a, $b, $c] = $this->makeLinks(3);

        $this->actingAs($this->owner)
            ->patchJson(route('groups.links.order', $this->group), ['links' => [$c->id, $a->id, $b->id]])
            ->assertOk()
            ->assertJson(['message' => 'Link order saved.']);

        $this->assertSame(['Link 3', 'Link 1', 'Link 2'], $this->orderedTitles());
        $this->assertSame([0, 1, 2], $this->group->links()->pluck('sort_order')->all());

        $this->actingAs($this->owner)
            ->get(route('groups.links.index', $this->group))
            ->assertSeeInOrder(['Link 3', 'Link 1', 'Link 2']);
    }

    public function test_invalid_ordering_payloads_are_rejected(): void
    {
        [$a, $b, $c] = $this->makeLinks(3);
        $foreign = Link::factory()->create();

        $payloads = [
            'missing payload' => [],
            'not an array' => ['links' => 'abc'],
            'missing id' => ['links' => [$a->id, $b->id]],
            'duplicate id' => ['links' => [$a->id, $a->id, $b->id]],
            'foreign id' => ['links' => [$a->id, $b->id, $foreign->id]],
            'extra id' => ['links' => [$a->id, $b->id, $c->id, $foreign->id]],
            'non-integer' => ['links' => [$a->id, $b->id, 'x']],
        ];

        foreach ($payloads as $case => $payload) {
            $this->actingAs($this->owner)
                ->patchJson(route('groups.links.order', $this->group), $payload)
                ->assertUnprocessable()
                ->assertJsonStructure(['message', 'errors']);
        }

        $this->assertSame(['Link 1', 'Link 2', 'Link 3'], $this->orderedTitles());
        $this->assertSame(0, $foreign->fresh()->sort_order);
    }

    public function test_links_from_another_group_cannot_be_reordered(): void
    {
        [$a, $b] = $this->makeLinks(2);
        $ownOtherGroup = LinkGroup::factory()->for($this->owner)->create();
        [$x, $y] = $this->makeLinks(2, $ownOtherGroup);

        $this->actingAs($this->owner)
            ->patchJson(route('groups.links.order', $this->group), ['links' => [$y->id, $x->id]])
            ->assertUnprocessable();

        $this->assertSame([0, 1], [$x->fresh()->sort_order, $y->fresh()->sort_order]);
        $this->assertSame(['Link 1', 'Link 2'], $this->orderedTitles());
    }

    public function test_cross_group_updates_and_deletes_return_not_found(): void
    {
        $this->makeLinks(1);
        $ownOtherGroup = LinkGroup::factory()->for($this->owner)->create();
        [$otherLink] = $this->makeLinks(1, $ownOtherGroup);
        $foreignLink = Link::factory()->create(['title' => 'Foreign']);

        foreach ([$otherLink, $foreignLink] as $link) {
            $this->actingAs($this->owner)
                ->patch(route('groups.links.update', [$this->group, $link]), ['title' => 'Hijacked'])
                ->assertNotFound();
            $this->actingAs($this->owner)
                ->delete(route('groups.links.destroy', [$this->group, $link]))
                ->assertNotFound();
        }

        $this->assertSame('Link 1', $otherLink->fresh()->title);
        $this->assertSame('Foreign', $foreignLink->fresh()->title);
    }

    public function test_soft_deleted_groups_cannot_have_their_links_managed(): void
    {
        [$a, $b] = $this->makeLinks(2);
        $this->group->delete();

        $this->actingAs($this->owner)->get(route('groups.links.index', $this->group))->assertNotFound();
        $this->actingAs($this->owner)->post(route('groups.links.store', $this->group), ['title' => 'X', 'url' => 'https://x.test'])->assertNotFound();
        $this->actingAs($this->owner)->patch(route('groups.links.update', [$this->group, $a]), ['title' => 'Changed'])->assertNotFound();
        $this->actingAs($this->owner)->delete(route('groups.links.destroy', [$this->group, $a]))->assertNotFound();
        $this->actingAs($this->owner)->patchJson(route('groups.links.order', $this->group), ['links' => [$b->id, $a->id]])->assertNotFound();

        $this->assertSame(['Link 1', 'Link 2'], Link::orderBy('sort_order')->pluck('title')->all());
    }

    public function test_non_numeric_link_ids_do_not_match_routes(): void
    {
        $this->actingAs($this->owner)
            ->delete('/groups/'.$this->group->id.'/links/abc')
            ->assertNotFound();
    }
}
