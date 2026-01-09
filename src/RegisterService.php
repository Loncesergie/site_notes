<?php
/**
 * @psalm-suppress UnusedClass
 */
final class RegisterService
{
    public function validate(string $username, string $email, string $password, string $confirm): array
    {
        $errors = [];

        $username = trim($username);
        $email = trim($email);

        if ($username === '' || strlen($username) < 3 || strlen($username) > 30) {
            $errors[] = 'username_length';
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            $errors[] = 'username_charset';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email_invalid';
        }

        if (strlen($password) < 8) {
            $errors[] = 'password_too_short';
        }
        if ($password !== $confirm) {
            $errors[] = 'password_mismatch';
        }

        return $errors;
    }

    public function register(PDO $pdo, string $username, string $email, string $password): int
    {
        $username = trim($username);
        $email = trim($email);

        $check = $pdo->prepare(
            "SELECT 1 FROM users WHERE UserName = :u OR Email = :e LIMIT 1"
        );
        $check->execute([':u' => $username, ':e' => $email]);

        if ($check->fetchColumn()) {
            throw new RuntimeException('duplicate');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $pdo->prepare(
            "INSERT INTO users (UserName, Email, Password, is_admin)
             VALUES (:u, :e, :p, 0)"
        );

        $insert->execute([
            ':u' => $username,
            ':e' => $email,
            ':p' => $hash
        ]);

        return (int)$pdo->lastInsertId();
    }
}

