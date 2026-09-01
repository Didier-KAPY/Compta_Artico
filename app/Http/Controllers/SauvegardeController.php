<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Throwable;

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
        try {
            $process = new Process([
                $this->binary('DB_DUMP_BINARY', 'mysqldump.exe'), '--host='.$db['host'], '--port='.(string) $db['port'],
                '--user='.$db['username'], '--single-transaction', '--routines', '--triggers', $db['database'],
                '--result-file='.$path,
            ], null, $this->processEnvironment((string) $db['password']), null, 300);
            $process->mustRun();
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($name);

            throw $exception;
        }

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
        $this->restoreFile($file);

        return back()->with('success', 'Base restaurée depuis '.$file.'.');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'fichier' => ['required', 'file', 'max:102400'],
            'password' => ['required', 'string'],
            'confirmation' => ['accepted'],
        ]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }

        /** @var UploadedFile $upload */
        $upload = $data['fichier'];
        if (mb_strtolower($upload->getClientOriginalExtension()) !== 'sql') {
            throw ValidationException::withMessages(['fichier' => 'Le fichier importé doit être au format .sql.']);
        }

        Storage::disk('local')->makeDirectory('backups');
        $filename = 'importe-'.now()->format('Ymd-His').'-'.substr(sha1($upload->getClientOriginalName()), 0, 8).'.sql';
        $upload->storeAs('backups', $filename, 'local');
        $this->restoreFile($filename);

        return back()->with('success', 'Base importée et restaurée depuis '.$upload->getClientOriginalName().'.');
    }

    private function restoreFile(string $filename): void
    {
        $db = config('database.connections.'.config('database.default'));
        abort_unless(($db['driver'] ?? null) === 'mysql', 422, 'La restauration est configurée pour MySQL.');
        $stream = fopen(Storage::disk('local')->path('backups/'.$filename), 'r');

        try {
            $process = new Process([$this->binary('DB_CLIENT_BINARY', 'mysql.exe'), '--host='.$db['host'], '--port='.(string) $db['port'], '--user='.$db['username'], $db['database']], null, $this->processEnvironment((string) $db['password']), $stream, 300);
            $process->mustRun();
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function binary(string $environmentKey, string $executable): string
    {
        if ($configured = env($environmentKey)) {
            return $configured;
        }
        $xampp = 'C:/xampp/mysql/bin/'.$executable;
        if (is_file($xampp)) {
            return $xampp;
        }
        $laragon = glob('C:/laragon/bin/mysql/*/bin/'.$executable) ?: [];

        return end($laragon) ?: pathinfo($executable, PATHINFO_FILENAME);
    }

    private function processEnvironment(string $password): array
    {
        $windowsDirectory = getenv('SystemRoot') ?: getenv('WINDIR') ?: 'C:\\Windows';

        return [
            'MYSQL_PWD' => $password,
            'SystemRoot' => $windowsDirectory,
            'WINDIR' => $windowsDirectory,
            'COMSPEC' => getenv('COMSPEC') ?: $windowsDirectory.'\\System32\\cmd.exe',
            'PATH' => getenv('PATH') ?: $windowsDirectory.'\\System32',
            'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
        ];
    }
}
