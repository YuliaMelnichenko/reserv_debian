<!-- 
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();
// $usersInfo = $_SESSION["ss_user_info"];

// $userID = $usersInfo[1][$userNum];	
// $userRate = $usersInfo[2][$userNum];	
// $userDefaultStartTime = $usersInfo[3][$userNum];
// $userDefaultStartHour = $usersInfo[4][$userNum];
// $userDefaultStartMinute = $usersInfo[5][$userNum];
// $userAllowedDelay = $usersInfo[6][$userNum];

$userID = $_SESSION['ss_id'];
$userRate = $_SESSION['ss_rate'];	
$userDefaultStartTime = $_SESSION['ss_defaultStartTime'];
$userDefaultStartHour = $_SESSION['ss_defaultStartHour'];
$userDefaultStartMinute = $_SESSION['ss_defaultStartMinute'];
$userAllowedDelay = $_SESSION['ss_allowedDelay'];

include_once "/var/www/tori/funcs.php";

$currDate = date('Y-m-d');

$rowsContent = array();

$index = 0;

$userCount = count($userID);

$dateWidth = 100;
$cellWidth = 205;

foreach ( $usersInfo[7][0][0] as $currentMonthDate ) {
  $userCounter = 1;

  $currentDayName = GetWeekDayNameD( $currentMonthDate );

  $sufix = "";

  if ( $currentMonthDate == $currDate ){
    $sufix = "<br>текущий рабочий день";
  }

  $rowContent  = "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = $dateWidth >";
  $rowContent .= "<h5>$currentMonthDate<br>$currentDayName</h5><br><h5 class=\"smallBlue\">".$sufix."</h5>";
  $rowContent .= "</td>";

  for ( $userNum = 0; $userNum < $userCount; $userNum ++ ){
    $userID = $usersInfo[1][$userNum];
    $userDefaultStartTime = $usersInfo[3][$userNum];
    $userAllowedDelay = $usersInfo[6][$userNum];	
    $rowContent .= get_cell_content_by_stat( $usersInfo[7][$userNum], $index, $cellWidth, $userID, $userDefaultStartTime, $userAllowedDelay );

    if ( $userCounter < 5 ){
      $userCounter ++;
    }
    else{
      if ( $userNum != $userCount - 1 ) {
        $rowContent .= "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = $dateWidth >";
        $rowContent .= "<h5>$currentMonthDate<br>$currentDayName";
        $rowContent .= "</td>";
        $userCounter = 0;
      }
    }
  }

  $rowsContent[] = $rowContent;

  $areThereShowedResult = 0;
  $headContent = "";
  $enlarge = 0;

  for ( $resType = 1; $resType <= 6; $resType ++ ){
    $rowResContent = "";
    $typeShowed = 0;
    $userCounter = 1;

    for ( $userNum = 0; $userNum < $userCount; $userNum ++ ){
      $userID = $usersInfo[1][$userNum];
  
      $rowResContent .= get_results_cell_content_by_stat( $usersInfo[7][$userNum], $index, $cellWidth, $userID, $user_defaultStartTimeStr, $user_allowedDelay, $resType, $typeShowed, $headContent );

      if ( $userCounter < 5 ){
        $userCounter ++;
      }
      else{
        if ( $rowResContent != "" ){
          if ( $userNum != $userCount - 1 ){
            $rowResContent .= $headContent;
            $userCounter = 0;
            $enlarge ++;
          }
        }  
      }
    }

    if ( $rowResContent != "" ){
      $rowContent = "";
      for ( $userNum = 0; $userNum < $userCount + $enlarge + 1; $userNum ++ ){
        $rowContent  .= "<td class=\"nopadding\" bgcolor=\"#555555\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" height = 1></td>";
      }            

      $rowsContent[] = $rowContent;                                                                                                      
    
      $rowsContent[] = $rowResContent; 

      $areThereShowedResult = 1; 
    }
  }

  if ( $areThereShowedResult == 1 ){
    $rowContent = "";
    for ( $userNum = 0; $userNum < $userCount + $enlarge + 1; $userNum ++ ) {
      $rowContent  .= "<td class=\"nopadding\" bgcolor=\"#555555\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" height = 1></td>";
    } 
    $rowsContent[] = $rowContent;
  }
  $index ++;
}
 
echo "<table class=\"slim\" id=\"time_report_table_body\" border=1>";

for ( $idx = count( $rowsContent ); $idx >= 0; $idx -- ) {
  echo "<tr>";
  
    echo $rowsContent[$idx];  

  echo "</tr>";
}

echo "</table>"; -->
