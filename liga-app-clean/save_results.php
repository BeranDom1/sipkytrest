<?php
// save_results.php
session_start();
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'
    || !in_array($_SESSION['role']??'', ['admin','stat_editor'], true)
) {
  header('Location: edit_results.php');
  exit;
}

// začneme transakci
$conn->begin_transaction();

// připravíme ins/upsert dotaz
$sql = "
  REPLACE INTO zapasy
    ( match_id
    , score_home, score_away
    , average_home, average_away
    , high_finish_home, high_finish_away
    , count_100p_home, count_100p_away
    , count_120p_home, count_120p_away
    , count_140p_home, count_140p_away
    , count_160p_home, count_160p_away
    , count_180_home, count_180_away
    , entered_by, entered_at
    )
  VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
";
$stmt = $conn->prepare($sql);

foreach ($_POST['skore1'] as $id => $s1) {
  // pro oba hráče vytažení dat z POST
  $s2  = (int)($_POST['skore2'][$id] ?? 0);
  $avg1= (float)($_POST['average_home'][$id] ?? 0);
  $avg2= (float)($_POST['average_away'][$id] ?? 0);
  $hf1 = (int)($_POST['high_finish_home'][$id] ?? 0);
  $hf2 = (int)($_POST['high_finish_away'][$id] ?? 0);
  $c100h = (int)($_POST['count_100p_home'][$id] ?? 0);
  $c100a = (int)($_POST['count_100p_away'][$id] ?? 0);
  $c120h = (int)($_POST['count_120p_home'][$id] ?? 0);
  $c120a = (int)($_POST['count_120p_away'][$id] ?? 0);
  $c140h = (int)($_POST['count_140p_home'][$id] ?? 0);
  $c140a = (int)($_POST['count_140p_away'][$id] ?? 0);
  $c160h = (int)($_POST['count_160p_home'][$id] ?? 0);
  $c160a = (int)($_POST['count_160p_away'][$id] ?? 0);
  $c180h = (int)($_POST['count_180_home'][$id] ?? 0);
  $c180a = (int)($_POST['count_180_away'][$id] ?? 0);

  $stmt->bind_param(
    'iiiddiiiiiiiiiiiis',
    $id, $s1, $s2,
    $avg1, $avg2,
    $hf1, $hf2,
    $c100h, $c100a,
    $c120h, $c120a,
    $c140h, $c140a,
    $c160h, $c160a,
    $c180h, $c180a,
    $_SESSION['user_id']
  );

  if (!$stmt->execute()) {
    $conn->rollback();
    die("Chyba při ukládání zápasu #{$id}: " . $stmt->error);
  }
}

$conn->commit();
$stmt->close();
$conn->close();

// hotovo, zpět
header('Location: edit_results.php?success=1');
exit;
