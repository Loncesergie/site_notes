<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/RegisterService.php';

final class RegisterServiceTest extends TestCase
{
    private PDO $pdo;
    private RegisterService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                UserName TEXT NOT NULL,
                Email TEXT NOT NULL,
                Password TEXT NOT NULL,
                is_admin INTEGER NOT NULL DEFAULT 0
            );
        ");

        $this->service = new RegisterService();
    }

    public function testRejectsInvalidEmail(): void
    {
        $errors = $this->service->validate('userok', 'not-an-email', 'password123', 'password123');
        $this->assertContains('email_invalid', $errors);
    }

    public function testRejectsPasswordMismatch(): void
    {
        $errors = $this->service->validate('userok', 'u@exemple.fr', 'password123', 'password124');
        $this->assertContains('password_mismatch', $errors);
    }

    public function testStoresHashedPassword(): void
    {
        $id = $this->service->register($this->pdo, 'userok', 'u@exemple.fr', 'password123');
        $this->assertGreaterThan(0, $id);

        $row = $this->pdo->query("SELECT Password FROM users WHERE id = " . (int)$id)->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($row);

        $hash = $row['Password'];
        $this->assertNotSame('password123', $hash);
        $this->assertTrue(password_verify('password123', $hash));
    }

    public function testRejectsDuplicateUsernameOrEmail(): void
    {
        $this->service->register($this->pdo, 'dupuser', 'dup@exemple.fr', 'password123');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('duplicate');

        $this->service->register($this->pdo, 'dupuser', 'other@exemple.fr', 'password123');
    }
}

