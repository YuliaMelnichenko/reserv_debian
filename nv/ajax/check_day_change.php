<?
session_start();

$ss_dayWasChanged = $_SESSION['ss_dayWasChanged'];

if ( $ss_dayWasChanged == 1 )
{
  $_SESSION['ss_dayWasChanged'] = 0;
//  echo "1";
  echo "0";
}
else
{
  echo "0";
} 	
?>

                                                                         