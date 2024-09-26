<?
ob_start();
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

<?

session_start();

$err = array(); 
	
include_once"../php_tori/connect.php";

if (isset( $_POST['r_button']) )    
{

  $query = mysql_query("SELECT COUNT(id) FROM employees WHERE login='".mysql_real_escape_string($_POST['r_login'])."'"); 
  $merr=mysql_error();
  if ( !$query ) 
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else
  {
    if(mysql_result($query, 0) > 0) 
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
      if(strlen($_POST['r_passwd']) != strlen($_POST['r_passwd_rep'])) 
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
      $login = mysql_real_escape_string($_POST['r_login']);   	       
      $passwd = md5(md5(trim(mysql_real_escape_string($_POST['r_passwd'])))); 

      $surname = $_POST['r_surname'];
      $first_name = $_POST['r_first_name'];
      $second_name = $_POST['r_second_name'];
    
      $query = mysql_query("select max(id) FROM employees");
      $merr=mysql_error();
      if (!$query){ $err[] = $merr; die(); }
      else
      {
        $newuserid = mysql_result($query, 0) + 1;
        $res=mysql_query("BEGIN");
        mysql_query( 'SET NAMES utf8' );
          
        $res=mysql_query("insert into employees values ('$newuserid','$login','$passwd','$first_name','$second_name','$surname','','','-1')");
        $merr=mysql_error();
        if (!$res)
        { 
	  echo $merr;
	  $err[] = $merr; 
	  mysql_query("ROLLBACK");	
        }
        else
        {
          mysql_query("COMMIT");
        }
      }

      session_start();

      $_SESSION['ss_id'] = $newuserid;

      header("Location: index.php");
      exit(); 

    }
    else
    {
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
