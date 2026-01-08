<?php
use PHPUnit\Framework\TestCase;

final class CsrfTokenTest extends TestCase
{
    public function testCsrfTokenIsGenerated(): void
    {
        require_once __DIR__ . '/../includes/session.php';
        require_once __DIR__ . '/../includes/csrf.php';

        $token = csrf_token();
        $this->assertNotEmpty($token);
        $this->assertSame($token, $_SESSION['csrf_token']);
    }
}

