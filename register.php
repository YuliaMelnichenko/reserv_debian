<?php
ob_start();
session_start();

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
<link rel="stylesheet" type="text/css" href="style/style.css" />
</head>
<body bgcolor="#ffffff">
<div align="left">

<?php
$err = array(); 
	
include_once __DIR__ . "/php_tori/connect.php";

if (isset( $_POST['r_button']) ) {
  $login = trim((string) ($_POST['r_login'] ?? ''));

  $query = db_query($link, 'SELECT COUNT(id) AS cnt FROM employees WHERE login = ?', 's', array($login));
  $merr = mysqli_error($link);

  $row = mysqli_fetch_assoc($query);

  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    if($row && $row['cnt'] > 0) 
    { 
      $err[] = "Пользователь с таким логином уже существует"; 
    }

    if ( count($err) == 0 )
    {
      if(strlen($_POST['r_login']) < 3 or strlen($_POST['r_login']) > 30) 
      { 
        $err[] = "Логин должен быть не меньше 3-х символов и не больше 30"; 
      }
    }

    if ( count($err) == 0 )
    {
      if((string) $_POST['r_passwd'] !== (string) $_POST['r_passwd_rep'])
      { 
        $err[] = "Пароль и его повтор не совпадают"; 
      }
    }

    if ( count($err) == 0 )
    {
      if(!preg_match("/^[a-zA-Z0-9]+$/",$_POST['r_login'])) 
      { 
        $err[] = "Логин может состоять только из букв английского алфавита и цифр"; 
      }
    } 

    if ( count($err) == 0 )
    {
      if(strlen($_POST['r_passwd']) < 3 or strlen($_POST['r_passwd']) > 30) 
      { 
        $err[] = "Пароль должен быть не меньше 3-х символов и не больше 30"; 
      }
    }

    if ( count($err) == 0 )
    {
      if(!preg_match("/^[a-zA-Z0-9]+$/",$_POST['r_passwd'])) 
      { 
        $err[] = "Пароль может состоять только из букв английского алфавита и цифр"; 
      }
    } 

    if ( count($err) == 0 )
    {
      if( strlen($_POST['r_surname']) < 1 or strlen($_POST['r_surname']) > 50 )
      { 
        $err[] = "Поле ФАМИЛИЯ должно быть не пустым и не больше 50 символов"; 
      }
    }

    if ( count($err) == 0 )
    {
      if( strlen($_POST['r_first_name']) < 1 or strlen($_POST['r_first_name']) > 50 )
      {   
        $err[] = "Поле ИМЯ должно быть не пустым и не больше 50 символов"; 
      }
    }

    if ( count($err) == 0 )
    {
      if( strlen($_POST['r_second_name']) < 1 or strlen($_POST['r_second_name']) > 50 )
      { 
        $err[] = "Поле ОТЧЕСТВО должно быть не пустым и не больше 50 символов"; 
      }
    }

    if(count($err) == 0) 
    { 
      $login = trim((string) $_POST['r_login']);
      $passwd = md5(md5(trim((string) $_POST['r_passwd'])));

      $surname = $_POST['r_surname'];
      $first_name = $_POST['r_first_name'];
      $second_name = $_POST['r_second_name'];
    
      $query = mysqli_query($link, "SELECT MAX(id) FROM employees");
      $merr = mysqli_error($link);
      if (!$query){ 
        $err[] = $merr; die(); 
      }
      else {
        $row = mysqli_fetch_row($query);
        $newuserid = ($row ? $row[0] : 0) + 1;
        $res = mysqli_query($link, "BEGIN");

        mysqli_set_charset($link, "utf8");
          
        $res = db_execute($link, 'INSERT INTO employees VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', 'isssssssi', array($newuserid, $login, $passwd, $first_name, $second_name, $surname, '', '', -1));

        $merr = mysqli_error($link);
        if (!$res) { 
          echo database_error_message($link, __FILE__ . ':' . __LINE__);
          $err[] = $merr; 
          mysqli_query($link, "ROLLBACK");	
        }
        else {
          mysqli_query($link, "COMMIT");
        }
      }

      header("Location: index.php");
      exit(); 

    }
    else {
      echo "При регистрации возникли следующие ошибки:<br>"; 
      foreach($err AS $error) 
      { 
        echo "- ".$error."\n"; 
      }
      echo "<br><br>"; 
      unset( $_POST['r_login']);
    }
  }
}

echo "<h6>Для регистации заполните необходимые сведения</h6>";

echo "<form action=\"register.php\" method=\"post\">";
echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_ensure_token(), ENT_QUOTES, 'UTF-8') . '">';
echo "<table><tr>";
echo "<td class=\"rg\">Логин</td>";
echo "<td class=\"rg\"><input name=\"r_login\" style=\"width:255px;\" type=\"text\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Пароль</td>";
echo "<td class=\"rg\"><input name=\"r_passwd\" style=\"width:255px;\" type=\"password\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Повтор пароля</td>";
echo "<td class=\"rg\"><input name=\"r_passwd_rep\" style=\"width:255px;\" type=\"password\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Фамилия</td>";
echo "<td class=\"rg\"><input name=\"r_surname\" style=\"width:255px;\" type=\"text\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Имя</td>";
echo "<td class=\"rg\"><input name=\"r_first_name\" style=\"width:255px;\" type=\"text\" value=\"\"></td>";
echo "</tr><tr>";
echo "<td class=\"rg\">Отчество</td>";
echo "<td class=\"rg\"><input name=\"r_second_name\" style=\"width:255px;\" type=\"text\" value=\"\"></td>";
echo "</tr>";
echo "</table>";
echo "<input type=\"submit\" name=\"r_button\" value=\"Зарегистрироваться\"/><br>";
echo "<br><a href=\"index.php\" class=\"ml\" title=\"Вернуться на главную страницу\">На главную</a>";
?>

</div>
</body>
</html>
