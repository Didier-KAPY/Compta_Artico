<?php

namespace App\Services;

use App\Models\{Entreprise, User};
use Illuminate\Support\Facades\Storage;

class ReportSignatureService
{
    public function forCompany(Entreprise $company): array
    {
        $manager = app(RhPayslipSignatureService::class)->gerant($company);
        $finance = User::with(['role', 'employe'])->where('statut', 'Actif')->orderBy('id')->get()->first(function (User $user) use ($company) {
            $role = mb_strtolower(trim((string)$user->role?->designation));
            if (!in_array($role, ['chargé des finances','chargé de finance','charge des finances','charge de finance','charger de finance'], true)) return false;
            $id = $user->employe?->entreprise_id ?? app(CurrentEntreprise::class)->for($user)->id;
            return (int)$id === (int)$company->id;
        });
        return [
            'gerant'=>['nom'=>$manager ? trim($manager->nom.' '.$manager->prenom) : 'Non renseigné', 'image'=>$this->image($manager?->signature, 'signature-gerant')],
            'finances'=>['nom'=>$finance ? trim($finance->nom.' '.$finance->prenom) : 'Non renseigné', 'image'=>$this->image($finance?->signature, 'signature-finances')],
            'cachet'=>['nom'=>$company->nom_entreprise, 'image'=>$this->image($company->cachet, 'cachet-entreprise')],
        ];
    }

    private function image(?string $path, string $name): ?array
    {
        if (!$path || !Storage::disk('public')->exists($path)) return null;
        $bytes = Storage::disk('public')->get($path);
        $info = @getimagesizefromstring($bytes);
        $mime = $info['mime'] ?? null;
        $extensions = ['image/png'=>'png', 'image/jpeg'=>'jpg', 'image/gif'=>'gif'];
        if (!isset($extensions[$mime])) return null;
        $scale = min(140 / $info[0], 75 / $info[1]);
        return ['name'=>$name.'.'.$extensions[$mime], 'mime'=>$mime, 'base64'=>base64_encode($bytes), 'width'=>max(1,(int)round($info[0]*$scale)), 'height'=>max(1,(int)round($info[1]*$scale))];
    }

    public function excelDocument(string $html, array $signatures): string
    {
        // Excel's existing HTML .xls export uses a self-contained MHTML package
        // when it contains signatures. Content-Location resolves embedded images.
        $boundary = 'releve-'.bin2hex(random_bytes(16));
        $document = "MIME-Version: 1.0\r\nContent-Type: multipart/related; type=\"text/html\"; boundary=\"{$boundary}\"\r\n\r\n";
        $document .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\nContent-Location: file:///releve.htm\r\n\r\n{$html}\r\n";
        foreach ($signatures as $signature) {
            if (!$image = $signature['image']) continue;
            $document .= "--{$boundary}\r\nContent-Type: {$image['mime']}\r\nContent-Transfer-Encoding: base64\r\nContent-Location: file:///{$image['name']}\r\n\r\n";
            $document .= chunk_split($image['base64'], 76, "\r\n");
        }
        return $document."--{$boundary}--\r\n";
    }
}
