<?

/*include_once "funcs.php";

$userName = "";
$userPass = "";

get_cookie( $userName, $userPass );

echo "$userName | $userPass<br>";*/

//include_once "get_login_from_cookie.php";

session_start();

echo "| ".$_COOKIE['T_O_R_I_USERNAME']." ".$_COOKIE['T_O_R_I_PASSWORD']."|";


//echo " ";
//include_once "get_passwd_from_cookie.php";



?>