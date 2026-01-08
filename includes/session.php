<?php
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
  'httponly' => true,
  'secure'   => $isHttps,   // true en HTTPS
  'samesite' => 'Lax'
]);

session_start();
