<?php
  ob_start();

  session_start();
  unset($_SESSION['ss_id']);

  session_destroy();

  header("Location: index.php");
?>