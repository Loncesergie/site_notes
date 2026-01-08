<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Méthode non autorisée');
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    exit('CSRF invalide');
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

if ($username === '' || $email === '' || $password === '' || $passwordConfirm === '') {
    exit('Champs manquants');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Email invalide');
}

if ($password !== $passwordConfirm) {
    exit('Les mots de passe ne correspondent pas');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (UserName, Email, Password) VALUES (:username, :email, :password)";
$stmt = $dbh->prepare($sql);

try {
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);
} catch (PDOException $e) {
    exit('Utilisateur ou email déjà existant');
}

header('Location: index.php');
exit;

