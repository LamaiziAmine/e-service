<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "projet_web"; // Updated to match your new DB

$connection = new mysqli($host, $username, $password, $database);
if ($connection->connect_error) {
    die("Connexion échouée : " . $connection->connect_error);
}
?>
