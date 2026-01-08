<?php
use PHPUnit\Framework\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function testHtaccessContainsSecurityHeaders(): void
    {
        $htaccess = @file_get_contents(__DIR__ . '/../.htaccess');
        $this->assertNotFalse($htaccess, ".htaccess introuvable (site_notes/.htaccess)");

        $this->assertStringContainsString('Content-Security-Policy', $htaccess);
        $this->assertStringContainsString('X-Frame-Options', $htaccess);
        $this->assertStringContainsString('X-Content-Type-Options', $htaccess);
    }
}

