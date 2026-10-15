<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class RenderConfigurationTest extends TestCase
{
    private function environment(): array
    {
        return [
            'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'DEMO_MODE' => 'true',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)), 'APP_URL' => 'https://demo.example.test',
            'DB_CONNECTION' => 'pgsql', 'DB_URL' => 'postgresql://demo:private-test-value@db.example.test/demo?sslmode=verify-full',
            'DB_SSLMODE' => 'verify-full', 'CATALOG_STORAGE' => 'cloudinary',
            'CLOUDINARY_CLOUD_NAME' => 'test', 'CLOUDINARY_API_KEY' => 'test-key', 'CLOUDINARY_API_SECRET' => 'private-test-secret',
            'DEMO_ACCESS_HASH' => password_hash('private-test-access', PASSWORD_BCRYPT),
            'SESSION_DRIVER' => 'database', 'SESSION_SECURE_COOKIE' => 'true', 'SESSION_LIFETIME' => '10080',
            'CACHE_STORE' => 'database', 'MAIL_MAILER' => 'array',
        ];
    }

    public function test_render_startup_accepts_persistent_demo_configuration_without_printing_secrets(): void
    {
        $process = new Process([PHP_BINARY, base_path('deploy/validate-environment.php')], base_path(), $this->environment());
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame('', $process->getOutput());
    }

    public function test_render_startup_rejects_unsafe_configuration_without_printing_secrets(): void
    {
        foreach ([['DEMO_MODE' => 'false'], ['APP_DEBUG' => 'true'], ['DB_CONNECTION' => 'sqlite'], ['CATALOG_STORAGE' => 'local'], ['APP_KEY' => 'invalid'], ['SESSION_LIFETIME' => '120'], ['APP_URL' => 'http://demo.example.test'], ['DB_URL' => 'postgresql://demo:private-test-value@db.example.test/demo?sslmode=disable']] as $change) {
            $process = new Process([PHP_BINARY, base_path('deploy/validate-environment.php')], base_path(), $change + $this->environment());
            $process->run();
            $this->assertFalse($process->isSuccessful());
            $this->assertStringNotContainsString('private-test-', $process->getOutput().$process->getErrorOutput());
        }
    }
}
