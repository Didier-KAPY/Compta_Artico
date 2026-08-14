@if($pdfMode ?? false)
<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>{{ $carteService->numero }}</title></head><body>
@endif
<style>
    @page { size: 53.98mm 85.60mm; margin: 0; }
    .service-card-wrap { display:flex; justify-content:center; padding:12px; }
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
    .sc-body { position:absolute; top:19mm; right:0; bottom:13mm; left:0; padding:4.2mm 3.4mm 1mm; text-align:center; overflow:hidden; }
    .sc-photo { width:18mm; height:20mm; object-fit:cover; border-radius:2mm; border:.8mm solid #fff; outline:.35mm solid #1769a5; background:#eef3f7; }
    .sc-photo-placeholder { display:inline-block; width:18mm; height:20mm; border-radius:2mm; background:#e9eff4; color:#1769a5; border:.35mm solid #1769a5; font-weight:800; font-size:5.5mm; line-height:20mm; }
    .sc-name { margin-top:1.25mm; font-weight:900; text-transform:uppercase; color:#0c2948; overflow-wrap:anywhere; }
    .sc-role { margin-top:.35mm; font-size:2.05mm; font-weight:700; color:#1769a5; line-height:2.55mm; overflow-wrap:anywhere; }
    .sc-dept { font-size:1.8mm; color:#546273; line-height:2.35mm; overflow-wrap:anywhere; }
    .sc-info { margin-top:1.15mm; padding-top:1.15mm; border-top:.25mm solid #dde5ec; text-align:left; font-size:1.78mm; line-height:2.45mm; overflow-wrap:anywhere; }
    .sc-info b { color:#0c2948; }
    .sc-bottom { position:absolute; bottom:0; left:0; right:0; height:13mm; padding:1.4mm 3.4mm 1.5mm; background:#f3f7fa; border-top:.3mm solid #d8e2ea; font-size:1.65mm; overflow:hidden; }
    .sc-number { float:left; width:20mm; color:#1769a5; font-weight:800; padding-top:4mm; white-space:nowrap; }
    .sc-sign { float:right; width:25mm; text-align:center; line-height:2mm; overflow-wrap:anywhere; }
    .sc-signature { display:block; width:20mm; height:4.5mm; margin:0 auto .2mm; object-fit:contain; background:transparent; }
    .sc-cachet { position:absolute; right:2mm; bottom:.4mm; width:13mm; height:11mm; object-fit:contain; opacity:.72; z-index:1; }
    .sc-sign-line { display:block; border-top:.25mm solid #596673; margin-top:.3mm; padding-top:.55mm; font-weight:700; }
    .sc-sign { position:relative; z-index:2; }
    .sc-accent { position:absolute; width:18mm; height:18mm; border-radius:50%; border:3mm solid rgba(23,105,165,.05); right:-7mm; top:35mm; }
    .pdf-card-wrap { padding:0; width:53.98mm; height:85.60mm; }
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
            height:85.60mm !important;
            min-width:53.98mm !important;
            min-height:85.60mm !important;
            margin:0 !important;
            padding:0 !important;
            overflow:hidden !important;
            background:#fff !important;
        }
        body > * { visibility:hidden; }
        .service-card-wrap, .service-card-wrap * { visibility:visible; }
        .service-card-wrap {
            position:fixed;
            left:0;
            top:0;
            width:53.98mm !important;
            height:85.60mm !important;
            margin:0 !important;
            padding:0 !important;
        }
        .service-card {
            width:53.98mm !important;
            height:85.60mm !important;
            margin:0 !important;
            box-shadow:none;
            border-radius:0;
        }
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
    $tailleNom = mb_strlen($nomCarte) > 34 ? '2.35mm' : (mb_strlen($nomCarte) > 25 ? '2.65mm' : '3mm');
    $ligneNom = mb_strlen($nomCarte) > 25 ? '2.85mm' : '3.45mm';
@endphp
<div class="service-card-wrap {{ ($pdfMode ?? false) ? 'pdf-card-wrap' : '' }}">
    <div class="service-card">
        <div class="sc-header">
            @if($logoData)<img class="sc-logo" src="{{ $logoData }}" alt="Logo">@else<span class="sc-logo-fallback">{{ mb_substr($entreprise?->nom_entreprise ?? 'E', 0, 1) }}</span>@endif
            <div class="sc-company"><strong>{{ $entreprise?->nom_entreprise ?? 'Entreprise' }}</strong><small>{{ $entreprise?->adresse }}</small><small>@if($entreprise?->telephone)Tél. {{ $entreprise->telephone }}@endif</small></div>
            <div class="sc-title">Carte de service</div>
        </div>
        <div class="sc-accent"></div>
        <div class="sc-body">
            @if($photoData)<img class="sc-photo" src="{{ $photoData }}" alt="Photo">@else<span class="sc-photo-placeholder">{{ $initiales ?: '—' }}</span>@endif
            <div class="sc-name" style="font-size:{{ $tailleNom }};line-height:{{ $ligneNom }}">{{ $nomCarte }}</div>
            <div class="sc-role">{{ $agent?->fonction?->designation ?? 'Fonction non renseignée' }}</div>
            <div class="sc-dept">{{ $agent?->departement?->designation ?? 'Département non renseigné' }}</div>
            <div class="sc-info">
                <div><b>Sexe :</b> {{ $carteService->sexe ?: '—' }}</div>
                <div><b>Adresse :</b> {{ $adresseCarte ?: '—' }}</div>
                <div><b>Délivrée le :</b> {{ $carteService->date_delivrance?->format('d/m/Y') }}</div>
            </div>
        </div>
        <div class="sc-bottom">
            <span class="sc-number">N° {{ $carteService->numero }}</span>
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
        </div>
    </div>
</div>
@if($pdfMode ?? false)
</body></html>
@endif
