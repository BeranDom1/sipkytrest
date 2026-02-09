<?php
$servername = "md418.wedos.net";
$username = "w377108_liga";
$password = "Lns2c2F3";
$database = "d377108_liga";

// Vytvoření připojení
$conn = new mysqli($servername, $username, $password, $database);

// Kontrola připojení
if ($conn->connect_error) {
    die("Připojení k databázi selhalo: " . $conn->connect_error);
}

// Nastavení kódování
$conn->set_charset("utf8");
?>
