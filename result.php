<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/config.php';

// Éviter de divulguer des infos techniques
error_reporting(0);
ini_set('display_errors', '0');

$rollid = '';
$classid = '';
$student = null;
$subjects = [];
$totlcount = 0;

// On ne traite que du POST (et on vérifie le CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $rollid = trim($_POST['rollid'] ?? '');
    $classid = trim($_POST['class'] ?? '');

    // Validation simple des entrées
    if ($rollid === '' || $classid === '') {
        http_response_code(400);
        $error = "Paramètres manquants.";
    } elseif (strlen($rollid) > 50 || !ctype_digit($classid)) {
        http_response_code(400);
        $error = "Paramètres invalides.";
    } else {
        $_SESSION['rollid'] = $rollid;
        $_SESSION['classid'] = $classid;

        try {
            // 1) Infos étudiant
            $qery = "SELECT
                        s.StudentName, s.RollId, s.RegDate, s.StudentId, s.Status,
                        c.ClassName, c.Section
                     FROM tblstudents s
                     JOIN tblclasses c ON c.id = s.ClassId
                     WHERE s.RollId = :rollid AND s.ClassId = :classid
                     LIMIT 1";
            $stmt = $dbh->prepare($qery);
            $stmt->bindParam(':rollid', $rollid, PDO::PARAM_STR);
            $stmt->bindParam(':classid', $classid, PDO::PARAM_INT);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                // 2) Notes / matières
                $q = "SELECT sub.SubjectName, r.marks
                      FROM tblstudents s
                      JOIN tblresult r ON r.StudentId = s.StudentId
                      JOIN tblsubjects sub ON sub.id = r.SubjectId
                      WHERE s.RollId = :rollid AND s.ClassId = :classid";
                $query = $dbh->prepare($q);
                $query->bindParam(':rollid', $rollid, PDO::PARAM_STR);
                $query->bindParam(':classid', $classid, PDO::PARAM_INT);
                $query->execute();
                $subjects = $query->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            // log serveur uniquement, jamais à l’écran
            error_log($e->getMessage());
            http_response_code(500);
            $error = "Erreur interne.";
        }
    }
} else {
    // Si accès direct, on renvoie vers la page de recherche
    header('Location: find-result.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestion des résultats des étudiants</title>

  <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
  <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
  <link rel="stylesheet" href="css/animate-css/animate.min.css" media="screen">
  <link rel="stylesheet" href="css/lobipanel/lobipanel.min.css" media="screen">
  <link rel="stylesheet" href="css/prism/prism.css" media="screen">
  <link rel="stylesheet" href="css/main.css" media="screen">
  <script src="js/modernizr/modernizr.min.js"></script>
</head>

<body>
<div class="main-wrapper">
  <div class="content-wrapper">
    <div class="content-container">
      <div class="main-page">
        <div class="container-fluid">
          <div class="row page-title-div">
            <div class="col-md-12">
              <h2 class="title" align="center">Gestion des résultats des étudiants</h2>
            </div>
          </div>
        </div>

        <section class="section" id="exampl">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <div class="panel">
                  <div class="panel-heading">
                    <div class="panel-title">
                      <h3 align="center">Note étudiant</h3>
                      <hr />
                      <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger" role="alert">
                          <?php echo htmlspecialchars($error); ?>
                        </div>
                      <?php } ?>
                    </div>
                  </div>

                  <div class="panel-body p-20">
                    <?php if ($student) { ?>
                      <p><b>Nom de l'étudiant :</b> <?php echo htmlspecialchars($student['StudentName']); ?></p>
                      <p><b>Numéro de l'étudiant :</b> <?php echo htmlspecialchars($student['RollId']); ?></p>
                      <p><b>Classe de l'étudiant :</b> <?php echo htmlspecialchars($student['ClassName']); ?> (<?php echo htmlspecialchars($student['Section']); ?>)</p>

                      <table class="table table-hover table-bordered" border="1" width="100%">
                        <thead>
                          <tr style="text-align:center">
                            <th style="text-align:center">#</th>
                            <th style="text-align:center">Subject</th>
                            <th style="text-align:center">Marks</th>
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (!empty($subjects)) {
                            $cnt = 1;
                            foreach ($subjects as $s) {
                                $marks = (int)($s['marks'] ?? 0);
                                $totlcount += $marks;
                                ?>
                                <tr>
                                  <th scope="row" style="text-align:center"><?php echo $cnt; ?></th>
                                  <td style="text-align:center"><?php echo htmlspecialchars($s['SubjectName']); ?></td>
                                  <td style="text-align:center"><?php echo $marks; ?></td>
                                </tr>
                                <?php
                                $cnt++;
                            }

                            $outof = ($cnt - 1) * 100;
                            $pct = ($outof > 0) ? ($totlcount * 100 / $outof) : 0;
                            ?>
                            <tr>
                              <th scope="row" colspan="2" style="text-align:center">Total Marks</th>
                              <td style="text-align:center"><b><?php echo $totlcount; ?></b> out of <b><?php echo $outof; ?></b></td>
                            </tr>
                            <tr>
                              <th scope="row" colspan="2" style="text-align:center">Percentage</th>
                              <td style="text-align:center"><b><?php echo number_format($pct, 2); ?> %</b></td>
                            </tr>
                            <tr>
                              <td colspan="3" align="center">
                                <i class="fa fa-print fa-2x" aria-hidden="true" style="cursor:pointer" onclick="CallPrint()"></i>
                              </td>
                            </tr>
                        <?php } else { ?>
                          <tr>
                            <td colspan="3">
                              <div class="alert alert-warning" role="alert">
                                <strong>Notice!</strong> Résultat non disponible.
                              </div>
                            </td>
                          </tr>
                        <?php } ?>
                        </tbody>
                      </table>
                    <?php } else { ?>
                      <div class="alert alert-danger" role="alert">
                        <strong>Oh snap!</strong> Identifiant étudiant invalide.
                      </div>
                    <?php } ?>

                    <div class="form-group">
                      <div class="col-sm-6">
                        <a href="index.php">Back to Home</a>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

      </div>
    </div>
  </div>
</div>

<script src="js/jquery/jquery-2.2.4.min.js"></script>
<script src="js/bootstrap/bootstrap.min.js"></script>
<script src="js/pace/pace.min.js"></script>
<script src="js/lobipanel/lobipanel.min.js"></script>
<script src="js/iscroll/iscroll.js"></script>
<script src="js/prism/prism.js"></script>
<script src="js/main.js"></script>

<script>
function CallPrint() {
  var prtContent = document.getElementById("exampl");
  var WinPrint = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
  WinPrint.document.write(prtContent.innerHTML);
  WinPrint.document.close();
  WinPrint.focus();
  WinPrint.print();
  WinPrint.close();
}
</script>
</body>
</html>
