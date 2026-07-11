<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
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
  $login = trim((string) ($_POST['r_login'] ?? ''));
  $plainPassword = (string) ($_POST['r_passwd'] ?? '');
  $passwordRepeat = (string) ($_POST['r_passwd_rep'] ?? '');
  $surname = (string) ($_POST['r_surname'] ?? '');
  $first_name = (string) ($_POST['r_first_name'] ?? '');
  $second_name = (string) ($_POST['r_second_name'] ?? '');

  if(strlen($login) < 3 or strlen($login) > 30)
  {
    $err[] = "Логин должен быть не меньше 3-х символов и не больше 30";
  }

  if ( count($err) == 0 )
  {
    if($plainPassword !== $passwordRepeat)
    {
      $err[] = "Пароль и его повтор не совпадают";
    }
  }

  if ( count($err) == 0 )
  {
    if(!preg_match("/^[a-zA-Z0-9]+$/", $login))
    {
      $err[] = "Логин может состоять только из букв английского алфавита и цифр";
    }
  }

  if ( count($err) == 0 )
  {
    if(strlen($plainPassword) < 3 or strlen($plainPassword) > 30)
    {
      $err[] = "Пароль должен быть не меньше 3-х символов и не больше 30";
    }
  }

  if ( count($err) == 0 )
  {
    if(!preg_match("/^[a-zA-Z0-9]+$/", $plainPassword))
    {
      $err[] = "Пароль может состоять только из букв английского алфавита и цифр";
    }
  }

  if ( count($err) == 0 )
  {
    if( strlen($surname) < 1 or strlen($surname) > 50 )
    {
      $err[] = "Поле ФАМИЛИЯ должно быть не пустым и не больше 50 символов";
    }
  }

  if ( count($err) == 0 )
  {
    if( strlen($first_name) < 1 or strlen($first_name) > 50 )
    {
      $err[] = "Поле ИМЯ должно быть не пустым и не больше 50 символов";
    }
  }

  if ( count($err) == 0 )
  {
    if( strlen($second_name) < 1 or strlen($second_name) > 50 )
    {
      $err[] = "Поле ОТЧЕСТВО должно быть не пустым и не больше 50 символов";
    }
  }

  if(count($err) == 0)
  {
    if (!mysqli_begin_transaction($link)) {
      $err[] = database_error_message($link, __FILE__ . ':' . __LINE__);
    }
    else {
      $lastEmployeeResult = db_query($link, 'SELECT id FROM employees ORDER BY id DESC LIMIT 1 FOR UPDATE');

      if (!$lastEmployeeResult) {
        mysqli_rollback($link);
        $err[] = database_error_message($link, __FILE__ . ':' . __LINE__);
      }
      else {
        $lastEmployee = mysqli_fetch_assoc($lastEmployeeResult);
        $newuserid = $lastEmployee ? (int) $lastEmployee['id'] + 1 : 1;

        $duplicateResult = db_query($link, 'SELECT 1 FROM employees WHERE login = ? LIMIT 1', 's', array($login));

        if (!$duplicateResult) {
          mysqli_rollback($link);
          $err[] = database_error_message($link, __FILE__ . ':' . __LINE__);
        }
        else if (mysqli_fetch_assoc($duplicateResult)) {
          mysqli_rollback($link);
          $err[] = "Пользователь с таким логином уже существует";
        }
        else {
          $passwd = md5(md5(trim($plainPassword)));
          $res = db_execute($link, 'INSERT INTO employees VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', 'isssssssi', array($newuserid, $login, $passwd, $first_name, $second_name, $surname, '', '', -1));

          if (!$res) {
            mysqli_rollback($link);
            $err[] = database_error_message($link, __FILE__ . ':' . __LINE__);
          }
          else if (!mysqli_commit($link)) {
            mysqli_rollback($link);
            $err[] = database_error_message($link, __FILE__ . ':' . __LINE__);
          }
          else {
            header("Location: index.php");
            exit();
          }
        }
      }
    }
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
