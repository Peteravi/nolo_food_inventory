<?php
$host = "localhost";
$port = "3307";
$dbname = "manueltesis";
$user = "root";
$password = "1234";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa"; // opcional: puedes comentarlo si ya no quieres mostrarlo
} catch(PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
