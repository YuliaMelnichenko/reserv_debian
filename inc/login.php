<?
function Log_In( $_login, $_passwd )
{
  include_once"../php/connect.php";
  $login_ = mysql_real_escape_string( $_login );
  $passwd_ = mysql_real_escape_string( $_passwd );

  $query = mysql_query("select passwd FROM employee WHERE login='$login_'"); 
  $merr=mysql_error();
  if (!$query) 
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else
  {
    $data = mysql_fetch_assoc($query); 
    
    if($data['passwd'] === md5(md5($_POST['passwd_']))) 
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}
	


			$_SESSION['ss_login'] = (string)$_POST['login'];
			$_SESSION['ss_passwd'] = (string)$data['passwd'];
			$_SESSION['ss_sid'] = session_id();
			$_SESSION['ss_userid'] = 	(string)$data['userid'];
			$_SESSION['ss_role'] = (int)$data['role'];
			$_SESSION['ss_ip'] = $_SERVER['REMOTE_ADDR'];

			mysql_query( 'SET NAMES utf8' );

			$query1 = mysql_query("select fio, company, email, web, phone, fax, cladr_subj, cladr_distr, cladr_settl, comments FROM eco_uai WHERE userid='".$data['userid']."' LIMIT 1"); 
			$merr=mysql_error();
			if (!$query1) 
			{
				echo "<br>mysql_error = $merr<br>";
			}		

			$data1 = mysql_fetch_assoc($query1);

			#Проверить, нужны ли эти переменные
			$_SESSION['ss_fio'] = $data1['fio'];
			$_SESSION['ss_company'] = $data1['company'];
			$_SESSION['ss_email'] = $data1['email'];
			$_SESSION['ss_web'] = $data1['web'];
			$_SESSION['ss_phone'] = $data1['phone'];
			$_SESSION['ss_fax'] = $data1['fax'];
			$_SESSION['ss_cladr_subj'] = $data1['cladr_subj'];
			$_SESSION['ss_cladr_distr'] = $data1['cladr_distr'];
			$_SESSION['ss_cladr_settl'] = $data1['cladr_settl'];
			$_SESSION['ss_comments'] = $data1['comments'];
			#

			$_SESSION['ss_calcs_st1agent'] = $data1['fio'];
			$_SESSION['ss_calcs_st1company'] = $data1['company'];
			$_SESSION['ss_calcs_st1address'] = "";
			$_SESSION['ss_calcs_st1email'] = $data1['email'];
			$_SESSION['ss_calcs_st1web'] = $data1['web'];
			$_SESSION['ss_calcs_st1phone'] = $data1['phone'];
			$_SESSION['ss_calcs_st1fax'] = $data1['fax'];


			$_SESSION['ss_cladr_subj'] = $data1['cladr_subj'];
			$_SESSION['ss_cladr_distr'] = $data1['cladr_distr'];
			$_SESSION['ss_cladr_settl'] = $data1['cladr_settl'];


			$_SESSION['ss_calcs_st1comments'] = $data1['comments'];

      $_SESSION['ss_calcs_state']=0;
			settype($_SESSION['ss_calcs_state'], "integer");

			if ( ! isset( $_SESSION['ss_login_finished'] ) )
			{
				#Register authorized logon
				include_once "inc/behav_mod/algo.php";
				$output_str = (string)$_SESSION['ss_userid'];
				Fill_spec_record_Encode( $output_str, 1, 1, -1 );
				$_SESSION['ss_login_finished'] = 1;
			}
			
      header("Location: ".$LastWisitedPage); 
			exit(); 
		}
		else
		{
      echo "<div align=\"center\">";
			echo "<table><tr><td align=\"left\">";
			echo "<h3 class=\"alert\">При авторизации произошла ошибка:<br>"; 
			echo "<h3 class=\"rg\">Неверны имя пользователя или пароль<br>";
      echo "<a href=\"\" class=\"rg\" title=\"Восстановление пароля\">Восстановить пароль?</a>";
      echo "<br></td></tr></table>";
		}
	}
	else
	{
		echo "<div align=\"center\">";
		echo "<table width=470><tr><td align=\"left\">";
		echo "<h3 class=\"alert\">При авторизации произошли следующие ошибки:<br>"; 

		echo "<h3 class=\"small\">"; 
		foreach($err AS $error) 
  	{ 
  		echo "- ".$error."<br>"; 
  	} 
		echo "</h3></td></tr></table>";  	
 	} 
}

include_once 'loginform.php';
?>