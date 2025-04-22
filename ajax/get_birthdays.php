<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include  "/var/www/tori/php_tori/connect.php";
include  "/var/www/tori/lib/Morphos-master/src/Names/NameCases.php";
use function morphos\Russian\getNameCases;
use function morphos\Russian\getSurnameCases;
use function morphos\Russian\getMiddleNameCases;

$userID = $_SESSION['ss_id'] ?? 0;

if (!$userID) {
    exit;
}

mysqli_set_charset($link, "utf8");

$today = date('-m-d');

$query_self = mysqli_query($link, "SELECT id, firstname, lastname, surname, birthday FROM employees WHERE id = '$userID' AND DATE_FORMAT(birthday, '-%m-%d') = '$today'");

$query_all = mysqli_query($link, "SELECT id, firstname, lastname, surname, birthday FROM employees WHERE DATE_FORMAT(birthday, '-%m-%d') = '$today'");

if (!$query_self) {
    echo 'Error query: ' . mysqli_error($link);
    exit;
}

if (!$query_all) {
    echo 'Error query: ' . mysqli_error($link);
    exit;
}

if ($userID == 148) {
    // if (mysqli_num_rows($query_self) > 0) {
    //     echo "<div id=\"hb_window\">
    //          <div id=\"hb\">
    //              <strong> С днем рождения!</strong>
    //          </div>
    //          <div id =\"close_window_sport_pause\">
    //              <br><button id=\"sport_pause_btn\" onclick=\"close_sport_pause();\" style=\"cursor:pointer\">Спасибо</button>
    //          </div>
    //      </div>";
    // }


    if (mysqli_num_rows($query_all) > 0) {
        echo '<div id="hb_block">
                <div id="ul_hb_block">
                    <h5 class="activ_text">Сегодня день рождения у:</h5>
                    <ul style="width: 240px">';
                        while ($row = mysqli_fetch_assoc($query_all)) {
                            $surnameRod = \morphos\Russian\LastNamesInflection($row['surname'])['genative'];
                            

                            echo '<li style="width: 240px">' . htmlspecialchars($row['surname']) . ' ' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</li>';
                        }
        echo '      </ul>
                </div>
                <img onclick="close_sport_pause();" src="img/closeSmall.png" style="cursor:pointer; width: 14px; height: 14px">
              </div>';
    }
}
else {
    echo "";
}

?>
