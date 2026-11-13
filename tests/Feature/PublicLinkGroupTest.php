<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicLinkGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private LinkGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Olivia Owner', 'email' => 'olivia@example.com']);
        $this->group = LinkGroup::factory()->for($this->owner)->create([
            'name' => 'Acme Studio',
            'description' => 'Design and print shop.',
            'avatar_path' => null,
        ]);
    }

    private function link(string $title, int $sortOrder, array $attributes = []): Link
    {
        $link = Link::factory()->for($this->group)->create(['title' => $title, 'description' => null, ...$attributes]);
        $link->forceFill(['sort_order' => $sortOrder])->save();

        return $link;
    }

    public function test_guests_can_view_active_groups(): void
    {
        $this->assertSame(url('/p/acme-studio'), $this->group->publicUrl());
        $this->assertSame(route('public.groups.show', 'acme-studio'), $this->group->publicUrl());

        $this->get('/p/acme-studio')
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Acme Studio')
            ->assertSee('Design and print shop.');

        $this->assertGuest();
    }

    public function test_signed_in_users_see_the_same_public_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/p/acme-studio')->assertOk()->assertSee('Acme Studio');
    }

    public function test_unknown_slugs_return_not_found(): void
    {
        $this->get('/p/does-not-exist')->assertNotFound();
        $this->get('/p/ACME-STUDIO')->assertNotFound();
        $this->get('/p/')->assertNotFound();
    }

    public function test_inactive_groups_return_not_found_and_reactivation_restores_the_url(): void
    {
        $this->group->update(['is_active' => false]);
        $this->get('/p/acme-studio')->assertNotFound();

        $this->group->update(['is_active' => true]);
        $this->get('/p/acme-studio')->assertOk()->assertSee('Acme Studio');
    }

    public function test_soft_deleted_groups_return_not_found(): void
    {
        $this->group->delete();

        $this->get('/p/acme-studio')->assertNotFound();
    }

    public function test_only_active_links_are_shown_in_order(): void
    {
        $this->link('Third', 2, ['url' => 'https://example.com/3']);
        $this->link('First', 0, ['url' => 'https://example.com/1', 'description' => 'Start here']);
        $this->link('Hidden', 1, ['url' => 'https://example.com/hidden', 'is_active' => false]);
        $tieA = $this->link('Tie A', 5, ['url' => 'https://example.com/a']);
        $tieB = $this->link('Tie B', 5, ['url' => 'https://example.com/b']);
        $this->assertLessThan($tieB->id, $tieA->id);

        $this->get('/p/acme-studio')
            ->assertOk()
            ->assertSeeInOrder(['First', 'Start here', 'Third', 'Tie A', 'Tie B'])
            ->assertSee('href="https://example.com/1"', false)
            ->assertDontSee('Hidden')
            ->assertDontSee('https://example.com/hidden');
    }

    public function test_links_open_in_the_same_tab_with_the_stored_url(): void
    {
        $this->link('Query', 0, ['url' => 'https://example.com/path?a=1&b=two#frag']);

        $this->get('/p/acme-studio')
            ->assertSee('href="https://example.com/path?a=1&amp;b=two#frag"', false)
            ->assertDontSee('target="_blank"', false);
    }

    public function test_user_generated_text_is_escaped(): void
    {
        $this->group->update(['name' => '<b>Acme</b>', 'description' => '<script>alert(1)</script>']);
        $this->link('<img src=x onerror=alert(1)>', 0, ['url' => 'https://example.com', 'description' => '<i>desc</i>']);

        $this->get('/p/acme-studio')
            ->assertOk()
            ->assertDontSee('<b>Acme</b>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertDontSee('<i>desc</i>', false)
            ->assertSee('&lt;b&gt;Acme&lt;/b&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_unsafe_stored_urls_are_not_rendered_as_links(): void
    {
        $unsafe = ['javascript:alert(1)', 'JAVASCRIPT:alert(1)', 'data:text/html,hi', 'ftp://example.com', '//evil.example', 'not a url', 'https://'];

        foreach ($unsafe as $i => $url) {
            // Bypass validation to simulate bad data already in the database.
            DB::table('links')->insert([
                'link_group_id' => $this->group->id, 'title' => 'Unsafe '.$i, 'url' => $url,
                'sort_order' => $i, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->link('Safe', 99, ['url' => 'https://example.com/safe']);

        $response = $this->get('/p/acme-studio')->assertOk()->assertSee('Unsafe 0')->assertSee('currently unavailable');

        foreach ($unsafe as $url) {
            $response->assertDontSee('href="'.e($url).'"', false);
        }
        $response->assertSee('href="https://example.com/safe"', false);
        $this->assertSame(1, preg_match_all('/<a\s/', $response->getContent()));
    }

    public function test_groups_without_active_links_show_an_empty_state(): void
    {
        $this->link('Disabled', 0, ['is_active' => false]);

        $this->get('/p/acme-studio')
            ->assertOk()
            ->assertSee('Acme Studio')
            ->assertSee('Design and print shop.')
            ->assertSee('no links here yet')
            ->assertDontSee('Disabled');
    }

    public function test_renaming_a_group_keeps_its_public_url(): void
    {
        $this->actingAs($this->owner)->put(route('groups.update', $this->group), ['name' => 'Acme Renamed', 'description' => 'New text']);
        auth()->logout();

        $this->get('/p/acme-studio')->assertOk()->assertSee('Acme Renamed')->assertSee('New text')->assertDontSee('Acme Studio');
        $this->get('/p/acme-renamed')->assertNotFound();
    }

    public function test_dashboard_link_changes_appear_on_the_next_load(): void
    {
        $a = $this->link('Alpha', 0, ['url' => 'https://example.com/a']);
        $b = $this->link('Beta', 1, ['url' => 'https://example.com/b']);
        $c = $this->link('Gamma', 2, ['url' => 'https://example.com/c']);

        $this->get('/p/acme-studio')->assertSeeInOrder(['Alpha', 'Beta', 'Gamma']);

        $this->actingAs($this->owner);
        $this->patchJson(route('groups.links.order', $this->group), ['links' => [$c->id, $a->id, $b->id]])->assertOk();
        $this->patch(route('groups.links.update', [$this->group, $a]), ['title' => 'Alpha Edited', 'url' => 'https://example.com/new']);
        $this->patch(route('groups.links.update', [$this->group, $b]), ['is_active' => '0']);
        $this->post(route('groups.links.store', $this->group), ['title' => 'Delta', 'url' => 'https://example.com/d']);
        $this->delete(route('groups.links.destroy', [$this->group, $c]));
        auth()->logout();

        $this->get('/p/acme-studio')
            ->assertSeeInOrder(['Alpha Edited', 'Delta'])
            ->assertSee('href="https://example.com/new"', false)
            ->assertDontSee('Beta')
            ->assertDontSee('Gamma');
    }

    public function test_public_pages_expose_no_dashboard_controls_or_private_data(): void
    {
        $link = $this->link('Alpha', 0, ['url' => 'https://example.com/a']);

        $response = $this->get('/p/acme-studio')->assertOk();

        foreach ([
            'Olivia Owner', 'olivia@example.com', 'Log out', 'Manage links', 'Delete', 'Edit',
            'csrf', '<form', '<script', route('dashboard'), route('groups.edit', $this->group),
            '/groups/'.$this->group->id, 'data-id="'.$link->id.'"',
        ] as $text) {
            $response->assertDontSee($text, false);
        }

        $response->assertCookieMissing(config('session.cookie'))->assertCookieMissing('XSRF-TOKEN');
        $this->assertSame(0, DB::table('sessions')->count());
    }

    public function test_metadata_uses_group_details_and_canonical_url(): void
    {
        $this->get('/p/acme-studio')
            ->assertSee('<title>Acme Studio</title>', false)
            ->assertSee('<meta name="description" content="Design and print shop.">', false)
            ->assertSee('<meta property="og:title" content="Acme Studio">', false)
            ->assertSee('<meta property="og:description" content="Design and print shop.">', false)
            ->assertSee('<link rel="canonical" href="'.url('/p/acme-studio').'">', false)
            ->assertSee('name="viewport"', false);

        $this->group->update(['description' => null]);

        $this->get('/p/acme-studio')->assertOk()->assertDontSee('name="description"', false)->assertDontSee('og:description', false);
    }

    public function test_avatar_is_shown_only_when_the_file_exists(): void
    {
        Storage::fake('public');

        $this->group->update(['avatar_path' => 'avatars/missing.png']);
        $this->get('/p/acme-studio')->assertOk()->assertDontSee('<img', false);

        UploadedFile::fake()->image('logo.png')->storeAs('avatars', 'logo.png', 'public');
        $this->group->update(['avatar_path' => 'avatars/logo.png']);
        $this->get('/p/acme-studio')
            ->assertSee('src="'.Storage::disk('public')->url('avatars/logo.png').'"', false)
            ->assertSee('alt="Profile image for Acme Studio"', false);

        Storage::disk('public')->put('avatars/notes.txt', 'not an image');
        $this->group->update(['avatar_path' => 'avatars/notes.txt']);
        $this->get('/p/acme-studio')->assertDontSee('<img', false);

        $this->group->update(['avatar_path' => '../.env']);
        $this->get('/p/acme-studio')->assertDontSee('<img', false);
    }
}
