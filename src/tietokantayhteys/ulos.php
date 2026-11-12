<?php
session_start(); // Hakee nykyisen istunnon
session_unset(); // Tyhjentää istunnon muuttujat
session_destroy(); // Tuhoaa istunnon

// Ohjaa käyttäjän kirjautumissivulle
header("Location: kirjautuminen.php?viesti=ulos_ok");
exit();
?>

