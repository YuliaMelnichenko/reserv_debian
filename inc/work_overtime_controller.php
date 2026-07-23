<?php

function handle_work_overtime_request($link)
{
    // === AJAX: список сотрудников с количеством переработок >= hours (текущий квартал) ===
    if (isset($_GET['action']) && $_GET['action'] === 'load') {
        ajax_json_headers();

        try {
            $hours = normalizeOvertimeThreshold($_GET['hours'] ?? null);
            $period = $_GET['period'] ?? 'quarter';
            list($qstart, $qend) = getOvertimePeriodBounds(
                $period,
                $_GET['start'] ?? '',
                $_GET['end'] ?? ''
            );

            list($numbersSql, $addWorkDateSql, $addRangeSql, $addDurationSql) = overtimeAddTimeSqlParts();

            $sql = "
                SELECT
                    e.id AS emp_id,
                    CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS fio,
                    COUNT(t.work_date) AS overtime_days,
                    ROUND(SUM(t.total_hours) - (? * COUNT(t.work_date)), 2) AS overtime_hours
                FROM employees e
                LEFT JOIN (
                    SELECT d.user_id, d.work_date,
                        GREATEST(
                            0,
                            IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
                        ) AS total_hours
                    FROM (
                        SELECT user_id, DATE(in_dt) AS work_date
                        FROM visiting
                        WHERE in_dt >= ? AND in_dt < ?
                        AND in_dt IS NOT NULL
                        AND in_dt != '0000-00-00 00:00:00'
                        AND out_dt IS NOT NULL
                        AND out_dt != '0000-00-00 00:00:00'
                        AND out_dt > in_dt
                        GROUP BY user_id, DATE(in_dt)

                        UNION

                        SELECT a.USERID AS user_id, $addWorkDateSql AS work_date
                        FROM ADD_TIME a
                        JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                        WHERE $addRangeSql
                        AND a.REASON IN (1, 2, 3, 4, 5)
                        GROUP BY a.USERID, $addWorkDateSql
                    ) d
                    LEFT JOIN (
                        SELECT user_id, DATE(in_dt) AS work_date,
                            ROUND(SUM(
                                    TIME_TO_SEC(TIMEDIFF(out_dt, in_dt))
                                    - IF(
                                        eat_start_dt IS NULL
                                        OR eat_stop_dt IS NULL
                                        OR eat_start_dt = '0000-00-00 00:00:00'
                                        OR eat_stop_dt = '0000-00-00 00:00:00'
                                        OR eat_stop_dt <= eat_start_dt,
                                        0,
                                        TIME_TO_SEC(TIMEDIFF(eat_stop_dt, eat_start_dt))
                                    )
                                ) / 3600, 2) AS office_hours
                        FROM visiting
                        WHERE in_dt >= ? AND in_dt < ?
                        AND in_dt IS NOT NULL
                        AND in_dt != '0000-00-00 00:00:00'
                        AND out_dt IS NOT NULL
                        AND out_dt != '0000-00-00 00:00:00'
                        AND out_dt > in_dt
                        GROUP BY user_id, DATE(in_dt)
                    ) v ON d.user_id = v.user_id AND d.work_date = v.work_date
                    LEFT JOIN (
                        SELECT a.USERID AS user_id, $addWorkDateSql AS work_date,
                            ROUND(SUM($addDurationSql) / 3600, 2) AS outside_hours
                        FROM ADD_TIME a
                        JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                        WHERE $addRangeSql
                        AND a.REASON IN (1, 2, 3, 4, 5)
                        GROUP BY a.USERID, $addWorkDateSql
                    ) a ON d.user_id = a.user_id AND d.work_date = a.work_date
                    LEFT JOIN (
                        SELECT a.USERID AS user_id, $addWorkDateSql AS work_date,
                            ROUND(SUM($addDurationSql) / 3600, 2) AS pause_hours
                        FROM ADD_TIME a
                        JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                        WHERE $addRangeSql
                        AND a.REASON = -1
                        GROUP BY a.USERID, $addWorkDateSql
                    ) p ON d.user_id = p.user_id AND d.work_date = p.work_date
                    WHERE GREATEST(
                        0,
                        IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
                    ) >= ?
                ) AS t ON e.id = t.user_id
                WHERE t.work_date IS NOT NULL
                GROUP BY e.id
                ORDER BY overtime_days DESC, fio ASC
            ";

            $res = db_query(
                $link,
                $sql,
                'dssssssssssssssssd',
                array(
                    $hours,
                    $qstart, $qend,
                    $qend, $qstart, $qstart, $qend,
                    $qstart, $qend,
                    $qend, $qstart, $qstart, $qend,
                    $qend, $qstart, $qstart, $qend,
                    $hours,
                )
            );

            if (!$res) {
                throw new Exception('Ошибка выполнения запроса: ' . db_error($link));
            }

            $rows = [];

            foreach (db_fetch_all($res) as $row) {
                $rows[] = [
                    'id' => intval($row['emp_id']),
                    'fio' => $row['fio'],
                    'overtime_count' => intval($row['overtime_days']),
                    'overtime_hours' => floatval($row['overtime_hours'])
                ];
            }

            ajax_json_response(['status' => 'success', 'data' => $rows, 'quarter_start' => $qstart, 'quarter_end' => $qend]);
        } catch (Throwable $e) {
            ajax_json_application_error('Overtime list at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
        }
        exit;
    }

    // === AJAX: детали по сотруднику — записи (дата + часы) с переработкой >= hours (текущий квартал) ===
    if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
        ajax_json_headers();

        try {
            $empId = intval($_GET['id']);
            if ($empId <= 0) throw new Exception('Некорректный ID сотрудника');

            $hours = normalizeOvertimeThreshold($_GET['hours'] ?? null);
            $period = $_GET['period'] ?? 'quarter';
            list($qstart, $qend) = getOvertimePeriodBounds(
                $period,
                $_GET['start'] ?? '',
                $_GET['end'] ?? ''
            );

            list($numbersSql, $addWorkDateSql, $addRangeSql, $addDurationSql) = overtimeAddTimeSqlParts();

            $sql = "
                SELECT
                    d.work_date,
                    ROUND(
                        GREATEST(
                            0,
                            IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
                        ),
                        2
                    ) AS total_hours,
                    ROUND(IFNULL(v.office_hours, 0), 2) AS office_hours,
                    ROUND(IFNULL(a.outside_hours, 0), 2) AS outside_hours,
                    ROUND(IFNULL(p.pause_hours, 0), 2) AS pause_hours
                FROM (
                    SELECT DATE(in_dt) AS work_date
                    FROM visiting
                    WHERE user_id = ?
                    AND in_dt >= ? AND in_dt < ?
                    AND in_dt IS NOT NULL
                    AND in_dt != '0000-00-00 00:00:00'
                    GROUP BY DATE(in_dt)

                    UNION

                    SELECT $addWorkDateSql AS work_date
                    FROM ADD_TIME a
                    JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                    WHERE a.USERID = ?
                    AND $addRangeSql
                    AND a.REASON IN (1, 2, 3, 4, 5)
                    GROUP BY $addWorkDateSql
                ) d
                LEFT JOIN (
                    SELECT DATE(in_dt) AS work_date,
                        ROUND(SUM(
                            TIME_TO_SEC(TIMEDIFF(out_dt, in_dt))
                            - IF(
                                eat_start_dt IS NULL
                                OR eat_stop_dt IS NULL
                                OR eat_start_dt = '0000-00-00 00:00:00'
                                OR eat_stop_dt = '0000-00-00 00:00:00'
                                OR eat_stop_dt <= eat_start_dt,
                                0,
                                TIME_TO_SEC(TIMEDIFF(eat_stop_dt, eat_start_dt))
                            )
                        ) / 3600, 2) AS office_hours
                    FROM visiting
                    WHERE user_id = ?
                    AND in_dt >= ? AND in_dt < ?
                    AND in_dt IS NOT NULL
                    AND in_dt != '0000-00-00 00:00:00'
                    AND out_dt IS NOT NULL
                    AND out_dt != '0000-00-00 00:00:00'
                    AND out_dt > in_dt
                    GROUP BY DATE(in_dt)
                ) v ON d.work_date = v.work_date
                LEFT JOIN (
                    SELECT $addWorkDateSql AS work_date,
                        ROUND(SUM($addDurationSql) / 3600, 2) AS outside_hours
                    FROM ADD_TIME a
                    JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                    WHERE a.USERID = ?
                    AND $addRangeSql
                    AND a.REASON IN (1, 2, 3, 4, 5)
                    GROUP BY $addWorkDateSql
                ) a ON d.work_date = a.work_date
                LEFT JOIN (
                    SELECT $addWorkDateSql AS work_date,
                        ROUND(SUM($addDurationSql) / 3600, 2) AS pause_hours
                    FROM ADD_TIME a
                    JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                    WHERE a.USERID = ?
                    AND $addRangeSql
                    AND a.REASON = -1
                    GROUP BY $addWorkDateSql
                ) p ON d.work_date = p.work_date
                WHERE GREATEST(
                    0,
                    IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
                ) >= ?
                ORDER BY d.work_date DESC
            ";
            $res = db_query(
                $link,
                $sql,
                'ississssississssissssd',
                array(
                    $empId, $qstart, $qend,
                    $empId, $qend, $qstart, $qstart, $qend,
                    $empId, $qstart, $qend,
                    $empId, $qend, $qstart, $qstart, $qend,
                    $empId, $qend, $qstart, $qstart, $qend,
                    $hours,
                )
            );

            if (!$res) {
                throw new Exception('Ошибка выполнения запроса: ' . db_error($link));
            }

            $rows = [];
            foreach (db_fetch_all($res) as $row) {
                $rows[] = [
                    'date' => $row['work_date'],
                    'hours_total' => formatHours($row['total_hours']),
                    'office_hours' => formatHours($row['office_hours']),
                    'outside_hours' => formatHours($row['outside_hours']),
                    'pause_hours' => formatHours($row['pause_hours'])
                ];
            }

            ajax_json_response([
                'status' => 'success',
                'data' => $rows,
                'quarter_start' => $qstart,
                'quarter_end' => $qend
            ]);
        } catch (Throwable $e) {
            ajax_json_application_error('Overtime details at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
        }
        exit;
    }

    return false;
}
