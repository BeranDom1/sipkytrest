<?php
// events.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// zjistíme, pro který terč chceme data
$terc = isset($_GET['terc']) ? intval($_GET['terc']) : 0;
if ($terc < 1 || $terc > 4) {
    echo json_encode([]);
    exit;
}

// připravíme dotaz
$stmt = $conn->prepare("
  SELECT jmeno, datum, cas
    FROM rezervace
   WHERE terc_id = ?
     AND datum >= CURDATE()
");
$stmt->bind_param('i', $terc);
$stmt->execute();
$res = $stmt->get_result();

$events = [];
while ($row = $res->fetch_assoc()) {
    // start v ISO formátu
    $start = $row['datum'] . 'T' . substr($row['cas'], 0, 5);
    // end = +1 hodina
    $endTs = strtotime($row['datum'] . ' ' . $row['cas']) + 3600;
    $end   = date('Y-m-d\TH:i', $endTs);

    // barva podle jména
    $hash = substr(md5($row['jmeno']), 0, 6);
    $color = "#{$hash}";

    $events[] = [
      'title'           => $row['jmeno'],
      'start'           => $start,
      'end'             => $end,
      'allDay'          => false,
      'backgroundColor' => $color,
      'borderColor'     => $color,
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
