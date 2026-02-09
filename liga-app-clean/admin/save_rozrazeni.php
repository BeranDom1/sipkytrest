<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

if ($_SERVER['REQUEST_METHOD']!=='POST' || !csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad request'); }
$rocnik_id = (int)($_POST['rocnik_id'] ?? 0);
if ($rocnik_id<=0) { header('Location: /liga-app/admin/index.php'); exit; }

$row = $conn->query("SELECT locked FROM rocniky WHERE id={$rocnik_id}")->fetch_assoc();
if (!$row) { http_response_code(404); exit('Sezóna nenalezena'); }
if ((int)$row['locked'] === 1) { http_response_code(403); exit('Sezóna je uzamčena – uložení zakázáno'); }

$conn->begin_transaction();
try {
  foreach($_POST as $k=>$arr){
    if (strpos($k,'liga_')===0 && is_array($arr)){
      $liga_id=(int)substr($k,5);
      foreach($arr as $hid){
        $hid=(int)$hid;
        $stmt=$conn->prepare("
          INSERT INTO hraci_v_sezone (hrac_id, rocnik_id, liga_id)
          VALUES (?,?,?) ON DUPLICATE KEY UPDATE liga_id=VALUES(liga_id)
        ");
        $stmt->bind_param('iii',$hid,$rocnik_id,$liga_id);
        $stmt->execute();
      }
    }
  }
  if (!empty($_POST['neza']) && is_array($_POST['neza'])) {
    $ids = implode(',', array_map('intval', $_POST['neza']));
    $conn->query("DELETE FROM hraci_v_sezone WHERE rocnik_id={$rocnik_id} AND hrac_id IN ($ids)");
  }
  $conn->commit();
} catch(Throwable $e){
  $conn->rollback();
  http_response_code(500); echo "Chyba: ".$e->getMessage(); exit;
}
header('Location: /liga-app/admin/rozrazeni.php?rocnik_id='.$rocnik_id);
