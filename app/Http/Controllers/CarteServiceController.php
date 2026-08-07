<?php

namespace App\Http\Controllers;

use App\Models\CarteService;
use App\Models\Entreprise;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            ->setPaper([0, 0, 153.01, 242.65])
            ->download('carte-service-'.$carteService->numero.'.pdf');
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
        $gerant = User::with('role')->get()->first(fn (User $user) => in_array(
            mb_strtolower(trim((string) $user->role?->designation)),
            ['gérant', 'gerant'],
            true
        ));
        $signataire = $gerant ? trim($gerant->prenom.' '.$gerant->nom) : '';

        return compact('agents', 'carteService', 'signataire');
    }

    private function cardData(CarteService $carteService): array
    {
        $carteService->load(['user.departement', 'user.fonction']);
        $entreprise = Entreprise::first();
        $logoData = $this->imageData($entreprise?->logo);
        $photoData = $this->imageData($carteService->user?->photo);

        return compact('carteService', 'entreprise', 'logoData', 'photoData');
    }

    private function imageData(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    private function prochainNumero(): string
    {
        $prefixe = 'CS-'.now()->format('Y').'-';
        $dernier = CarteService::where('numero', 'like', $prefixe.'%')->max('numero');
        $sequence = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

        return $prefixe.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
