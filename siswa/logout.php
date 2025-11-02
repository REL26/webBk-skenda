<?php
session_start();
session_destroy();
header("Location: ../login.php"); // Mundur satu folder ke root
exit;
?>