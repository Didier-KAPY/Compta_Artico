<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class SauvegardeController extends Controller
{
    public function index()
    {
        $fichiers = collect(Storage::disk('local')->files('backups'))->filter(fn ($f) => str_ends_with($f, '.sql'))->sortDesc();
        return view('sauvegardes.index', compact('fichiers'));
    }

    public function store()
    {
        $db = config('database.connections.'.config('database.default'));
        abort_unless(($db['driver'] ?? null) === 'mysql', 422, 'La sauvegarde automatique est configurée pour MySQL.');
        $name = 'backups/compta-'.now()->format('Ymd-His').'.sql';
        Storage::disk('local')->makeDirectory('backups');
        $path = Storage::disk('local')->path($name);
        $process = new Process([
            $this->binary('DB_DUMP_BINARY', 'mysqldump.exe'), '--host='.$db['host'], '--port='.(string) $db['port'],
            '--user='.$db['username'], '--single-transaction', '--routines', '--triggers', $db['database'],
            '--result-file='.$path,
        ], null, ['MYSQL_PWD' => (string) $db['password']], null, 300);
        $process->mustRun();
        return back()->with('success', 'Sauvegarde créée : '.basename($name));
    }

    public function download(string $fichier)
    {
        abort_if(basename($fichier) !== $fichier || ! Storage::disk('local')->exists('backups/'.$fichier), 404);
        return Storage::disk('local')->download('backups/'.$fichier);
    }

    public function restore(Request $request)
    {
        $data = $request->validate(['fichier' => ['required', 'string'], 'password' => ['required', 'string'], 'confirmation' => ['accepted']]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }
        $file = basename($data['fichier']);
        abort_unless($file === $data['fichier'] && Storage::disk('local')->exists('backups/'.$file), 404);
        $db = config('database.connections.'.config('database.default'));
        $process = new Process([$this->binary('DB_CLIENT_BINARY', 'mysql.exe'), '--host='.$db['host'], '--port='.(string) $db['port'], '--user='.$db['username'], $db['database']], null, ['MYSQL_PWD' => (string) $db['password']], fopen(Storage::disk('local')->path('backups/'.$file), 'r'), 300);
        $process->mustRun();
        return back()->with('success', 'Base restaurée depuis '.$file.'.');
    }

    private function binary(string $environmentKey, string $executable): string
    {
        if ($configured = env($environmentKey)) return $configured;
        $laragon = glob('C:/laragon/bin/mysql/*/bin/'.$executable) ?: [];
        return end($laragon) ?: pathinfo($executable, PATHINFO_FILENAME);
    }
}
