<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Tests\TestCase;

class LinkGroupShareTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private LinkGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->group = LinkGroup::factory()->for($this->owner)->create(['name' => 'Acme Studio']);
    }

    /**
     * The decoder is memory-hungry, so large images are scaled down (nearest neighbour,
     * keeping modules crisp) before decoding.
     */
    private function decodePng(string $png): string
    {
        $image = imagecreatefromstring($png);

        if (imagesx($image) > 400) {
            $image = imagescale($image, 400, 400, IMG_NEAREST_NEIGHBOUR);
            ob_start();
            imagepng($image);
            $png = (string) ob_get_clean();
        }

        return (new QRCode)->readFromBlob($png)->data;
    }

    private function decodeSvg(string $svg): string
    {
        if (! extension_loaded('imagick') || Imagick::queryFormats('SVG') === []) {
            $this->markTestSkipped('Decoding SVG requires Imagick with SVG support.');
        }

        $image = new Imagick;
        $image->setResolution(800, 800);
        $image->readImageBlob($svg);
        $image->setImageFormat('png');

        return $this->decodePng($image->getImageBlob());
    }

    /**
     * @return array<string, string>
     */
    private function routes(LinkGroup $group): array
    {
        return [
            'share' => route('groups.share.show', $group),
            'svg' => route('groups.qr.svg', $group),
            'png' => route('groups.qr.png', $group),
        ];
    }

    public function test_owners_can_view_the_sharing_page(): void
    {
        $url = url('/p/acme-studio');
        $this->assertSame($url, $this->group->publicUrl());

        $response = $this->actingAs($this->owner)
            ->get(route('groups.share.show', $this->group))
            ->assertOk()
            ->assertSee('Acme Studio')
            ->assertSee($url)
            ->assertSee('Copy link')
            ->assertSee('Open public page')
            ->assertSee('alt="QR code that opens '.$url.'"', false)
            ->assertSee(route('groups.qr.svg', $this->group))
            ->assertSee(route('groups.qr.png', $this->group))
            ->assertSee('NFC writing application')
            ->assertSee('Before locking a card')
            ->assertDontSee('This group is inactive');

        preg_match('#src="data:image/svg\+xml;base64,([^"]+)"#', $response->getContent(), $match);
        $this->assertSame($url, $this->decodeSvg(base64_decode($match[1])));
    }

    public function test_management_pages_link_to_the_sharing_page(): void
    {
        $url = route('groups.share.show', $this->group);

        $this->actingAs($this->owner)->get(route('dashboard'))->assertSee($url);
        $this->actingAs($this->owner)->get(route('groups.edit', $this->group))->assertSee($url);
        $this->actingAs($this->owner)->get(route('groups.links.index', $this->group))->assertSee($url);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach ($this->routes($this->group) as $route) {
            $this->get($route)->assertRedirect(route('login'));
        }
    }

    public function test_other_users_receive_not_found(): void
    {
        $intruder = User::factory()->create();

        foreach ($this->routes($this->group) as $route) {
            $this->actingAs($intruder)->get($route)->assertNotFound();
        }
    }

    public function test_soft_deleted_groups_return_not_found(): void
    {
        $this->group->delete();

        foreach ($this->routes($this->group) as $route) {
            $this->actingAs($this->owner)->get($route)->assertNotFound();
        }
    }

    public function test_owners_can_download_an_svg_qr_code(): void
    {
        $response = $this->actingAs($this->owner)->get(route('groups.qr.svg', $this->group))->assertOk();

        $this->assertStringStartsWith('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=acme-studio-qr.svg', $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame("default-src 'none'", $response->headers->get('Content-Security-Policy'));

        $svg = $response->getContent();
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringNotContainsString('<script', $svg);
        $this->assertStringNotContainsString('Acme Studio', $svg);
        $this->assertSame($this->group->publicUrl(), $this->decodeSvg($svg));
    }

    public function test_owners_can_download_a_high_resolution_png_qr_code(): void
    {
        $response = $this->actingAs($this->owner)->get(route('groups.qr.png', $this->group))->assertOk();

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=acme-studio-qr.png', $response->headers->get('Content-Disposition'));

        $png = $response->getContent();
        $info = getimagesizefromstring($png);
        $this->assertSame('image/png', $info['mime']);
        $this->assertSame($info[0], $info[1]);
        $this->assertGreaterThanOrEqual(1000, $info[0]);

        // Quiet zone: the corner pixel is white.
        $image = imagecreatefromstring($png);
        $this->assertSame([255, 255, 255], array_values(array_slice(imagecolorsforindex($image, imagecolorat($image, 5, 5)), 0, 3)));

        $this->assertSame($this->group->publicUrl(), $this->decodePng($png));
    }

    public function test_request_input_cannot_change_the_encoded_url(): void
    {
        $png = $this->actingAs($this->owner)
            ->get(route('groups.qr.png', $this->group).'?url=https://evil.example&data=https://evil.example')
            ->getContent();

        $this->assertSame($this->group->publicUrl(), $this->decodePng($png));
    }

    public function test_the_url_survives_renaming_appearance_link_and_status_changes(): void
    {
        $original = $this->group->publicUrl();
        $link = Link::factory()->for($this->group)->create();

        $this->actingAs($this->owner);
        $this->put(route('groups.update', $this->group), ['name' => 'Renamed Studio', 'description' => 'New'])->assertSessionHasNoErrors();
        $this->put(route('groups.appearance.update', $this->group), [
            'theme' => 'dark',
            'appearance' => [...LinkGroup::themeDefaults('dark'), 'button_shape' => 'square'],
        ])->assertSessionHasNoErrors();
        $this->post(route('groups.links.store', $this->group), ['title' => 'New', 'url' => 'https://example.com'])->assertSessionHasNoErrors();
        $this->delete(route('groups.links.destroy', [$this->group, $link]));
        $this->put(route('groups.update', $this->group), ['is_active' => '0']);
        $this->put(route('groups.update', $this->group), ['is_active' => '1']);

        $group = $this->group->fresh();
        $this->assertSame('Renamed Studio', $group->name);
        $this->assertSame($original, $group->publicUrl());

        $this->get(route('groups.share.show', $group))->assertSee($original)->assertSee('Renamed Studio');
        $this->assertSame($original, $this->decodePng($this->get(route('groups.qr.png', $group))->getContent()));
    }

    public function test_inactive_groups_show_a_warning_and_still_download(): void
    {
        $this->group->update(['is_active' => false]);

        $this->actingAs($this->owner)
            ->get(route('groups.share.show', $this->group))
            ->assertOk()
            ->assertSee('This group is inactive')
            ->assertSee('reactivate the group')
            ->assertSee($this->group->publicUrl());

        $png = $this->get(route('groups.qr.png', $this->group))->assertOk()->getContent();
        $this->get(route('groups.qr.svg', $this->group))->assertOk();

        $this->assertSame($this->group->publicUrl(), $this->decodePng($png));
        $this->assertFalse($this->group->fresh()->is_active);
    }

    public function test_qr_generation_does_not_store_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->actingAs($this->owner)->get(route('groups.share.show', $this->group))->assertOk();
        $this->get(route('groups.qr.svg', $this->group))->assertOk();
        $this->get(route('groups.qr.png', $this->group))->assertOk();

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
