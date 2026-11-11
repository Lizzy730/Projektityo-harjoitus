<?php // EI SIIS VIELÄ OLE LAITETTU SIIHEN ULOSKIRJAUTUMIS NAPPIIN LOGIIKKAA MUTTA TÄSSÄ SE NYT ISTUU, KUN SE PITÄÄ LAITTAA
session_start(); // Aloitettaa istunnon
session_destroy(); // Tuhoaa istunnon
header("Location: kirjautuminen.php?viesti=ulos_ok");
exit();
?>
