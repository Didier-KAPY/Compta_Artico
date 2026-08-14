<?php

namespace App\Http\Controllers;

use App\Models\ListeDesComptes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImportComptableController extends Controller
{
    public function index(Request $request)
    {
        return view('imports_comptables.index', ['apercu' => $request->session()->get('import_comptes_apercu', [])]);
    }

    public function preview(Request $request)
    {
        $request->validate(['fichier' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($request->file('fichier')->getRealPath(), 'r');
        $first = fgets($handle);
        rewind($handle);
        $delimiter = substr_count((string) $first, ';') >= substr_count((string) $first, ',') ? ';' : ',';
        $header = array_map(fn ($v) => mb_strtolower(trim((string) $v)), fgetcsv($handle, 0, $delimiter) ?: []);
        $required = ['compte', 'designation', 'nature'];
        abort_unless(count(array_intersect($required, $header)) === 3, 422, 'Colonnes requises : compte, designation, nature.');
        $rows = [];
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false && count($rows) < 1000) {
            $row = array_combine($header, array_slice(array_pad($values, count($header), null), 0, count($header)));
            if (! trim((string) ($row['compte'] ?? ''))) continue;
            $rows[] = [
                'compte' => trim((string) $row['compte']), 'designation' => trim((string) $row['designation']),
                'nature' => trim((string) $row['nature']), 'observation' => trim((string) ($row['observation'] ?? '')) ?: null,
                'existe' => ListeDesComptes::where('compte', trim((string) $row['compte']))->exists(),
            ];
        }
        fclose($handle);
        $request->session()->put('import_comptes_apercu', $rows);
        return redirect()->route('parametres.imports.index')->with('success', count($rows).' ligne(s) analysée(s).');
    }

    public function store(Request $request)
    {
        $rows = $request->session()->pull('import_comptes_apercu', []);
        abort_if(empty($rows), 422, 'Aucun import à confirmer.');
        $created = 0;
        foreach ($rows as $row) {
            if ($row['existe']) continue;
            ListeDesComptes::create(collect($row)->except('existe')->all() + ['user_id' => Auth::id()]);
            $created++;
        }
        return back()->with('success', $created.' compte(s) importé(s); les doublons ont été ignorés.');
    }
}
