<?php
session_start();
session_destroy();
unset($_SESSION["USER"]);
unset($_SESSION["PASS"]);
header('Location: index.php');
?>