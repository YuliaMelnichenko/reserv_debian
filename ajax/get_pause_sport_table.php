<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = (int)$_SESSION['ss_id'];

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

echo "<h5 class=\"big\"> Текущая загруженность тренажерного зала </h5>";

echo "<table id=\"add_time_sport_table\" class=\"sport-table sport-table-current\">";
echo "<tr class=\"sport-table-head sport-table-head-short\">";
echo "<td class=\"add_time_sport sport-name-cell\">";
echo "<div class=\"person\">"."<h5 class=\"data_train\">Сотрудник</h5>"."</div>";
echo "</td>";
echo "<td class=\"add_time_sport sport-time-cell\">";
echo "<div class=\"training\">"."<h5 class=\"data_train\">Время прихода</h5>"."</div>";
echo "</td>";
echo "</tr>";

$desc = "Посещение тренажерного зала";
$stop = "0000-00-00 00:00:00";

mysqli_set_charset($link, "utf8");

$res = db_execute($link, "DELETE FROM gym_schedule WHERE USERID = ? AND DATE_TRAIN < CURDATE()", 'i', array($userID));
$merr = mysqli_error($link);

if ( !$res ) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 

$query = db_query(
    $link,
    "SELECT USERID, START_DT FROM ADD_TIME WHERE DESCRIPTION = ? AND STOP_DT = ?",
    'ss',
    array($desc, $stop)
);
if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}
$res = mysqli_num_rows($query);

if ($res == 0) {
    echo "<tr class=\"sport-empty-row\">";
    echo "<td colspan=\"2\" class=\"sport-empty-cell\"><h5>Тренажерный зал пуст</h5></td>";
    echo "</tr>";
}
else {
    while($row = mysqli_fetch_assoc($query)) {
        $ta_id = $row["USERID"];
        $ta_start_date = $row["START_DT"];
        $start_training = strtotime($ta_start_date);
        $time = date('H:i', $start_training);

        $query2 = db_query(
            $link,
            "SELECT firstname, lastname, surname FROM employees WHERE id = ?",
            'i',
            array((int)$ta_id)
        );
        if (!$query2) {
            echo database_error_message($link, __FILE__ . ':' . __LINE__);
            exit;
        }
        $row2 = mysqli_fetch_assoc($query2);
        if (!$row2) {
            continue;
        }
    
        $firstname = $row2["firstname"];
        $lastname = $row2["lastname"];
        $surname = $row2["surname"];
    
        echo "<tr class=\"sport-current-row\">";
        echo "<td class=\"add_time_sport sport-name-cell\"><h2 class=\"full_name sport\">" . html_escape($surname . " " . $firstname . " " . $lastname) . "</h2></td>";
        echo "<td class=\"add_time_sport sport-time-cell\"><h2 class=\"sport\">" . html_escape($time) . "</h2></td>";
        echo "</tr>";
    }
}
echo "</table><br><br>";

mysqli_set_charset($link, "utf8");

$search = db_query($link, "SELECT * FROM gym_schedule WHERE USERID = ?", 'i', array($userID));
if (!$search) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}
$res = mysqli_num_rows($search);
$merr=mysqli_error($link);

echo "<h5 class=\"big\"> Планируемые тренировки </h5>";

echo "<div id=\"training_button\">";
echo "<div id=\"planning\">";
echo "<button id=\"signUp\" onclick=\"add_training_time();\">Запланировать</button><br>";
echo "</div>";

if ($res === 0) {
    echo "<div id=\"delete_training\">";
    echo "<button id=\"disabled_btn\">Удалить запись</button><br>";
    echo "</div>";
}
else {
    echo "<div id=\"delete_training\">";
    echo "<button id=\"del_btn\" onclick=\"delete_training_schedule();\">Удалить запись</button><br>";
    echo "</div>";  
}
echo "</div>";

echo "<table id=\"schedule_training\" class=\"sport-table\">";
echo "<tr class=\"sport-table-head sport-table-head-short\">";
echo "<td class=\"add_time_sport sport-schedule-name-cell\" rowspan=\"2\">";
echo "<div class=\"person\">"."<h5 class=\"data_train\">Сотрудник</h5>"."</div>";
echo "</td>";
echo "<td class=\"add_time_sport sport-schedule-cell\" colspan=\"2\">";
echo "<div class=\"training\">"."<h5 class=\"data_train\">График</h5>"."</div>";
echo "</td>";
echo "</tr>";
echo "<tr class=\"sport-table-head sport-table-head-short\">";
echo "<td class=\"sport-schedule-cell\">";
echo "<div>"."<h5 class=\"data_train\">Дата</h5>"."</div>";
echo "</td>";
echo "<td class=\"sport-schedule-cell\">";
echo "<div>"."<h5 class=\"data_train\">Время</h5>"."</div>";
echo "</td>";
echo "</tr>";

mysqli_set_charset($link, "utf8");

$query3 = db_query($link, "SELECT *, GROUP_CONCAT(DATE_FORMAT(DATE_TRAIN, '%d %m') ORDER BY DATE_TRAIN ASC SEPARATOR ' ') AS DATE_TIME, GROUP_CONCAT(CONCAT(TIME_FORMAT(START_TIME, '%H:%i'), '-', TIME_FORMAT(STOP_TIME, '%H:%i')) ORDER BY DATE_TRAIN ASC SEPARATOR ' ') AS SCHEDULE FROM gym_schedule WHERE DATE_TRAIN >= DATE_FORMAT(NOW(), '%Y-%m-%d') GROUP BY USERID ORDER BY DATE_TRAIN ASC");
if (!$query3) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}

$res1 = mysqli_num_rows($query3);

if ($res1 === 0) {
    echo "<tr class=\"sport-empty-row\">";
    echo "<td colspan=\"3\" class=\"sport-empty-cell\"><h5>Записи отсутствуют</h5></td>";
    echo "</tr>";
}
else {
    while ($row3 = mysqli_fetch_assoc($query3)){
        $user_id = $row3["USERID"];
        $timeTrain = $row3["SCHEDULE"];
        $dateTrain = $row3["DATE_TIME"];

        $time = wordwrap($timeTrain, 10, "<br />");
        $date = wordwrap($dateTrain, 5, "<br />");

        $query4 = db_query(
            $link,
            "SELECT firstname, lastname, surname FROM employees WHERE id = ?",
            'i',
            array((int)$user_id)
        );
        if (!$query4) {
            echo database_error_message($link, __FILE__ . ':' . __LINE__);
            exit;
        }
        $row4 = mysqli_fetch_assoc($query4);
        if (!$row4) {
            continue;
        }
            
        $firstname4 = $row4["firstname"];
        $lastname4 = $row4["lastname"];
        $surname4 = $row4["surname"];

        $lenght = strlen($date);

        if ($lenght <= 5) {
            switch($date)  {
                case (strpos($date, ' 01') !== false):
                    $date = str_replace(' 01', ' января', $date);
                    break;
                case (strpos($date, ' 02') !== false):
                    $date = str_replace(' 02', ' февраля', $date);
                    break;
                case (strpos($date, ' 03') !== false):
                    $date = str_replace(' 03', ' марта', $date);
                    break;
                case (strpos($date, ' 04') !== false):
                    $date = str_replace(' 04', ' апреля', $date);
                    break; 
                case (strpos($date, ' 05') !== false):
                    $date = str_replace(' 05', ' мая', $date);
                    break;
                case (strpos($date, ' 06') !== false):
                    $date = str_replace(' 06', ' июня', $date);
                    break;
                case (strpos($date, ' 07') !== false):
                    $date = str_replace(' 07', ' июля', $date);
                    break;
                case (strpos($date, ' 08') !== false):
                    $date = str_replace(' 08', ' августа', $date);
                    break;
                case (strpos($date, ' 09') !== false):
                    $date = str_replace(' 09', ' сентября', $date);
                    break;
                case (strpos($date, ' 10') !== false):
                    $date = str_replace(' 10', ' октября', $date);
                    break;
                case (strpos($date, ' 11') !== false):
                    $date = str_replace(' 11', ' ноября', $date);
                    break;
                case (strpos($date, ' 12') !== false):
                    $date = str_replace(' 12', ' декабря', $date);
                    break;               
                default:
                    echo "ошибка";
                    break;
            }
            $output_date = '<h5>' . $date . '</h5>';
        }
        else {
            $str = "<h5>$date</h5>";
            if (strpos($str, '<br />') !== false) {
                $parts = explode('<br />', $str);
                foreach ($parts as &$part) {
                    switch ($part) {
                        case (strpos($part, ' 01') !== false):
                            $part = str_replace(' 01', ' января ', $part);
                            break;
                        case (strpos($part, ' 02') !== false):
                            $part = str_replace(' 02', ' февраля ', $part);
                            break;
                        case (strpos($part, ' 03') !== false):
                            $part = str_replace(' 03', ' марта ', $part);
                            break;
                        case (strpos($part, ' 04') !== false):
                            $part = str_replace(' 04', ' апреля ', $part);
                            break; 
                        case (strpos($part, ' 05') !== false):
                            $part = str_replace(' 05', ' мая ', $part);
                            break;
                        case (strpos($part, ' 06') !== false):
                            $part = str_replace(' 06', ' июня ', $part);
                            break;
                        case (strpos($part, ' 07') !== false):
                            $part = str_replace(' 07', ' июля ', $part);
                            break;
                        case (strpos($part, ' 08') !== false):
                            $part = str_replace(' 08', ' августа ', $part);
                            break;
                        case (strpos($part, ' 09') !== false):
                            $part = str_replace(' 09', ' сентября ', $part);
                            break;
                        case (strpos($part, ' 10') !== false):
                            $part = str_replace(' 10', ' октября ', $part);
                            break;
                        case (strpos($part, ' 11') !== false):
                            $part = str_replace(' 11', ' ноября ', $part);
                            break;
                        case (strpos($part, ' 12') !== false):
                            $part = str_replace(' 12', ' декабря ', $part);
                            break;               
                        default:
                            echo "ошибка";
                            break;
                    }
                }
                $output_date = '<h5>' . implode('<br>', $parts) . '</h5>';
            }
        }
        echo "<tr class=\"sport-schedule-row\">";
        echo "<td class=\"add_time_sport sport-schedule-name-cell\"><h5>" . html_escape($surname4 . " " . $firstname4 . " " . $lastname4) . "</h5></td>";
        echo "<td class=\"add_time_sport sport-schedule-date-cell\">$output_date</td>";
        echo "<td class=\"add_time_sport sport-schedule-time-cell\"><h5>$time</h5></td>";
        echo "</tr>";
    }
}
echo "</table>";
?>
