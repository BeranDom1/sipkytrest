<?php
  echo "<h1>Vítejte v nové aplikaci Šipky Třešť - Jaro 2026</h1>";
  echo "<p>Server běží lokálně na Laragonu!</p>";
  
  // Kontrola spojení s databází (zatím bez hesla, jak je v Laragonu zvykem)
  $conn = new mysqli("localhost", "root", "");
  if ($conn->connect_error) {
      die("Chyba databáze: " . $conn->connect_error);
  }
  echo "Status databáze: <span style='color:green'>Připojeno!</span>";
?>