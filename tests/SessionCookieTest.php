<?php
use PHPUnit\Framework\TestCase;

final class SessionCookieTest extends TestCase
{
    public function testSessionCookieSecureFlagInHttpsContext(): void
    {
        // Forcer HTTPS
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;

        // Nettoyer tout header potentiel
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header_remove();

        // Inclure session.php (doit démarrer la session avec Secure=true)
        require __DIR__ . '/../includes/session.php';

        $cookies = headers_list();
        $setCookieLines = array_values(array_filter($cookies, fn($h) => stripos($h, 'Set-Cookie:') === 0));

        // Sur certains environnements CLI, aucun header n'est émis.
        // Dans ce cas, on valide via session_get_cookie_params().
        if (count($setCookieLines) === 0) {
            $params = session_get_cookie_params();
            $this->assertTrue($params['secure']);
            $this->assertTrue($params['httponly']);
            $this->assertSame('Lax', $params['samesite']);
            return;
        }

        $combined = implode("\n", $setCookieLines);

        $this->assertStringContainsString('PHPSESSID=', $combined);
        $this->assertStringContainsString('HttpOnly', $combined);
        $this->assertStringContainsString('SameSite=Lax', $combined);
        $this->assertStringContainsString('Secure', $combined);
    }
}

