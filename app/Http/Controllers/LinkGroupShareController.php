<?php

namespace App\Http\Controllers;

use App\Models\LinkGroup;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Sharing page and QR code downloads. QR codes are generated on demand (never stored)
 * and always encode $group->publicUrl(); no request input reaches the encoder.
 */
class LinkGroupShareController extends Controller
{
    /** Pixels per module; about 1,100px for a typical public URL. */
    private const PNG_SCALE = 30;

    public function show(LinkGroup $group): View
    {
        Gate::authorize('view', $group);

        return view('groups.share', [
            'group' => $group,
            'qrDataUri' => 'data:image/svg+xml;base64,'.base64_encode($this->svg($group)),
        ]);
    }

    public function downloadSvg(LinkGroup $group): Response
    {
        Gate::authorize('view', $group);

        return $this->download($this->svg($group), 'image/svg+xml', $group->slug.'-qr.svg');
    }

    public function downloadPng(LinkGroup $group): Response
    {
        Gate::authorize('view', $group);

        $png = $this->qrCode(QRGdImagePNG::class)->render($group->publicUrl());

        return $this->download($png, 'image/png', $group->slug.'-qr.png');
    }

    private function svg(LinkGroup $group): string
    {
        return $this->qrCode(QRMarkupSVG::class)->render($group->publicUrl());
    }

    /**
     * Fixed, print-friendly style: black modules on white, standard 4-module quiet
     * zone, and error correction level M (about 15% recoverable damage).
     */
    private function qrCode(string $outputInterface): QRCode
    {
        return new QRCode(new QROptions([
            'outputInterface' => $outputInterface,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'outputBase64' => false,
            'connectPaths' => true,
            'scale' => self::PNG_SCALE,
        ]));
    }

    private function download(string $contents, string $contentType, string $filename): Response
    {
        return response($contents, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }
}
