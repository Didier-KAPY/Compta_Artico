@if($pdfMode ?? false)
<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>{{ $carteService->numero }}</title></head><body>
@endif
<style>
    @page { size: 53.98mm 85.60mm; margin: 0; }
    .service-card-wrap { display:flex; flex-wrap:wrap; justify-content:center; gap:12px; padding:12px; }
    .service-card { position:relative; overflow:hidden; width:53.98mm; height:85.60mm; background:#fff; border-radius:0; box-shadow:none; color:#152238; font-family:DejaVu Sans, Arial, sans-serif; box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; color-adjust:exact; }
    .service-card * { box-sizing:border-box; }
    .sc-header { height:19mm; padding:3mm 3mm 2mm; text-align:center; color:#fff; background:#0c2948; position:relative; }
    .sc-header:after { content:''; position:absolute; left:0; right:0; bottom:0; height:1.2mm; background:#e5ad25; }
    .sc-logo { width:9mm; height:9mm; background:transparent; object-fit:contain; padding:0; vertical-align:middle; float:left; }
    .sc-logo-fallback { float:left; width:9mm; height:9mm; border-radius:50%; background:#fff; color:#1769a5; font-weight:800; line-height:9mm; }
    .sc-company { margin-left:10mm; padding-top:.4mm; }
    .sc-company strong { display:block; font-size:2.65mm; line-height:3.05mm; text-transform:uppercase; overflow-wrap:anywhere; }
    .sc-company small { display:block; font-size:1.65mm; line-height:2.15mm; opacity:.95; overflow-wrap:anywhere; }
    .sc-title { clear:both; padding-top:1.7mm; font-size:2.25mm; font-weight:800; letter-spacing:.8mm; text-transform:uppercase; }
    .sc-body { position:absolute; top:19mm; right:0; bottom:16mm; left:0; padding:2.4mm 3.4mm .5mm; text-align:center; overflow:hidden; }
    .sc-photo { display:block; width:17.8mm; height:19.3mm; margin:0 auto; padding:.6mm; object-fit:cover; object-position:center 20%; border:.35mm solid #1769a5; border-radius:2.5mm; background:#fff; box-shadow:0 .6mm 1.4mm rgba(12,41,72,.18); }
    .sc-photo-placeholder { display:block; width:17.8mm; height:19.3mm; margin:0 auto; border:.35mm solid #1769a5; border-radius:2.5mm; background:#e9eff4; color:#1769a5; font-weight:800; font-size:5mm; line-height:18.6mm; box-shadow:0 .6mm 1.4mm rgba(12,41,72,.18); }
    .sc-name { margin-top:.8mm; font-weight:900; text-transform:uppercase; color:#0c2948; overflow-wrap:anywhere; }
    .sc-role { margin-top:.2mm; font-size:1.9mm; font-weight:700; color:#1769a5; line-height:2.25mm; overflow-wrap:anywhere; }
    .sc-dept { font-size:1.65mm; color:#546273; line-height:2.05mm; overflow-wrap:anywhere; }
    .sc-info { margin-top:.7mm; padding-top:.65mm; border-top:.25mm solid #dde5ec; text-align:left; font-size:1.85mm; font-weight:600; line-height:2.4mm; overflow-wrap:anywhere; }
    .sc-info b { color:#0c2948; font-weight:800; }
    .sc-assistance { position:absolute; right:3.4mm; bottom:0; left:3.4mm; height:5.2mm; padding-top:.35mm; border-top:.25mm solid #d8e2ea; color:#546273; font-size:1.05mm; font-style:italic; line-height:1.35mm; text-align:justify; }
    .sc-bottom { position:absolute; bottom:0; left:0; right:0; height:16mm; padding:1mm 3.4mm 6mm; background:#f3f7fa; border-top:.3mm solid #d8e2ea; font-size:1.55mm; overflow:hidden; }
    .sc-qr { float:left; width:8mm; height:8mm; margin-right:1.2mm; object-fit:contain; }
    .sc-number { float:left; width:11.5mm; color:#1769a5; font-weight:800; padding-top:3.2mm; white-space:nowrap; font-size:1.25mm; }
    .sc-sign { float:right; width:24mm; text-align:center; font-size:1.35mm; line-height:1.6mm; overflow-wrap:anywhere; }
    .sc-signature { display:block; width:18mm; height:3.7mm; margin:0 auto .1mm; object-fit:contain; background:transparent; }
    .sc-cachet { position:absolute; right:14mm; bottom:6.1mm; width:12mm; height:9mm; object-fit:contain; opacity:.78; z-index:1; }
    .sc-sign-line { display:block; border-top:.25mm solid #596673; margin-top:.15mm; padding-top:.3mm; font-weight:700; }
    .sc-sign { position:relative; z-index:2; }
    .sc-front .sc-sign { float:none; width:30mm; margin:0 auto; }
    .sc-accent { position:absolute; width:18mm; height:18mm; border-radius:50%; border:3mm solid rgba(23,105,165,.05); right:-7mm; top:35mm; }
    .sc-back { text-align:center; background:linear-gradient(180deg,#fff 0%,#f3f7fa 100%); }
    .sc-back-header { height:20mm; padding:3mm; color:#fff; background:#0c2948; border-bottom:1.2mm solid #e5ad25; }
    .sc-back-logo { width:10mm; height:10mm; object-fit:contain; vertical-align:middle; }
    .sc-back-company { margin-top:1mm; font-size:2.6mm; font-weight:800; text-transform:uppercase; }
    .sc-back-body { padding:7mm 5mm 4mm; }
    .sc-back-title { color:#0c2948; font-size:3mm; font-weight:900; text-transform:uppercase; letter-spacing:.5mm; }
    .sc-back-qr { display:block; width:28mm; height:28mm; margin:4mm auto 2.5mm; object-fit:contain; }
    .sc-back-number { color:#1769a5; font-size:3mm; font-weight:900; }
    .sc-back-help { margin-top:3mm; color:#546273; font-size:1.7mm; line-height:2.3mm; }
    .sc-back-footer { position:absolute; right:3.5mm; bottom:3.5mm; left:3.5mm; padding:2mm 2.2mm; border:.3mm solid #b8c8d6; border-radius:1.5mm; background:#fff; color:#26384a; font-size:1.8mm; font-weight:600; line-height:2.45mm; text-align:center; }
    .pdf-card-wrap { display:block; padding:0; width:53.98mm; height:auto; }
    .pdf-card-wrap .service-card { page-break-after:always; }
    .pdf-card-wrap .service-card:last-child { page-break-after:auto; }
    .sc-company, .sc-company strong, .sc-company small, .sc-title { color:#fff; }
    @media print {
        @page { size: 53.98mm 85.60mm; margin: 0; }
        html, body, .service-card-wrap, .service-card, .service-card * {
            -webkit-print-color-adjust:exact !important;
            print-color-adjust:exact !important;
            color-adjust:exact !important;
        }
        html, body {
            width:53.98mm !important;
            height:auto !important;
            min-width:53.98mm !important;
            min-height:85.60mm !important;
            margin:0 !important;
            padding:0 !important;
            overflow:visible !important;
            background:#fff !important;
        }
        body > * { visibility:hidden; }
        .service-card-wrap, .service-card-wrap * { visibility:visible; }
        .service-card-wrap {
            position:static;
            display:block;
            width:53.98mm !important;
            height:auto !important;
            margin:0 !important;
            padding:0 !important;
        }
        .service-card {
            width:53.98mm !important;
            height:85.60mm !important;
            margin:0 !important;
            box-shadow:none;
            border-radius:0;
            page-break-after:always;
        }
        .service-card:last-child { page-break-after:auto; }
        .sc-header { background:#0c2948 !important; color:#fff !important; }
        .sc-header:after { background:#e5ad25 !important; }
        .sc-bottom { background:#f3f7fa !important; }
    }
</style>
@php
    $agent = $carteService->user;
    $adresseCarte = $carteService->adresse ?: $agent?->adresse;
    $initiales = mb_strtoupper(mb_substr($agent?->prenom ?? '', 0, 1).mb_substr($agent?->nom ?? '', 0, 1));
    $nomCarte = trim(($agent?->nom ?? '').' '.($carteService->postnom ?? '').' '.($agent?->prenom ?? ''));
    $matriculeCarte = preg_replace('/-(?:19|20)\d{2}-/', '-', (string) ($agent?->employe?->matricule ?? ''));
    $tailleNom = mb_strlen($nomCarte) > 34 ? '2.35mm' : (mb_strlen($nomCarte) > 25 ? '2.65mm' : '3mm');
    $ligneNom = mb_strlen($nomCarte) > 25 ? '2.85mm' : '3.45mm';
@endphp
<div class="service-card-wrap {{ ($pdfMode ?? false) ? 'pdf-card-wrap' : '' }}">
    <div class="service-card sc-front">
        <div class="sc-header">
            @if($logoData)<img class="sc-logo" src="{{ $logoData }}" alt="Logo">@else<span class="sc-logo-fallback">{{ mb_substr($entreprise?->nom_entreprise ?? 'E', 0, 1) }}</span>@endif
            <div class="sc-company"><strong>{{ $entreprise?->nom_entreprise ?? 'Entreprise' }}</strong><small>{{ $entreprise?->adresse }}</small><small>@if($entreprise?->telephone)Tél. {{ $entreprise->telephone }}@endif</small></div>
            <div class="sc-title">Carte de service</div>
        </div>
        <div class="sc-accent"></div>
        <div class="sc-body">
            @if($photoData)<img class="sc-photo" src="{{ ($pdfMode ?? false) ? $photoData : $photoUrl }}" alt="Photo" crossorigin="anonymous">@else<span class="sc-photo-placeholder">{{ $initiales ?: '—' }}</span>@endif
            <div class="sc-name" style="font-size:{{ $tailleNom }};line-height:{{ $ligneNom }}">{{ $nomCarte }}</div>
            <div class="sc-role">{{ $agent?->fonction?->designation ?? 'Fonction non renseignée' }}</div>
            <div class="sc-dept">{{ $agent?->departement?->designation ?? 'Direction non renseignée' }}</div>
            <div class="sc-info">
                <div><b>N° matricule :</b> {{ $matriculeCarte ?: '—' }}</div>
                <div><b>Sexe :</b> {{ $carteService->sexe ?: '—' }}</div>
                <div><b>Adresse :</b> {{ $adresseCarte ?: '—' }}</div>
                <div><b>Délivrée le :</b> {{ $carteService->date_delivrance?->format('d/m/Y') }}</div>
                <div><b>N° carte :</b> {{ $carteService->numero }}</div>
            </div>
        </div>
        <div class="sc-bottom">
            @if($cachetData)
                <img class="sc-cachet" src="{{ $cachetData }}" alt="Cachet de l’entreprise">
            @endif
            <div class="sc-sign">
                <span>Le Gérant</span>
                @if($signatureData)
                    <img class="sc-signature" src="{{ $signatureData }}" alt="Signature du Gérant">
                @endif
                <span class="sc-sign-line">{{ $carteService->nom_signataire }}</span>
            </div>
            <div class="sc-assistance">Les autorités tant civiles que militaires ou policières sont priées d’apporter leur assistance au porteur de la présente.</div>
        </div>
    </div>
    <div class="service-card sc-back">
        <div class="sc-back-header">
            @if($logoData)<img class="sc-back-logo" src="{{ $logoData }}" alt="Logo">@endif
            <div class="sc-back-company">{{ $entreprise?->nom_entreprise ?? 'Entreprise' }}</div>
        </div>
        <div class="sc-back-body">
            <div class="sc-back-title">Pointage du personnel</div>
            @if($qrCodeData)<img class="sc-back-qr" src="{{ $qrCodeData }}" alt="Code QR de pointage arrivée et départ">@endif
            <div class="sc-back-help">Présentez ce code QR au scanner pour enregistrer votre arrivée et votre départ.</div>
        </div>
        <div class="sc-back-footer">Cette carte est strictement personnelle. En cas de perte, veuillez prévenir immédiatement l’entreprise.</div>
    </div>
</div>
@if($pdfMode ?? false)
</body></html>
@endif
