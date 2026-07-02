<?php
  ob_start();

  setcookie("T_O_R_I_PASSWORD", "", time() - 3600, "/ajax");
  setcookie("T_O_R_I_USERNAME", "", time() - 3600, "/ajax");

  session_start();
  unset($_SESSION['ss_id']);

  session_destroy();

  header("Location: index.php");
  exit();
?>