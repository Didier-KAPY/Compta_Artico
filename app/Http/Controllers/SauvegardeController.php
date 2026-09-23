<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;
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
            $process = new Process(array_merge(
                [$this->binary('DB_DUMP_BINARY', 'mysqldump.exe')],
                $this->connectionArguments($db),
                ['--single-transaction', '--routines', '--triggers', $db['database'], '--result-file='.$path],
            ), null, $this->processEnvironment((string) $db['password']), null, 300);
            $process->mustRun();
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($name);

            throw $exception;
        }

        return back()->with('success', 'Sauvegarde créée : '.basename($name));
    }

    public function exportPackage()
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'L’extension PHP ZIP est requise pour les dossiers de travail.');
        $db = config('database.connections.'.config('database.default'));
        abort_unless(($db['driver'] ?? null) === 'mysql', 422, 'La sauvegarde automatique est configurée pour MySQL.');

        $stamp = now()->format('Ymd-His');
        $sqlPath = tempnam(sys_get_temp_dir(), 'compta-sql-');
        $zipName = 'backups/compta-workspace-'.$stamp.'.zip';
        Storage::disk('local')->makeDirectory('backups');
        $zipPath = Storage::disk('local')->path($zipName);

        try {
            $process = new Process(array_merge(
                [$this->binary('DB_DUMP_BINARY', 'mysqldump.exe')],
                $this->connectionArguments($db),
                ['--single-transaction', '--routines', '--triggers', $db['database'], '--result-file='.$sqlPath],
            ), null, $this->processEnvironment((string) $db['password']), null, 300);
            $process->mustRun();

            $zip = new ZipArchive();
            abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Impossible de créer le paquet de travail.');
            $zip->addFile($sqlPath, 'database.sql');
            $manifest = ['format' => 'compta-artico-workspace-v1', 'created_at' => now()->toIso8601String(), 'files' => []];
            foreach (['public' => Storage::disk('public'), 'private' => Storage::disk('local')] as $prefix => $disk) {
                foreach ($disk->allFiles() as $file) {
                    if ($prefix === 'private' && str_starts_with($file, 'backups/')) continue;
                    $absolute = $disk->path($file);
                    if (is_file($absolute)) {
                        $archiveName = 'storage/'.$prefix.'/'.$file;
                        $zip->addFile($absolute, $archiveName);
                        $manifest['files'][] = $archiveName;
                    }
                }
            }
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->close();
        } finally {
            @unlink($sqlPath);
        }

        return Storage::disk('local')->download($zipName, basename($zipName), ['Content-Type' => 'application/zip']);
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
        try {
            $this->restoreFile($filename);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'fichier' => 'La restauration MySQL a échoué. Le fichier a été conservé dans les sauvegardes sous le nom '.$filename.'.',
            ]);
        }

        return back()->with('success', 'Base importée et restaurée depuis '.$upload->getClientOriginalName().'.');
    }

    public function importPackage(Request $request)
    {
        $data = $request->validate([
            'fichier' => ['required', 'file', 'max:512000'],
            'password' => ['required', 'string'],
            'confirmation' => ['accepted'],
        ]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }
        abort_unless(class_exists(ZipArchive::class), 500, 'L’extension PHP ZIP est requise pour les dossiers de travail.');
        $upload = $data['fichier'];
        if (mb_strtolower($upload->getClientOriginalExtension()) !== 'zip') {
            throw ValidationException::withMessages(['fichier' => 'Le dossier de travail doit être envoyé au format .zip.']);
        }

        $temporary = tempnam(sys_get_temp_dir(), 'compta-workspace-');
        @unlink($temporary);
        $upload->move(dirname($temporary), basename($temporary));
        $zip = new ZipArchive();
        try {
            abort_unless($zip->open($temporary) === true, 422, 'Archive ZIP illisible.');
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            $sqlEntry = $zip->locateName('database.sql') !== false ? 'database.sql' : 'database/dump.sql';
            abort_unless(($manifest['format'] ?? null) === 'compta-artico-workspace-v1' || $sqlEntry !== false, 422, 'Format de dossier de travail non reconnu.');
            abort_unless($sqlEntry !== false, 422, 'Le paquet ne contient pas de sauvegarde SQL.');

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                abort_if($name === false || str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('#(^|/)\.\.?(/|$)#', $name), 422, 'Chemin dangereux dans le paquet.');
            }
            $sql = tempnam(sys_get_temp_dir(), 'compta-sql-');
            try {
                file_put_contents($sql, $zip->getFromName($sqlEntry));
                $this->restoreSqlPath($sql);
            } finally {
                @unlink($sql);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $isPublic = str_starts_with($name, 'storage/public/') || str_starts_with($name, 'storage/app/public/');
                $isPrivate = str_starts_with($name, 'storage/private/') || str_starts_with($name, 'storage/app/private/');
                if (! $isPublic && ! $isPrivate) continue;
                $stream = $zip->getStream($name);
                if (! is_resource($stream)) continue;
                $relative = preg_replace('#^storage/app/(public|private)/|^storage/(public|private)/#', '', $name);
                ($isPublic ? Storage::disk('public') : Storage::disk('local'))->put($relative, $stream);
                fclose($stream);
            }
        } finally {
            $zip->close();
            @unlink($temporary);
        }

        return back()->with('success', 'Dossier de travail importé : base et fichiers restaurés.');
    }

    private function restoreFile(string $filename): void
    {
        $db = config('database.connections.'.config('database.default'));
        abort_unless(($db['driver'] ?? null) === 'mysql', 422, 'La restauration est configurée pour MySQL.');
        $stream = fopen(Storage::disk('local')->path('backups/'.$filename), 'r');

        try {
            $process = new Process(array_merge(
                [$this->binary('DB_CLIENT_BINARY', 'mysql.exe')],
                $this->connectionArguments($db),
                [$db['database']],
            ), null, $this->processEnvironment((string) $db['password']), $stream, 300);
            $process->mustRun();
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function restoreSqlPath(string $path): void
    {
        $db = config('database.connections.'.config('database.default'));
        abort_unless(($db['driver'] ?? null) === 'mysql', 422, 'La restauration est configurée pour MySQL.');
        $stream = fopen($path, 'r');
        try {
            $process = new Process(array_merge(
                [$this->binary('DB_CLIENT_BINARY', 'mysql.exe')],
                $this->connectionArguments($db),
                [$db['database']],
            ), null, $this->processEnvironment((string) $db['password']), $stream, 300);
            $process->mustRun();
        } finally {
            if (is_resource($stream)) fclose($stream);
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

    private function connectionArguments(array $db): array
    {
        $arguments = [
            '--host='.$db['host'],
            '--port='.(string) $db['port'],
            '--user='.$db['username'],
        ];
        $sslCa = env('MYSQL_ATTR_SSL_CA');
        if ($sslCa && is_file($sslCa)) {
            $arguments[] = '--ssl-ca='.$sslCa;
        }

        return $arguments;
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
