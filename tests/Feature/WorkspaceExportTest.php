<?php

namespace Tests\Feature;

use App\Http\Controllers\SauvegardeController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class WorkspaceExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(ZipArchive::class)) $this->markTestSkipped('ZIP extension required.');
        Storage::fake('local');
        Storage::fake('public');
        DB::shouldReceive('connection->getConfig')->once()->andReturn(['driver' => 'mysql']);
    }

    public function test_export_redirects_to_a_download_link_and_contains_only_workspace_files(): void
    {
        Storage::disk('public')->put('images/logo.png', 'public image');
        Storage::disk('local')->put('documents/piece.pdf', 'private document');
        Storage::disk('local')->put('backups/old.sql', 'old backup');
        Storage::disk('local')->put('backup-imports/upload/bloc.part', 'unfinished upload');
        $controller = new class extends SauvegardeController {
            protected function createSqlDump(array $db, string $path): void
            {
                file_put_contents($path, '-- SQL dump');
            }
        };

        $response = $controller->exportPackage();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('parametres.sauvegardes.index'), $response->getTargetUrl());
        $filename = session('workspace_download');
        $this->assertNotEmpty($filename);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open(Storage::disk('local')->path('backups/'.$filename)));
        try {
            $this->assertSame('-- SQL dump', $zip->getFromName('database.sql'));
            $this->assertSame('public image', $zip->getFromName('storage/public/images/logo.png'));
            $this->assertSame('private document', $zip->getFromName('storage/private/documents/piece.pdf'));
            $this->assertSame(4, $zip->numFiles);
            $manifest = json_decode($zip->getFromName('manifest.json'), true);
            $this->assertCount(2, $manifest['files']);
        } finally {
            $zip->close();
        }
    }

    public function test_failed_export_returns_an_error_without_a_download_link(): void
    {
        $controller = new class extends SauvegardeController {
            protected function createSqlDump(array $db, string $path): void
            {
                throw new \RuntimeException('Dump failed');
            }
        };

        $response = $controller->exportPackage();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue(session('errors')->has('sauvegarde'));
        $this->assertNull(session('workspace_download'));
        $this->assertSame([], Storage::disk('local')->files('backups'));
    }
}
