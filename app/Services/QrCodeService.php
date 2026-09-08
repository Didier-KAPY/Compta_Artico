<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function svg(string $contenu, int $taille = 300, int $marge = 2): string
    {
        $renderer = new ImageRenderer(new RendererStyle($taille, $marge), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($contenu);
    }

    public function dataUri(string $contenu, int $taille = 300, int $marge = 2): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($contenu, $taille, $marge));
    }

    public function pngDataUri(string $contenu, int $taille = 300, int $marge = 2): string
    {
        $png = (new Writer(new GDLibRenderer($taille, $marge)))->writeString($contenu);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
