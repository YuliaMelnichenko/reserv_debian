<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = (int)$_SESSION['ss_id'];

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/gym_schedule.php";

mysqli_set_charset($link, "utf8");

if (!delete_past_gym_schedule($link, $userID)) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

$activeVisitors = get_active_gym_visitors($link);

if ($activeVisitors === false) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

$userHasSchedule = user_has_gym_schedule($link, $userID);

if ($userHasSchedule === null) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

$upcomingSchedule = get_upcoming_gym_schedule($link);

if ($upcomingSchedule === false) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

echo "<h5 class=\"big\"> Текущая загруженность тренажерного зала </h5>";

echo "<table id=\"add_time_sport_table\" border=1 style=\"margin-top: 10px\">";
echo "<tr bgcolor=\"#DDDDDD\" bordercolor=\"#888888\" height=\"20px\">";
echo "<td class=\"add_time_sport\" valign=\"middle\" align=\"center\" width=490>";
echo "<div class=\"person\"><h5 class=\"data_train\">Сотрудник</h5></div>";
echo "</td>";
echo "<td class=\"add_time_sport\" valign=\"middle\" align=\"center\" width=200>";
echo "<div class=\"training\"><h5 class=\"data_train\">Время прихода</h5></div>";
echo "</td>";
echo "</tr>";

if (count($activeVisitors) === 0) {
    echo "<tr bordercolor=\"#888888\" height=\"40px\">";
    echo "<td colspan=3 align=\"center\"><h5>Тренажерный зал пуст</h5></td>";
    echo "</tr>";
}
else {
    foreach ($activeVisitors as $visitor) {
        echo "<tr bgcolor=\"#ddffff\" bordercolor=\"#888888\" height=\"60px\">";
        echo "<td width=490 class=\"add_time_sport\" valign=\"middle\" align=\"center\"><h2 class=\"full_name sport\">" . html_escape($visitor['full_name']) . "</h2></td>";
        echo "<td width=200 class=\"add_time_sport\" valign=\"middle\" align=\"center\"><h2 class=\"sport\">" . html_escape($visitor['start_time']) . "</h2></td>";
        echo "</tr>";
    }
}

echo "</table><br><br>";
echo "<h5 class=\"big\"> Планируемые тренировки </h5>";

echo "<div id=\"training_button\">";
echo "<div id=\"planning\">";
echo "<button id=\"signUp\" onclick=\"add_training_time();\">Запланировать</button><br>";
echo "</div>";

if ($userHasSchedule) {
    echo "<div id=\"delete_training\">";
    echo "<button id=\"del_btn\" onclick=\"delete_training_schedule();\">Удалить запись</button><br>";
    echo "</div>";
}
else {
    echo "<div id=\"delete_training\">";
    echo "<button id=\"disabled_btn\">Удалить запись</button><br>";
    echo "</div>";
}

echo "</div>";

echo "<table id=\"schedule_training\" border=1>";
echo "<tr bgcolor=\"#DDDDDD\" bordercolor=\"#888888\" height=\"20px\">";
echo "<td class=\"add_time_sport\" valign=\"middle\" align=\"center\" rowspan=2>";
echo "<div class=\"person\"><h5 class=\"data_train\">Сотрудник</h5></div>";
echo "</td>";
echo "<td class=\"add_time_sport\" valign=\"middle\" align=\"center\" colspan=2>";
echo "<div class=\"training\"><h5 class=\"data_train\">График</h5></div>";
echo "</td>";
echo "</tr>";
echo "<tr bgcolor=\"#DDDDDD\" bordercolor=\"#888888\" height=\"20px\">";
echo "<td align=\"center\">";
echo "<div><h5 class=\"data_train\">Дата</h5></div>";
echo "</td>";
echo "<td align=\"center\">";
echo "<div><h5 class=\"data_train\">Время</h5></div>";
echo "</td>";
echo "</tr>";

if (count($upcomingSchedule) === 0) {
    echo "<tr bordercolor=\"#888888\" height=\"40px\">";
    echo "<td colspan=3 align=\"center\"><h5>Записи отсутствуют</h5></td>";
    echo "</tr>";
}
else {
    foreach ($upcomingSchedule as $scheduleRow) {
        $dateLines = array_map('html_escape', $scheduleRow['dates']);
        $timeLines = array_map('html_escape', $scheduleRow['times']);

        echo "<tr bordercolor=\"#888888\" height=\"40px\">";
        echo "<td width=450 class=\"add_time_sport\" valign=\"middle\" align=\"left\" style=\"padding-left:5px\"><h5>" . html_escape($scheduleRow['full_name']) . "</h5></td>";
        echo "<td width=100 class=\"add_time_sport\" valign=\"middle\" align=\"left\" style=\"padding-left:35px\"><h5>" . implode('<br>', $dateLines) . "</h5></td>";
        echo "<td width=100 class=\"add_time_sport\" valign=\"middle\" align=\"center\"><h5>" . implode('<br>', $timeLines) . "</h5></td>";
        echo "</tr>";
    }
}

echo "</table>";
?>
