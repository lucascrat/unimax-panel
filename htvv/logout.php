<?php
// Developer eonbry


session_start();
session_destroy();
setcookie("auth", "");
header("location:exit.php");

?>