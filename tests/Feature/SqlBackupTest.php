<?php

namespace Tests\Feature;

use App\Http\Controllers\SauvegardeController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SqlBackupTest extends TestCase
{
    public function test_dump_uses_resolved_connection_and_managed_database_options(): void
    {
        config(['backups.dump_binary' => '/usr/bin/mariadb-dump']);
        $method = new \ReflectionMethod(SauvegardeController::class, 'dumpProcess');
        $process = $method->invoke(new SauvegardeController(), $this->connection(), '/tmp/backup.sql');

        $command = $process->getCommandLine();
        $this->assertStringContainsString('/usr/bin/mariadb-dump', $command);
        $this->assertStringContainsString('--host=database.example', $command);
        $this->assertStringContainsString('--no-tablespaces', $command);
        $this->assertStringContainsString('--single-transaction', $command);
        $this->assertStringContainsString('--routines', $command);
        $this->assertStringContainsString('--triggers', $command);
        $this->assertStringNotContainsString('secret-password', $command);
        $this->assertSame('secret-password', $process->getEnv()['MYSQL_PWD']);
    }

    public function test_failed_dump_removes_partial_backup_and_returns_an_error(): void
    {
        Storage::fake('local');
        DB::shouldReceive('connection->getConfig')->once()->andReturn($this->connection());
        $controller = new class extends SauvegardeController {
            protected function createSqlDump(array $db, string $path): void
            {
                file_put_contents($path, '-- incomplete');
                throw new \RuntimeException('Export failed');
            }
        };

        $response = $controller->store();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue(session('errors')->has('sauvegarde'));
        $this->assertSame([], Storage::disk('local')->files('backups'));
        $this->assertNull(session('success'));
    }

    public function test_dump_preserves_the_configured_tls_certificate(): void
    {
        if (! defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $this->markTestSkipped('The MySQL PDO extension is required.');
        }
        $certificate = tempnam(sys_get_temp_dir(), 'backup-ca-');
        try {
            $db = $this->connection();
            $db['options'][\PDO::MYSQL_ATTR_SSL_CA] = $certificate;
            $method = new \ReflectionMethod(SauvegardeController::class, 'connectionArguments');
            $arguments = $method->invoke(new SauvegardeController(), $db);

            $this->assertContains('--ssl-ca='.$certificate, $arguments);
        } finally {
            unlink($certificate);
        }
    }

    public function test_successful_dump_is_available_for_download(): void
    {
        Storage::fake('local');
        DB::shouldReceive('connection->getConfig')->once()->andReturn($this->connection());
        $controller = new class extends SauvegardeController {
            protected function createSqlDump(array $db, string $path): void
            {
                file_put_contents($path, '-- complete SQL dump');
            }
        };

        $controller->store();

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);
        $this->assertSame('-- complete SQL dump', Storage::disk('local')->get($files[0]));
        $this->assertNotNull(session('success'));
    }

    private function connection(): array
    {
        return ['driver' => 'mysql', 'host' => 'database.example', 'port' => 3306,
            'username' => 'backup-user', 'password' => 'secret-password', 'database' => 'compta'];
    }
}
