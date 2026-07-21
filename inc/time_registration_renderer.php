<?php

require_once __DIR__ . '/../funcs.php';

function change_time ($user) {
  include __DIR__ . "/../php_tori/connect.php";
  include_once __DIR__ . "/../funcs.php";

  mysqli_set_charset($link, "utf8");

  $currentTime = date("H:i:s");
  $currentDayNumber = GetWeekDayD(date("Y-m-d"));
  $daysBack = ($currentDayNumber == "1") ? 3 : 1;

  $content = "";

  $visitQuery = db_query(
    $link,
    "SELECT ID, out_dt, eat_start_dt, eat_stop_dt
     FROM visiting
     WHERE user_id = ?
       AND DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $daysBack DAY))
     ORDER BY in_dt DESC, ID DESC
     LIMIT 1",
    'i',
    array((int)$user)
  );

  if (!$visitQuery || mysqli_num_rows($visitQuery) == 0) {
    return $content;
  }

  $row = mysqli_fetch_assoc($visitQuery);
  $visitID = (int)$row["ID"];
  $out_value = isset($row["out_dt"])
    ? $row["out_dt"]
    : "0000-00-00 00:00:00";

  $eat_start_value = isset($row["eat_start_dt"])
    ? $row["eat_start_dt"]
    : "0000-00-00 00:00:00";

  $eat_stop_value = isset($row["eat_stop_dt"])
    ? $row["eat_stop_dt"]
    : "0000-00-00 00:00:00";

  $hasOpenLunch = ($eat_start_value !== "0000-00-00 00:00:00" && $eat_stop_value == "0000-00-00 00:00:00");

  $content .= "<input type=\"hidden\" id=\"change_visit_id\" value=\"$visitID\">";

  if ( $hasOpenLunch ){
    $content .= "<tr>";
    $content .= change_eat_stop_time( $currentTime);
    $content .= "</tr>";
    $content .= "<tr>";
    $content .= change_out_time_disabled( $out_value, $currentTime );
    $content .= "</tr>";
  }
  else {
    $content .= "<tr>";
    $content .= change_out_time( $out_value, $currentTime );
    $content .= "</tr>";
  }

  return $content;
}

function change_out_time ( $out_value, $currentTime ) {
  $bgcolor = "#AAFFAA";
  $content = "";

  if ( $out_value == "0000-00-00 00:00:00" ) {
    if ( $currentTime >= "09:00:00" && $currentTime < "19:30:00" ) {
      $content .= "<td class=\"nopadding_s\">";
      $content .= "<h5 class=\"change_time\">Добавить время ухода?</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s\" bgcolor=\"$bgcolor\" width=80 align = \"center\">";
      $content .= "<button id = \"add_out_time\" title = \"Добавить время.\" style=\"font-size: 80%; padding: 0px 0px 0px 0px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"enter_out_time();\"><img src=\"img/red.png\"></button>";
      $content .= "</td>";
    }
  }
  return $content;
}

function change_out_time_disabled ( $out_value, $currentTime ) {
  $content = "";

  if ( $out_value == "0000-00-00 00:00:00" ) {
    if ( $currentTime >= "09:00:00" && $currentTime < "19:30:00" ) {
      $content .= "<td class=\"nopadding_s\">";
      $content .= "<h5 class=\"change_time\">Добавить время ухода?</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s\" bgcolor=\"#DDDDDD\" width=80 align=\"center\">";
      $content .= "<button id=\"add_out_time_disabled\" disabled title=\"Сначала добавьте время прихода с обеда.\" style=\"font-size: 80%; padding: 0px 0px 0px 0px; background-color:#dddddd; border:1px solid #888888; cursor:not-allowed;\"><img src=\"img/red.png\"></button>";
      $content .= "</td>";
    }
  }

  return $content;
}

function change_eat_stop_time ( $currentTime ) {
  $bgcolor = "#AAFFAA";
  $content = "";

  if ( $currentTime >= "09:00:00" && $currentTime < "11:30:00" ) {
    $content .= "<td class=\"nopadding_s\">";
    $content .= "<h5 class=\"change_time\">Добавить время прихода с обеда?</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s\" bgcolor=\"$bgcolor\" width=80 align = \"center\">";
    $content .= "<button id = \"add_stop_eat\" title = \"Добавить время.\" style=\"font-size: 80%; padding: 0px 0px 0px 0px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"enter_stop_eat_time();\"><img src=\"img/din.png\"></button>";
    $content .= "</td>";
  }
  return $content;
}

function in_time_part( $datetime, $crossDay, $isThereDelay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $bgcolor = "";
  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
      $content .= "<h5 class=\"big\">Время прихода на рабочее место</h5>";
    $content .= "</td>";

    if ( $isThereDelay == 1 ) {
      $bgcolor = '#FFFFAA';
    }
    if ( $isThereDelay == 2 ) {
      $bgcolor = '#FFAAAA';
    }

    $content .= "<td class=\"nopadding_s\" bgcolor=\"$bgcolor\" width = \"$timeRestributionValWidth\" align = \"center\">";
    if ( $isThereDelay == 2 ) {
      if ( $crossDay == 1 ) {
        $datetime = split_data_and_time_by_nl_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      else {
        $datetime = datetime_to_time_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      $content .= " <button id = \"explBtn\" title = \"Внести объяснения к опозданию.\" style=\"font-size: 80%; padding: 0px 0px 0px 0px; background-color:#ffffff; border:1px solid #888888;\" onclick=\"add_expl();\"><img src=\"img/report_small.png\"></button>";
    }
    else {
      if ( $crossDay == 1 ) {
        $datetime = split_data_and_time_by_nl_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      else {
        $datetime = datetime_to_time_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
    }
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function eat_start_part( $datetime, $crossDay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
      $content .= "<h5 class=\"big\">Время ухода на обед</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionValWidth\" align = \"center\">";
    if ( $crossDay == 1 ) {
      $datetime = split_data_and_time_by_nl_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    else {
      $datetime = datetime_to_time_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function eat_stop_part( $datetime, $crossDay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
        $content .= "<h5 class=\"big\">Время прихода с обеда</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionValWidth\" align = \"center\">";
      if ( $crossDay == 1 ) {
        $datetime = split_data_and_time_by_nl_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      else {
        $datetime = datetime_to_time_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      $content .= "</td>";
    $content .= "</tr>";
  return $content;
}

function out_time_part( $datetime, $crossDay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $bgcolor = "#AAFFAA";
  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
      $content .= "<h5 class=\"big\">Время ухода с рабочего места</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s\" bgcolor=\"$bgcolor\" width = \"$timeRestributionValWidth\" align = \"center\">";
    if ( $crossDay == 1 ) {
      $datetime = split_data_and_time_by_nl_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    else {
      $datetime = datetime_to_time_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function empty_line() {
  $content = "";
  $content .= "<tr height = 10 >";
  $content .=   "<td align = \"right\" class=\"nopadding_s\">";
  $content .=   "</td>";
  $content .= "</tr>";
  return $content;
}

function pure_work_day_duration_part( $time, $norm, $check, $timeRestributionDescWidth, $timeRestributionValWidth, $msg, $rightAlign, $showRMTime ) {
  $bgcolor = "";
  $addonStr = "";

  if ( $showRMTime == 1 ) {
    $tms = time_to_second( $time );
    $formatedTime =redmine_represent( $tms );
    $addonStr = " (RM: ".$formatedTime.")";
  }

  if( $check == 1 ) {
    if ( strtotime( $time ) >= strtotime( $norm ) ) {
      $bgcolor = "#AAFFAA";
    }
    else {
      $bgcolor = "#FFAAAA";
    }
  }
  $content = "";
  $content .= "<tr>";
    if ( $rightAlign == 1 ) {
      $content .= "<td align = \"right\" class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
      $content .= "<h5 class=\"biggreen1\">$msg</h5>";
    }
    else {
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
      $content .= "<h5 class=\"biggreen1\">$msg</h5>";
    }
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionValWidth\" title=\"выделение цветом: \nзеленый - продолжительность рабочего времени не менее нормы\nкрасный - меньше\" bgcolor=\"$bgcolor\" width=80 align = \"center\">";
      $content .= "<h5 class=\"big\">".$time.$addonStr."</h5>";
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function add_time_work_day_duration_part( $time, $valid, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";

  if ( $valid ) {
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
        $content .= "<h5 class=\"biggreen1\">Продолжительность работы вне офиса</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionValWidth\" align = \"center\">";
        $content .= "<h5 class=\"big\">".$time."</h5>";
      $content .= "</td>";
    $content .= "</tr>";
  }
  return $content;
}

function add_pause_work_day_duration_part( $time, $valid, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";

  if ( $valid ) {
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
        $content .= "<h5 class=\"bigred\">Продолжительность приостановки учета времени</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionValWidth\" align = \"center\">";
        $content .= "<h5 class=\"big\">".$time."</h5>";
      $content .= "</td>";
    $content .= "</tr>";
  }
  return $content;
}

function eat_duration_part( $time, $norm, $check, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $bgcolor = "";

  if( $check == 1 ) {
    if ( strtotime( $time ) <= strtotime( $norm ) ) {
      $bgcolor = "#AAFFAA";
    }
    else {
      $bgcolor = "#FFAAAA";
    }
  }

  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
      $content .= "<h5 class=\"biggreen1\">Продолжительность обеденного времени</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionValWidth\" title=\"выделение цветом: \nзеленый - продолжительность обеда не более 1 часа\nкрасный - свыше\" bgcolor=\"$bgcolor\" width=80 align = \"center\">";
      $content .= "<h5 class=\"big\">".$time."</h5>";
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function delay_part( $time, $valid, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
  if ( $valid ) {
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionDescWidth\">";
        $content .= "<h5 class=\"biggreen1\">Длительность опоздания</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s\" width = \"$timeRestributionValWidth\" align = \"center\">";
        $content .= "<h5 class=\"big\">".$time."</h5>";
      $content .= "</td>";
    $content .= "</tr>";
  }
  return $content;
}

