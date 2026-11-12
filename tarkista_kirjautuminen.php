<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jos EI ole kirjautunut sisään = Potkitaan käyttäjä pois
if (!isset($_SESSION["kirjautunut"]) || $_SESSION["kirjautunut"] !== true) {
    header("Location: kirjautuminen.php?virhe=vaatii_kirjautumisen");
    exit();
}
?>
