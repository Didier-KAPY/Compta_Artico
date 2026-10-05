<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class SauvegardeController extends Controller
{
    public function index()
    {
        $erreurLecture = null;
        try {
            $fichiers = collect(Storage::disk('local')->files('backups'))->filter(fn ($f) => str_ends_with($f, '.sql'))->sortDesc();
        } catch (Throwable $exception) {
            report($exception);
            $fichiers = collect();
            $erreurLecture = 'Impossible de lire les sauvegardes. Vérifiez les droits d’accès au dossier de sauvegardes sur le serveur. Le détail est enregistré dans les journaux du serveur.';
        }

        return view('sauvegardes.index', compact('fichiers', 'erreurLecture'));
    }

    public function store()
    {
        $db = DB::connection()->getConfig();
        abort_unless(($db['driver'] ?? null) === 'mysql', 422, 'La sauvegarde automatique est configurée pour MySQL.');
        $name = 'backups/compta-'.now()->format('Ymd-His').'-'.Str::random(8).'.sql';
        try {
            Storage::disk('local')->makeDirectory('backups');
            $this->createSqlDump($db, Storage::disk('local')->path($name));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($name);
            report($exception);

            return back()->withErrors(['sauvegarde' => 'La sauvegarde SQL a échoué. Vérifiez la configuration de l’outil d’export, la connexion MySQL et les droits du compte de base de données. Le détail est enregistré dans les journaux du serveur.']);
        }

        return back()->with('success', 'Sauvegarde créée : '.basename($name));
    }

    public function exportPackage()
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'L’extension PHP ZIP est requise pour les dossiers de travail.');
        $db = DB::connection()->getConfig();
        abort_unless(($db['driver'] ?? null) === 'mysql', 422, 'La sauvegarde automatique est configurée pour MySQL.');

        $stamp = now()->format('Ymd-His');
        $sqlPath = tempnam(sys_get_temp_dir(), 'compta-sql-');
        $zipName = 'backups/compta-workspace-'.$stamp.'-'.Str::random(8).'.zip';
        Storage::disk('local')->makeDirectory('backups');
        $zipPath = Storage::disk('local')->path($zipName);

        try {
            $this->createSqlDump($db, $sqlPath);

            $zip = new ZipArchive();
            abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Impossible de créer le paquet de travail.');
            if (! $zip->addFile($sqlPath, 'database.sql')) {
                throw new \RuntimeException('Impossible d’ajouter la base SQL au dossier de travail.');
            }
            $manifest = ['format' => 'compta-artico-workspace-v1', 'created_at' => now()->toIso8601String(), 'files' => []];
            foreach (['public' => Storage::disk('public'), 'private' => Storage::disk('local')] as $prefix => $disk) {
                foreach ($disk->allFiles() as $file) {
                    if ($prefix === 'private' && (str_starts_with($file, 'backups/') || str_starts_with($file, 'backup-imports/'))) continue;
                    $absolute = $disk->path($file);
                    if (is_file($absolute)) {
                        $archiveName = 'storage/'.$prefix.'/'.$file;
                        if (! $zip->addFile($absolute, $archiveName)) {
                            throw new \RuntimeException('Impossible d’ajouter un fichier au dossier de travail.');
                        }
                        $manifest['files'][] = $archiveName;
                    }
                }
            }
            if (! $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))) {
                throw new \RuntimeException('Impossible d’ajouter le manifeste au dossier de travail.');
            }
            if (! $zip->close()) {
                throw new \RuntimeException('Impossible de finaliser le dossier de travail. Vérifiez l’espace disque disponible.');
            }
        } catch (Throwable $exception) {
            unset($zip);
            Storage::disk('local')->delete($zipName);
            report($exception);

            return back()->withErrors(['sauvegarde' => 'L’export du dossier de travail a échoué. Le détail est enregistré dans les journaux du serveur.']);
        } finally {
            @unlink($sqlPath);
        }

        return redirect()->route('parametres.sauvegardes.index')
            ->with('success', 'Dossier de travail prêt. Vous pouvez télécharger la base et les pièces jointes.')
            ->with('workspace_download', basename($zipName));
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
        try {
            $this->restoreFile($file);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'fichier' => 'La restauration a échoué. La sauvegarde '.$file.' est toujours conservée sur le serveur.',
            ]);
        }

        return back()->with('success', 'Base restaurée depuis '.$file.'.');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'fichier' => ['required', 'file', 'max:1048576'],
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

    public function initChunkedImport(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'regex:/\.(sql|zip)$/i'],
            'taille' => ['required', 'integer', 'min:1', 'max:'.(str_ends_with(strtolower((string) $request->input('nom')), '.sql') ? 1073741824 : 524288000)],
            'nombre_blocs' => ['required', 'integer', 'min:1', 'max:256'],
        ]);
        $uploadId = (string) Str::uuid();
        $directory = 'backup-imports/'.$request->user()->id.'/'.$uploadId;
        Storage::disk('local')->put($directory.'/metadata.json', json_encode([
            'user_id' => $request->user()->id,
            'nom' => basename($data['nom']),
            'taille' => (int) $data['taille'],
            'nombre_blocs' => (int) $data['nombre_blocs'],
            'created_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR));

        return response()->json(['upload_id' => $uploadId]);
    }

    public function storeImportChunk(Request $request)
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0'],
            'bloc' => ['required', 'file', 'max:5120'],
        ]);
        [$directory, $metadata] = $this->chunkMetadata($request, $data['upload_id']);
        abort_if((int) $data['index'] >= $metadata['nombre_blocs'], 422, 'Numéro de bloc invalide.');
        $data['bloc']->storeAs($directory, sprintf('bloc-%05d.part', $data['index']), 'local');

        return response()->json(['ok' => true]);
    }

    public function finishChunkedImport(Request $request)
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'password' => ['required', 'string'],
            'confirmation' => ['accepted'],
        ]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }
        [$directory, $metadata] = $this->chunkMetadata($request, $data['upload_id']);
        Storage::disk('local')->makeDirectory('backups');
        $extension = mb_strtolower(pathinfo($metadata['nom'], PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['sql', 'zip'], true), 422, 'Format de fichier non reconnu.');
        $filename = 'importe-'.now()->format('Ymd-His').'-'.substr(sha1($metadata['nom']), 0, 8).'.'.$extension;
        $destination = fopen(Storage::disk('local')->path('backups/'.$filename), 'wb');
        abort_unless(is_resource($destination), 500, 'Impossible de créer le fichier SQL assemblé.');

        try {
            for ($index = 0; $index < $metadata['nombre_blocs']; $index++) {
                $chunk = $directory.'/'.sprintf('bloc-%05d.part', $index);
                abort_unless(Storage::disk('local')->exists($chunk), 422, 'Un bloc du fichier est manquant. Relancez l’import.');
                $source = fopen(Storage::disk('local')->path($chunk), 'rb');
                abort_unless(is_resource($source), 422, 'Un bloc du fichier est illisible.');
                stream_copy_to_stream($source, $destination);
                fclose($source);
            }
        } finally {
            fclose($destination);
        }

        abort_unless(Storage::disk('local')->size('backups/'.$filename) === $metadata['taille'], 422, 'Le fichier assemblé est incomplet. Relancez l’import.');
        Storage::disk('local')->deleteDirectory($directory);
        try {
            if ($extension === 'zip') {
                $this->restorePackagePath(Storage::disk('local')->path('backups/'.$filename));
            } else {
                $this->restoreFile($filename);
            }
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'fichier' => 'La restauration a échoué. Le fichier assemblé est conservé sous le nom '.$filename.'.',
            ]);
        }

        return response()->json(['message' => $extension === 'zip'
            ? 'Dossier de travail importé : base et fichiers restaurés.'
            : 'Base importée et restaurée depuis '.$metadata['nom'].'.']);
    }

    private function chunkMetadata(Request $request, string $uploadId): array
    {
        abort_unless(Str::isUuid($uploadId), 404);
        $directory = 'backup-imports/'.$request->user()->id.'/'.$uploadId;
        abort_unless(Storage::disk('local')->exists($directory.'/metadata.json'), 404);
        $metadata = json_decode(Storage::disk('local')->get($directory.'/metadata.json'), true, flags: JSON_THROW_ON_ERROR);
        abort_unless(($metadata['user_id'] ?? null) === $request->user()->id, 403);

        return [$directory, $metadata];
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
        try {
            $this->restorePackagePath($temporary);
        } finally {
            @unlink($temporary);
        }

        return back()->with('success', 'Dossier de travail importé : base et fichiers restaurés.');
    }

    private function restorePackagePath(string $path): void
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'L’extension PHP ZIP est requise pour les dossiers de travail.');
        $zip = new ZipArchive();
        try {
            abort_unless($zip->open($path) === true, 422, 'Archive ZIP illisible.');
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
        }
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
            try {
                $process->mustRun();
            } catch (Throwable $exception) {
                report($exception);
                $this->restoreWithPdo(Storage::disk('local')->path('backups/'.$filename));
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        $this->migrateRestoredDatabase();
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
            try {
                $process->mustRun();
            } catch (Throwable $exception) {
                report($exception);
                $this->restoreWithPdo($path);
            }
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
        $this->migrateRestoredDatabase();
    }

    private function migrateRestoredDatabase(): void
    {
        DB::purge(config('database.default'));
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function createSqlDump(array $db, string $path): void
    {
        $process = $this->dumpProcess($db, $path);
        $process->mustRun();
        clearstatcache(true, $path);
        if (! is_file($path) || filesize($path) === 0) {
            throw new \RuntimeException('L’outil d’export n’a produit aucun fichier SQL.');
        }
    }

    private function dumpProcess(array $db, string $path): Process
    {
        return new Process(array_merge(
            [$this->binary('DB_DUMP_BINARY', 'mysqldump.exe')],
            $this->connectionArguments($db),
            ['--single-transaction', '--no-tablespaces', '--routines', '--triggers', '--hex-blob', '--result-file='.$path, $db['database']],
        ), null, $this->processEnvironment((string) $db['password']), null, 300);
    }

    private function binary(string $environmentKey, string $executable): string
    {
        $key = $environmentKey === 'DB_DUMP_BINARY' ? 'dump_binary' : 'client_binary';
        if ($configured = config('backups.'.$key)) {
            return $configured;
        }
        $xampp = 'C:/xampp/mysql/bin/'.$executable;
        if (is_file($xampp)) {
            return $xampp;
        }
        $laragon = glob('C:/laragon/bin/mysql/*/bin/'.$executable) ?: [];

        $finder = new ExecutableFinder();
        $name = pathinfo($executable, PATHINFO_FILENAME);

        return end($laragon) ?: $finder->find($name)
            ?? $finder->find($name === 'mysqldump' ? 'mariadb-dump' : 'mariadb')
            ?? $name;
    }

    private function connectionArguments(array $db): array
    {
        $arguments = [
            '--host='.$db['host'],
            '--port='.(string) $db['port'],
            '--user='.$db['username'],
        ];
        $sslCa = defined('PDO::MYSQL_ATTR_SSL_CA') ? ($db['options'][\PDO::MYSQL_ATTR_SSL_CA] ?? null) : null;
        if ($sslCa && is_file($sslCa)) {
            $arguments[] = '--ssl-ca='.$sslCa;
        }

        return $arguments;
    }

    private function restoreWithPdo(string $path): void
    {
        $handle = fopen($path, 'rb');
        abort_unless(is_resource($handle), 422, 'Le fichier SQL ne peut pas être lu.');

        $pdo = DB::connection()->getPdo();
        $statement = '';
        $delimiter = ';';
        $quote = null;
        $escaped = false;
        $blockComment = false;

        try {
            while (($line = fgets($handle)) !== false) {
                if ($quote === null && ! $blockComment && trim($statement) === ''
                    && preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $match)) {
                    $delimiter = $match[1];
                    $statement = '';
                    continue;
                }

                $length = strlen($line);
                for ($index = 0; $index < $length; $index++) {
                    $character = $line[$index];
                    $next = $index + 1 < $length ? $line[$index + 1] : null;

                    if ($blockComment) {
                        $statement .= $character;
                        if ($character === '*' && $next === '/') {
                            $statement .= '/';
                            $index++;
                            $blockComment = false;
                        }
                        continue;
                    }

                    if ($quote !== null) {
                        $statement .= $character;
                        if ($escaped) {
                            $escaped = false;
                        } elseif ($character === '\\') {
                            $escaped = true;
                        } elseif ($character === $quote) {
                            if ($next === $quote) {
                                $statement .= $next;
                                $index++;
                            } else {
                                $quote = null;
                            }
                        }
                        continue;
                    }

                    if ($character === '/' && $next === '*') {
                        $statement .= '/*';
                        $index++;
                        $blockComment = true;
                        continue;
                    }
                    if ($character === '#' || ($character === '-' && $next === '-' && preg_match('/\s/', $line[$index + 2] ?? ' '))) {
                        $statement .= substr($line, $index);
                        break;
                    }
                    if (in_array($character, ["'", '"', '`'], true)) {
                        $quote = $character;
                        $statement .= $character;
                        continue;
                    }

                    $statement .= $character;
                    if ($delimiter !== '' && str_ends_with($statement, $delimiter)) {
                        $sql = trim(substr($statement, 0, -strlen($delimiter)));
                        $statement = '';
                        if ($sql !== '') {
                            $this->executeRestoreStatement($pdo, $sql);
                        }
                    }
                }
            }

            if (trim($statement) !== '') {
                $this->executeRestoreStatement($pdo, trim($statement));
            }
        } finally {
            fclose($handle);
            // Discard dump session state (LOCK TABLES, foreign key checks, etc.),
            // including when an import fails before its cleanup statements.
            DB::purge(config('database.default'));
        }
    }

    private function executeRestoreStatement(\PDO $pdo, string $sql): void
    {
        try {
            $pdo->exec($sql);
        } catch (\PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 3105
                || ! preg_match('/\bINSERT\s+INTO\s+(`(?:``|[^`])+`)/i', $sql, $match)) {
                throw $exception;
            }

            $columns = $pdo->query('SHOW FULL COLUMNS FROM '.$match[1])->fetchAll(\PDO::FETCH_ASSOC);
            $normalized = (new \App\Services\SqlGeneratedColumnNormalizer())->normalize($sql, $columns);
            if ($normalized === null || $normalized === $sql) throw $exception;

            $pdo->exec($normalized);
        }
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
