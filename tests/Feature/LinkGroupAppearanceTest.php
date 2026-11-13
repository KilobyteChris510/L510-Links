<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LinkGroupAppearanceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private LinkGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->owner = User::factory()->create();
        $this->group = LinkGroup::factory()->for($this->owner)->create(['name' => 'Acme', 'description' => 'Our links']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = [], string $theme = 'light'): array
    {
        return [
            'theme' => $theme,
            'appearance' => array_merge(LinkGroup::themeDefaults($theme), $overrides),
        ];
    }

    private function save(array $data): TestResponse
    {
        return $this->actingAs($this->owner)->put(route('groups.appearance.update', $this->group), $data);
    }

    private function publicPage(): TestResponse
    {
        auth()->logout();

        return $this->get('/p/acme')->assertOk();
    }

    public function test_owners_can_open_the_editor_with_a_preview_of_real_content(): void
    {
        Link::factory()->for($this->group)->create(['title' => 'Second', 'sort_order' => 2]);
        Link::factory()->for($this->group)->create(['title' => 'First', 'sort_order' => 1]);
        Link::factory()->for($this->group)->inactive()->create(['title' => 'Hidden link', 'sort_order' => 0]);

        $this->actingAs($this->owner)
            ->get(route('groups.appearance.edit', $this->group))
            ->assertOk()
            ->assertSee(['Light', 'Dark', 'Warm', 'Rounded', 'Pill', 'Square'])
            ->assertSee(['Background', 'Text', 'Button background', 'Button text'])
            ->assertSee('Profile image')
            ->assertSee('Save changes')
            ->assertSee('data-preview', false)
            ->assertSee('$event.preventDefault()', false)
            ->assertSee('Our links')
            ->assertSeeInOrder(['Preview', 'Acme', 'First', 'Second'])
            ->assertDontSee('Hidden link')
            ->assertSee(route('groups.links.index', $this->group));
    }

    public function test_management_pages_link_to_the_appearance_editor(): void
    {
        $url = route('groups.appearance.edit', $this->group);

        $this->actingAs($this->owner)->get(route('dashboard'))->assertSee($url);
        $this->actingAs($this->owner)->get(route('groups.edit', $this->group))->assertSee($url);
        $this->actingAs($this->owner)->get(route('groups.links.index', $this->group))->assertSee($url);
    }

    public function test_guests_cannot_use_the_editor(): void
    {
        $this->get(route('groups.appearance.edit', $this->group))->assertRedirect(route('login'));
        $this->put(route('groups.appearance.update', $this->group), $this->payload([], 'dark'))->assertRedirect(route('login'));

        $this->assertSame('light', $this->group->fresh()->theme);
    }

    public function test_other_users_receive_not_found(): void
    {
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('groups.appearance.edit', $this->group))->assertNotFound();
        $this->actingAs($intruder)
            ->put(route('groups.appearance.update', $this->group), [...$this->payload([], 'dark'), 'avatar' => UploadedFile::fake()->image('a.png')])
            ->assertNotFound();

        $this->assertSame('light', $this->group->fresh()->theme);
        $this->assertNull($this->group->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_soft_deleted_groups_cannot_be_customized(): void
    {
        $this->group->delete();

        $this->actingAs($this->owner)->get(route('groups.appearance.edit', $this->group))->assertNotFound();
        $this->save($this->payload([], 'dark'))->assertNotFound();

        $this->assertSame('light', LinkGroup::withTrashed()->find($this->group->id)->theme);
    }

    public function test_owners_can_save_a_theme_with_overrides(): void
    {
        $this->save($this->payload(['text_color' => '#ABCDEF', 'button_shape' => 'square'], 'dark'))
            ->assertRedirect(route('groups.appearance.edit', $this->group))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Appearance saved.');

        $group = $this->group->fresh();
        $this->assertSame('dark', $group->theme);
        $this->assertSame(['text_color' => '#abcdef', 'button_shape' => 'square'], $group->appearance);
        $this->assertSame(
            [...LinkGroup::themeDefaults('dark'), 'text_color' => '#abcdef', 'button_shape' => 'square'],
            $group->appearanceSettings(),
        );

        $editor = $this->actingAs($this->owner)
            ->get(route('groups.appearance.edit', $this->group))
            ->assertSee('value="#abcdef"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/name="theme" value="dark"[^>]*checked/', $editor);
        $this->assertMatchesRegularExpression('/name="appearance\[button_shape\]" value="square"[^>]*checked/', $editor);
    }

    public function test_switching_themes_applies_the_new_theme_defaults(): void
    {
        $this->save($this->payload(['background_color' => '#123456'], 'dark'));
        $this->assertSame(['background_color' => '#123456'], $this->group->fresh()->appearance);

        $this->save($this->payload([], 'warm'))->assertSessionHasNoErrors();

        $group = $this->group->fresh();
        $this->assertSame('warm', $group->theme);
        $this->assertNull($group->appearance);
        $this->assertSame(LinkGroup::themeDefaults('warm'), $group->appearanceSettings());
    }

    public function test_invalid_themes_are_rejected(): void
    {
        foreach (['neon', '', 'default', ['light']] as $theme) {
            $this->save(['theme' => $theme, 'appearance' => LinkGroup::themeDefaults('light')])->assertSessionHasErrors('theme');
        }

        $this->assertSame('light', $this->group->fresh()->theme);
    }

    public function test_invalid_colors_are_rejected(): void
    {
        $bad = ['red', '#fff', '#12345g', '123456', '#1234567', '#123456;background:url(x)', 'url(javascript:alert(1))', '</style><script>', ''];

        foreach ($bad as $color) {
            $this->save($this->payload(['background_color' => $color]))->assertSessionHasErrors('appearance.background_color');
        }

        $this->assertNull($this->group->fresh()->appearance);
    }

    public function test_invalid_button_shapes_are_rejected(): void
    {
        foreach (['circle', '', 'rounded; border:0'] as $shape) {
            $this->save($this->payload(['button_shape' => $shape]))->assertSessionHasErrors('appearance.button_shape');
        }

        $this->assertNull($this->group->fresh()->appearance);
    }

    public function test_unsupported_and_missing_appearance_keys_are_rejected(): void
    {
        $this->save($this->payload(['custom_css' => 'body{display:none}']))->assertSessionHasErrors('appearance');
        $this->save($this->payload(['font_family' => 'Comic Sans']))->assertSessionHasErrors('appearance');
        $this->save(['theme' => 'light'])->assertSessionHasErrors('appearance');

        $partial = $this->payload();
        unset($partial['appearance']['text_color']);
        $this->save($partial)->assertSessionHasErrors('appearance.text_color');

        $this->assertNull($this->group->fresh()->appearance);
    }

    public function test_groups_without_settings_render_with_default_theme(): void
    {
        DB::table('link_groups')->where('id', $this->group->id)->update(['theme' => 'default', 'appearance' => null]);
        $group = $this->group->fresh();

        $this->assertSame('light', $group->appearanceTheme());
        $this->assertSame(LinkGroup::themeDefaults('light'), $group->appearanceSettings());

        $this->publicPage()->assertSee('--lg-background-color: '.config('appearance.themes.light.background_color'), false);
        $this->assertSame('light', LinkGroup::factory()->create()->fresh()->theme);
    }

    public function test_malformed_stored_settings_are_ignored_when_rendering(): void
    {
        DB::table('link_groups')->where('id', $this->group->id)->update([
            'theme' => 'dark',
            'appearance' => json_encode([
                'background_color' => 'red;}</style><script>alert(1)</script>',
                'button_shape' => 'blob',
                'evil' => 'x',
                'text_color' => '#00FF00',
            ]),
        ]);

        $this->assertSame(
            [...LinkGroup::themeDefaults('dark'), 'text_color' => '#00ff00'],
            $this->group->fresh()->appearanceSettings(),
        );

        $this->publicPage()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('blob')
            ->assertSee('--lg-text-color: #00ff00', false);
    }

    public function test_saved_appearance_renders_on_the_public_page_without_javascript(): void
    {
        Link::factory()->for($this->group)->create(['title' => 'Visit us', 'url' => 'https://example.com']);
        $this->save($this->payload(['button_background_color' => '#336699', 'button_shape' => 'pill'], 'warm'));

        $warm = LinkGroup::themeDefaults('warm');

        $this->publicPage()
            ->assertSee('--lg-background-color: '.$warm['background_color'], false)
            ->assertSee('--lg-text-color: '.$warm['text_color'], false)
            ->assertSee('--lg-button-background-color: #336699', false)
            ->assertSee('--lg-button-radius: '.config('appearance.button_shapes.pill.radius'), false)
            ->assertSee('<meta name="theme-color" content="'.$warm['background_color'].'">', false)
            ->assertSee('href="https://example.com"', false)
            ->assertSee('Visit us')
            ->assertDontSee('<script', false);
    }

    public function test_appearance_updates_cannot_change_other_group_data_or_links(): void
    {
        $link = Link::factory()->for($this->group)->create(['title' => 'Keep', 'sort_order' => 3]);
        $other = User::factory()->create();
        $before = $this->group->only(['user_id', 'slug', 'name', 'description', 'is_active']);

        $this->save([
            ...$this->payload([], 'dark'),
            'user_id' => $other->id,
            'slug' => 'stolen',
            'name' => 'Hijacked',
            'description' => 'Changed',
            'is_active' => '0',
            'links' => [['id' => $link->id, 'title' => 'Changed', 'sort_order' => 0]],
        ])->assertSessionHasNoErrors();

        $group = $this->group->fresh();
        $this->assertSame('dark', $group->theme);
        $this->assertSame($before, $group->only(['user_id', 'slug', 'name', 'description', 'is_active']));
        $this->assertSame(['Keep', 3], [$link->fresh()->title, $link->fresh()->sort_order]);
        $this->get('/p/acme')->assertOk();
    }

    public function test_valid_images_are_normalized_and_stored(): void
    {
        foreach (['photo.jpg', 'logo.png', 'pic.webp'] as $name) {
            $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image($name, 900, 600)])->assertSessionHasNoErrors();

            $path = $this->group->fresh()->avatar_path;
            $this->assertMatchesRegularExpression('#^avatars/[0-9a-f-]{36}\.webp$#', $path);
            Storage::disk('public')->assertExists($path);

            $info = getimagesizefromstring(Storage::disk('public')->get($path));
            $this->assertSame([512, 512, 'image/webp'], [$info[0], $info[1], $info['mime']]);
        }

        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->publicPage()->assertSee('src="'.Storage::disk('public')->url($this->group->fresh()->avatar_path).'"', false);
    }

    public function test_small_images_are_not_upscaled(): void
    {
        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('small.png', 120, 80)])->assertSessionHasNoErrors();

        $info = getimagesizefromstring(Storage::disk('public')->get($this->group->fresh()->avatar_path));
        $this->assertSame([80, 80], [$info[0], $info[1]]);
    }

    public function test_invalid_image_types_are_rejected(): void
    {
        $textFile = tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($textFile, "<?php echo 'not an image';");
        $svgFile = tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>');

        $files = [
            UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->image('animation.gif'),
            new UploadedFile($textFile, 'disguised.png', null, null, true),
            new UploadedFile($svgFile, 'vector.png', null, null, true),
            UploadedFile::fake()->createWithContent('fake.jpg', 'definitely not jpeg data'),
        ];

        foreach ($files as $file) {
            $this->save([...$this->payload(), 'avatar' => $file])->assertSessionHasErrors('avatar');
        }

        $this->assertNull($this->group->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_oversized_images_are_rejected(): void
    {
        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('big.jpg', 800, 800)->size(2049)])
            ->assertSessionHasErrors(['avatar' => 'The profile image must be 2 MB or smaller.']);

        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('huge.png', 4001, 10)])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($this->group->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_images_can_be_replaced_and_the_old_file_is_deleted(): void
    {
        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('one.png', 300, 300)]);
        $first = $this->group->fresh()->avatar_path;

        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('two.png', 300, 300)])->assertSessionHasNoErrors();
        $second = $this->group->fresh()->avatar_path;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_failed_replacements_keep_the_existing_image(): void
    {
        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('one.png', 300, 300)]);
        $first = $this->group->fresh()->avatar_path;

        $this->save([...$this->payload(['text_color' => 'nope']), 'avatar' => UploadedFile::fake()->image('two.png', 300, 300)])
            ->assertSessionHasErrors('appearance.text_color');
        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->createWithContent('bad.png', 'garbage')])
            ->assertSessionHasErrors('avatar');

        $this->assertSame($first, $this->group->fresh()->avatar_path);
        $this->assertSame([$first], Storage::disk('public')->allFiles());
    }

    public function test_images_can_be_removed(): void
    {
        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('one.png', 300, 300)]);
        $path = $this->group->fresh()->avatar_path;

        $this->save([...$this->payload(), 'remove_avatar' => '1'])->assertSessionHasNoErrors();

        $this->assertNull($this->group->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
        $this->publicPage()->assertDontSee('<img', false);
    }

    public function test_uploading_and_removing_at_once_is_rejected(): void
    {
        $this->save([...$this->payload(), 'avatar' => UploadedFile::fake()->image('one.png', 300, 300)]);
        $path = $this->group->fresh()->avatar_path;

        $this->save([...$this->payload(), 'remove_avatar' => '1', 'avatar' => UploadedFile::fake()->image('two.png', 300, 300)])
            ->assertSessionHasErrors('avatar');

        $this->assertSame($path, $this->group->fresh()->avatar_path);
        $this->assertSame([$path], Storage::disk('public')->allFiles());
    }

    public function test_files_outside_the_avatar_directory_are_never_deleted(): void
    {
        Storage::disk('public')->put('logos/shared.png', UploadedFile::fake()->image('shared.png')->getContent());
        $this->group->update(['avatar_path' => 'logos/shared.png']);

        $this->save([...$this->payload(), 'remove_avatar' => '1'])->assertSessionHasNoErrors();

        $this->assertNull($this->group->fresh()->avatar_path);
        Storage::disk('public')->assertExists('logos/shared.png');
    }
}
