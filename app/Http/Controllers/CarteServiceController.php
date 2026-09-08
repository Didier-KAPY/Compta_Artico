<?php

namespace App\Http\Controllers;

use App\Models\CarteService;
use App\Models\Entreprise;
use App\Models\User;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarteServiceController extends Controller
{
    public function index(Request $request)
    {
        $recherche = trim((string) $request->input('recherche'));
        $cartes = CarteService::with(['user.departement', 'user.fonction'])
            ->when($recherche, fn ($query) => $query->where(function ($query) use ($recherche) {
                $query->where('numero', 'like', "%{$recherche}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('prenom', 'like', "%{$recherche}%"));
            }))
            ->latest('date_delivrance')
            ->paginate(15)
            ->withQueryString();

        return view('parametres.cartes-service.index', compact('cartes', 'recherche'));
    }

    public function create()
    {
        return view('parametres.cartes-service.form', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['numero'] = $this->prochainNumero();
        $carte = CarteService::create($data);

        return redirect()->route('parametres.cartes-service.show', $carte)
            ->with('success', 'Carte de service créée avec succès.');
    }

    public function show(CarteService $carteService)
    {
        return view('parametres.cartes-service.show', $this->cardData($carteService));
    }

    public function edit(CarteService $carteService)
    {
        return view('parametres.cartes-service.form', $this->formData($carteService));
    }

    public function update(Request $request, CarteService $carteService)
    {
        $carteService->update($this->validated($request));

        return redirect()->route('parametres.cartes-service.show', $carteService)
            ->with('success', 'Carte de service mise à jour.');
    }

    public function destroy(CarteService $carteService)
    {
        $carteService->delete();

        return redirect()->route('parametres.cartes-service.index')
            ->with('success', 'Carte de service supprimée.');
    }

    public function pdf(CarteService $carteService)
    {
        $data = $this->cardData($carteService) + ['pdfMode' => true];

        return Pdf::loadView('parametres.cartes-service.carte', $data)
            ->setOptions([
                'defaultMediaType' => 'screen',
                'dpi' => 96,
                'isRemoteEnabled' => false,
            ])
            ->setPaper([0, 0, 153.01, 242.65])
            ->download('carte-service-'.$carteService->numero.'.pdf');
    }

    public function photo(CarteService $carteService)
    {
        $path = $carteService->user?->photo;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'image/jpeg',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'postnom' => ['nullable', 'string', 'max:100'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'sexe' => ['nullable', Rule::in(['Masculin', 'Féminin'])],
            'date_delivrance' => ['required', 'date'],
            'nom_signataire' => ['required', 'string', 'max:150'],
        ]);
    }

    private function formData(?CarteService $carteService = null): array
    {
        $agents = User::with(['departement', 'fonction'])->orderBy('nom')->orderBy('prenom')->get();
        $gerant = $this->gerant();
        $entreprise = Entreprise::first();
        $signataire = $gerant ? trim($gerant->prenom.' '.$gerant->nom) : '';

        return compact('agents', 'carteService', 'signataire', 'gerant', 'entreprise');
    }

    private function cardData(CarteService $carteService): array
    {
        $carteService->load(['user.departement', 'user.fonction', 'user.employe']);
        $entreprise = Entreprise::first();
        $gerant = $this->gerant();
        $logoData = $this->imageData($entreprise?->logo);
        $photoData = $this->photoData($carteService->user?->photo);
        $photoUrl = $carteService->user?->photo
            ? route('parametres.cartes-service.photo', $carteService, false).'?v='.urlencode((string) $carteService->updated_at?->timestamp)
            : null;
        $signatureData = $this->imageData($gerant?->signature);
        $cachetData = $this->imageData($entreprise?->cachet);
        $qrCodeData = $this->qrCodeData($carteService);

        return compact('carteService', 'entreprise', 'logoData', 'photoData', 'photoUrl', 'signatureData', 'cachetData', 'qrCodeData');
    }

    private function qrCodeData(CarteService $carteService): ?string
    {
        $employe = $carteService->user?->employe;
        if (! $employe || $employe->statut !== 'Actif') {
            return null;
        }

        if (! $employe->qr_token) {
            $employe->forceFill(['qr_token' => Str::random(64), 'qr_genere_le' => now()])->save();
        }

        return app(QrCodeService::class)->pngDataUri('rh-attendance:'.$employe->qr_token, 220, 1);
    }

    private function gerant(): ?User
    {
        return User::with('role')->get()->first(fn (User $user) => in_array(
            mb_strtolower(trim((string) $user->role?->designation)),
            ['gérant', 'gerant'],
            true
        ));
    }

    private function imageData(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    private function photoData(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $source = @imagecreatefromstring(Storage::disk('public')->get($path));
        if (! $source) {
            return $this->imageData($path);
        }

        $largeur = imagesx($source);
        $hauteur = imagesy($source);
        $cibleLargeur = 640;
        $cibleHauteur = 700;
        $ratioSource = $largeur / $hauteur;
        $ratioCible = $cibleLargeur / $cibleHauteur;
        $x = 0;
        $y = 0;
        $largeurSource = $largeur;
        $hauteurSource = $hauteur;

        if ($ratioSource > $ratioCible) {
            $largeurSource = (int) round($hauteur * $ratioCible);
            $x = (int) floor(($largeur - $largeurSource) / 2);
        } else {
            $hauteurSource = (int) round($largeur / $ratioCible);
            $y = (int) floor(($hauteur - $hauteurSource) / 2);
        }

        $photo = imagecreatetruecolor($cibleLargeur, $cibleHauteur);
        imagecopyresampled($photo, $source, 0, 0, $x, $y, $cibleLargeur, $cibleHauteur, $largeurSource, $hauteurSource);
        ob_start();
        imagepng($photo, null, 6);
        $png = ob_get_clean();
        imagedestroy($photo);
        imagedestroy($source);

        return $png === false ? $this->imageData($path) : 'data:image/png;base64,'.base64_encode($png);
    }

    private function prochainNumero(): string
    {
        $prefixe = 'CS-'.now()->format('Y').'-';
        $dernier = CarteService::where('numero', 'like', $prefixe.'%')->max('numero');
        $sequence = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

        return $prefixe.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
