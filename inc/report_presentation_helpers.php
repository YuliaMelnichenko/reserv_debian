<?php

require_once __DIR__ . '/output.php';
require_once __DIR__ . '/time_format.php';
require_once __DIR__ . '/work_duration.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/date_range.php';

function represent_is_time_defined( $time, $crossDayPeriod ){
  $valid = 1;

  if ( $crossDayPeriod == 0 ){
    if ( $time == "0000-00-00 00:00:00" ){
      $time = "_____-__-__ ___:___:__";
      $valid = 0;
    }
  }
  else{
    $time = substr( $time, 11, 8 );
    if ( $time == "00:00:00" ){
      $time = "__:__:__";
      $valid = 0;
    }
  }

  return array($time, $valid);
}

function get_range_by_times_pair( $firstTime, $secondTime, $currentDay, $workTime, $defaultInTime, $allowedDelay, $crossDayPeriod ){
  $currentDate = Date("Y-m-d");

  $result = "<h5 class=\"middleSmall\">";

  $styleClass = "middleSmall";

  if ( $currentDay == "0" ){
    $styleClass = "middleRedSmall";
  }
  else{
    $styleClass = "middleInvisible";
  }

  $timeArray = represent_is_time_defined($firstTime, $crossDayPeriod);
  $firstTime = $timeArray[0];
  $validTime = $timeArray[1];

  if ( $validTime == 1 ) {
    $result = $result . "<h5 class=\"middleSmall\">". $firstTime. " - </h5>";
  }
  else
  {
    $result = $result . "<h5 class=\"middleSmallGrey\">". $firstTime. " - </h5>";
  }

  $timeArray = represent_is_time_defined($secondTime, $crossDayPeriod);

  $secondTime = $timeArray[0];
  $validTime  = $timeArray[1];

  if ( $validTime == 1 )
  {
    $result = $result . " <h5 class=\"middleSmall\">".$secondTime. "</h5>";
  }
  else
  {
    $result = $result . "  <h5 class=\"middleSmallGrey\"> ". $secondTime. "</h5>";
  }

  return $result;
}

function colored_result( $prefix, $realTime, $needTime, $inverse, $check, $isresult ){
  $resultStr = format_time_d_hhmmss_pure( $realTime );

  if ( $isresult == 1 ){
    $colorClass = "bigbigbig";
  }
  else{
    $colorClass = "middle";
  }

  $resAdd1 = "(";
  $resAdd2 = ")";

  if( $isresult ) {
    $resAdd1 = "";
    $resAdd2 = "";
  }

  $result = "<h5 class=\"$colorClass\">$prefix$resAdd1$resultStr$resAdd2";


  if ( $check == 1 ){
    if( $inverse == 1 ){
      if ( $realTime > $needTime ){
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
    else{
      if ( $realTime < $needTime ){
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
  }
  return $result;
}

function colored_result_partial( $prefix, $realTime, $needTime, $inverse, $check, $isresult ){
  $resultStr = format_time_d_hhmmss_pure_partial( $realTime );

  if ( $isresult == 1 ){
    $colorClass = "bigbigbig";
  }
  else
  {
    $colorClass = "middle";
  }

  $resAdd1 = "(";
  $resAdd2 = ")";

  if( $isresult )
  {
    $resAdd1 = "";
    $resAdd2 = "";
  }

  $result = "<h5 class=\"$colorClass\">$prefix$resAdd1$resultStr$resAdd2";

  if ( $check == 1 )
  {
    if( $inverse == 1 )
    {
      if ( $realTime > $needTime )
      {
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
    else
    {
      if ( $realTime < $needTime )
      {
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
  }
  return $result;
}
