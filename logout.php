<?php
include "logger.php?event=LOGOUT";
session_start();
session_destroy(); // Hancurkan tiket sesi
header("Location: index.php"); // Balik ke gerbang
exit();
?>