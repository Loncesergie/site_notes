<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/config.php';

/**
 * IMPORTANT
 * - config.php doit exposer une connexion DB valide dans $dbh1 (mysqli)
 * - Si vous êtes en local, gardez l'affichage d'erreurs OFF pour éviter "Application Error Disclosure"
 */
error_reporting(0);
ini_set('display_errors', '0');

// Reset de la session admin si déjà connecté
if (!empty($_SESSION['alogin'])) {
    $_SESSION['alogin'] = '';
}

if (isset($_POST['login'])) {

    // 1) CSRF check
    csrf_check();

    // 2) Récupération + validation simple
    $uname = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($uname === '' || $password === '' || strlen($uname) > 80) {
        $_SESSION['msgErreur'] = "Mauvais identifiant / mot de passe.";
        header('Location: admin-login.php');
        exit;
    }

    // 3) Requête préparée (anti SQLi)
    // On récupère le hash (ou md5 si votre base est encore en md5)
    $stmt = $dbh1->prepare("SELECT UserName, Password, is_admin FROM users WHERE UserName = ? LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        exit('Erreur interne');
    }

    $stmt->bind_param("s", $uname);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $login_ok = false;

    if ($row) {
        // Cas 1: mot de passe stocké avec password_hash()
        if (password_get_info($row['Password'])['algo'] !== 0) {
            $login_ok = password_verify($password, $row['Password']);
        } else {
            // Cas 2: ancien stockage md5 (votre code actuel)
            $login_ok = hash_equals($row['Password'], md5($password));
        }
    }

    if ($row && $login_ok) {
        $_SESSION['alogin'] = $row['UserName'];
        $_SESSION['is_admin'] = (int)$row['is_admin'];

        // Bonus : régénérer l’ID de session après login
        session_regenerate_id(true);

        header('Location: dashboard.php');
        exit;
    }

    $_SESSION['msgErreur'] = "Mauvais identifiant / mot de passe.";
    header('Location: admin-login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="icon" type="image/x-icon" href="assets/images/favicon.png">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" media="screen">
  <link rel="stylesheet" href="assets/css/font-awesome.min.css" media="screen">
  <link rel="stylesheet" href="assets/css/animate-css/animate.min.css" media="screen">
  <link rel="stylesheet" href="assets/css/prism/prism.css" media="screen">
  <link rel="stylesheet" href="assets/css/main.css" media="screen">
  <script src="assets/js/modernizr/modernizr.min.js"></script>

  <style>
    .error-message{
      background-color:#fce4e4;
      border:1px solid #fcc2c3;
      float:left;
      padding:0px 30px;
      clear:both;
    }
  </style>
</head>

<body style="background-image:url(assets/images/back2.jpg); background-color:#fff; background-size:cover; background-position:center; background-repeat:no-repeat;">
  <div class="main-wrapper">
    <div class="">
      <div class="row">
        <div class="col-md-offset-7 col-lg-5">
          <section class="section">
            <div class="row mt-40">
              <div class="col-md-offset-2 col-md-10 pt-50">
                <div class="row mt-30">
                  <div class="col-md-11">
                    <div class="panel login-box" style="background:#172541;">
                      <div class="panel-heading">
                        <div class="text-center"><br>
                          <a href="#"><img style="height:70px" src="assets/images/footer-logo.png" alt=""></a>
                          <br>
                          <h3 style="color:white;"><strong>Login</strong></h3>
                        </div>
                      </div>

                      <?php if (!empty($_SESSION['msgErreur'])) { ?>
                        <p class="error-message"><?php echo htmlspecialchars($_SESSION['msgErreur']); unset($_SESSION['msgErreur']); ?></p><br><br>
                      <?php } ?>

                      <div class="panel-body p-20">
                        <form class="admin-login" method="post" action="admin-login.php">
                          <!-- CSRF token -->
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

                          <div class="form-group">
                            <label for="inputEmail3" class="control-label">Identifiant</label>
                            <input type="text" name="username" class="form-control" id="inputEmail3" placeholder="Identifiant" autocomplete="username">
                          </div>

                          <div class="form-group">
                            <label for="inputPassword3" class="control-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" id="inputPassword3" placeholder="Mot de passe" autocomplete="current-password">
                          </div>

                          <br>
                          <div class="form-group mt-20">
                            <button type="submit" name="login" class="btn login-btn">Se connecter</button>
                          </div>

                          <div class="col-sm-6">
                            <a href="index.php" class="text-white">Retour à l'accueil</a>
                          </div>
                          <br>
                        </form>
                      </div>

                    </div>
                  </div>
                  <!-- /.col-md-11 -->
                </div>
                <!-- /.row -->
              </div>
              <!-- /.col-md-12 -->
            </div>
            <!-- /.row -->
          </section>
        </div>
        <!-- /.col -->
      </div>
    </div>
  </div>

  <script src="assets/js/jquery/jquery-2.2.4.min.js"></script>
  <script src="assets/js/jquery-ui/jquery-ui.min.js"></script>
  <script src="assets/js/bootstrap/bootstrap.min.js"></script>
  <script src="assets/js/pace/pace.min.js"></script>
  <script src="assets/js/lobipanel/lobipanel.min.js"></script>
  <script src="assets/js/iscroll/iscroll.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
