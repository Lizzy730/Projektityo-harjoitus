
<?php

$servername = "db";
$username = "root";
$password = "root";
$dbname = "varausjarjestelma";

try {
    $yhteys = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);

    $yhteys->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    die("Tietokantayhteys epäonnistui: " . $e->getMessage());
}

?>
