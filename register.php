<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
require_once __DIR__ . '/inc/employee_registration.php';
require_page_director();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>                                                                                                                   
<head>
<title>Регистрация сотрудника</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META NAME="Author" CONTENT="InTec">
<link rel="stylesheet" type="text/css" href="style/main.css" />
</head>
<body bgcolor="#ffffff">
<div align="left">

<?php
$err = array(); 
	
include_once __DIR__ . "/php_tori/connect.php";

if (isset( $_POST['r_button']) ) {
  $err = register_employee($link, $_POST);

  if (!$err) {
    header("Location: index.php");
    exit();
  }

  if (count($err) > 0) {
    echo "При регистрации возникли следующие ошибки:<br>";
    foreach($err AS $error)
    {
      echo "- ".html_escape($error)."\n";
    }
    echo "<br><br>";
    unset( $_POST['r_login']);
  }
}

echo "<h6>Для регистации заполните необходимые сведения</h6>";

echo "<form action=\"register.php\" method=\"post\">";
echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_ensure_token(), ENT_QUOTES, 'UTF-8') . '">';
echo "<table><tr>";
echo "<td class=\"rg\">Логин</td>";
echo "<td class=\"rg\"><input class=\"registration-input\" name=\"r_login\" type=\"text\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Пароль</td>";
echo "<td class=\"rg\"><input class=\"registration-input\" name=\"r_passwd\" type=\"password\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Повтор пароля</td>";
echo "<td class=\"rg\"><input class=\"registration-input\" name=\"r_passwd_rep\" type=\"password\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Фамилия</td>";
echo "<td class=\"rg\"><input class=\"registration-input\" name=\"r_surname\" type=\"text\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Имя</td>";
echo "<td class=\"rg\"><input class=\"registration-input\" name=\"r_first_name\" type=\"text\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Отчество</td>";
echo "<td class=\"rg\"><input class=\"registration-input\" name=\"r_second_name\" type=\"text\" value=\"\"></td>";
echo "</tr>";
echo "</table>";
echo "<input type=\"submit\" name=\"r_button\" value=\"Зарегистрироваться\"/><br>";
echo "<br><a href=\"index.php\" class=\"ml\" title=\"Вернуться на главную страницу\">На главную</a>";
?>

</div>
</body>
</html>
