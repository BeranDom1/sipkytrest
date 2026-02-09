<?php
// set_password.php
// 1) Připojíme DB
require_once 'd.php';      // cesta k vašemu souboru s $conn, správně je db je potřeba pokud budu chtít měnit heslo tak tam napsat db.php
session_start();            // nepotřebujete session, ale neškodí

// 2) Nastavíme nové heslo
$newPassword = 'elprezidento589';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$username = 'sebesta';

// 3) Připravíme a spustíme UPDATE pomocí MySQLi
$stmt = $conn->prepare(
  "UPDATE uzivatele 
     SET password = ? 
   WHERE username = ?"
);
$stmt->bind_param('ss', $hash, $username);
if($stmt->execute()) {
  echo "Heslo pro '$username' bylo úspěšně nastaveno na '$newPassword'.";
} else {
  echo "Chyba při ukládání: ".$stmt->error;
}
$stmt->close();
$conn->close();
