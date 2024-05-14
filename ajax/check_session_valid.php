<?
session_start();

if ( ! isset( $_SESSION['ss_sessid'] ) || ! isset( $_SESSION['ss_id'] ) || ! isset( $_SESSION['ss_startDTStr'] ) || ! isset( $_SESSION['ss_stopDTStr'] ) )
{
  echo "0";
  return;
}
else
{
  if ( isset( $_SESSION['ss_startDTStr'] ) &&  isset( $_SESSION['ss_stopDTStr'] ) )
  {

    $startDTStr = $_SESSION['ss_startDTStr'];
    $stopDTStr = $_SESSION['ss_stopDTStr'];

    $startDTStrVal = strtotime( $startDTStr );
    $stopDTStrVal = strtotime( $stopDTStr );

    include_once "../funcs.php";

    $timeArr = get_current_datetime_in_timezone();

    $dateTime = $timeArr[1];

    $dateTimeVal = strtotime( $dateTime );

    $currentDate = get_current_datetime_in_timezone_str( 1, 0 );
    $user_dayTransitionTime = $_SESSION['$ss_dayTransitionTime'];

    $timeArr = datetimestr_to_day_start_stop_DT_ex_str( $currentDate, $user_dayTransitionTime );

    $startDTCalcStr = $timeArr[0];
    $stopDTCalcStr = $timeArr[1];

    $startDTCalcVal = strtotime( $startDTCalcStr );
    $stopDTCalcVal = strtotime( $stopDTCalcStr );

    if ( $startDTStrVal == $startDTCalcVal && $stopDTStrVal == $stopDTCalcVal )
    {
      if ( $dateTimeVal >= $startDTStrVal && $dateTimeVal <= $stopDTStrVal )
      {
        $ss_defaultStartTime = $_SESSION['ss_defaultStartTime'];
        $ss_allowedDelay = $_SESSION['ss_allowedDelay'];
     
        $ss_defaultStartTimeWithDelayValCalc = strtotime( date("H:i:s", strtotime($ss_defaultStartTime." + ".$ss_allowedDelay." minute")));
    
        $ss_defaultStartTimeWithDelayValExist = $_SESSION['ss_defaultStartTimeWithDelayVal'];
       
        if ( $ss_defaultStartTimeWithDelayValCalc == $ss_defaultStartTimeWithDelayValExist ) 
        {
          echo "1";
          return;
        }
        else
        {
          $_SESSION['ss_defaultStartTimeWithDelayVal'] = $ss_defaultStartTimeWithDelayValCalc;
        
          if ( $_SESSION['ss_state'] !=0 )
          {
            $currentDateT = get_current_datetime_in_timezone_str( 1, 0 );
            $user_dayTransitionTime = $_SESSION['$ss_dayTransitionTime'];

            $timeArr = datetimestr_to_day_start_stop_DT_ex_str( $currentDate, $user_dayTransitionTime );

            $differTimeSecStr = $timeArr[4];

            $differTimeSec = time_to_second( $differTimeSecStr );         

            if ( $differTimeSec < 3600 * 3 )
            {
              $_SESSION['ss_dayWasChanged'] = 1;
              echo "0";
              return;
            }
          }
          echo "0";
          return;
        }                                                                       
      } 
      else
      {
        echo "0";
        return;
      }
    }
    else
    {
      if ( $_SESSION['ss_state'] !=0 && $_SESSION['ss_state'] !=1 )
      {
        include_once "../funcs.php";

//$sstate = $_SESSION['ss_state'];

        $currentDateT = get_current_datetime_in_timezone_str( 1, 0 );
        $user_dayTransitionTime = $_SESSION['$ss_dayTransitionTime'];

        $timeArr = datetimestr_to_day_start_stop_DT_ex_str( $currentDate, $user_dayTransitionTime );

        $differTimeSecStr = $timeArr[4];

        $differTimeSec = time_to_second( $differTimeSecStr );         


      //echo "[[[".$sstate."---".$differTimeSec."]]]";


        if ( $differTimeSec < 3600 * 3 )
        {
          $_SESSION['ss_dayWasChanged'] = 1;
          echo "0";
        }

        echo "1";  
        return;
      }
      echo "1";
      return;
    }
  }
  else
  {
    echo "0";
    return;
  }  
}
 	
?>

                                                                         