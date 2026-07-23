<?php

require_once __DIR__ . '/date_range.php';
require_once __DIR__ . '/database.php';

function getEmployees($link)
{
    $employees = array();
    $result = db_query($link, 'SELECT id, firstname, surname FROM employees WHERE relevance = 1 ORDER BY surname');

    if (!$result) {
        return $employees;
    }

    while ($row = db_fetch_one($result)) {
        $employees[$row['id']] = $row['surname'] . ' ' . $row['firstname'];
    }

    return $employees;
}

function normalizeStaffLeaveRange($startDate, $stopDate)
{
    $start = normalize_date_value((string)$startDate);
    $stop = normalize_date_value((string)$stopDate);

    if ($start === null || $stop === null) {
        throw new InvalidArgumentException('Указана некорректная дата');
    }

    if ($stop < $start) {
        throw new InvalidArgumentException('Дата окончания меньше даты начала');
    }

    return array($start, $stop);
}

function normalizeStaffLeaveEvent($event)
{
    $event = trim((string)$event);
    $allowedEvents = array('Отпуск', 'Больничный', 'Командировка');

    if (!in_array($event, $allowedEvents, true)) {
        throw new InvalidArgumentException('Некорректный вид отсутствия');
    }

    return $event;
}

function getStaffLeaveDaysCount($startDate, $stopDate)
{
    list($start, $stop) = normalizeStaffLeaveRange($startDate, $stopDate);
    $startValue = new DateTimeImmutable($start);
    $stopValue = new DateTimeImmutable($stop);

    return (int)$startValue->diff($stopValue)->days + 1;
}

function getArchivePeriodDates($periodType, $startDateManual, $stopDateManual, $currentDate = null)
{
    $currDate = normalize_date_value($currentDate === null ? date('Y-m-d') : $currentDate);

    if ($currDate === null) {
        throw new InvalidArgumentException('Некорректная текущая дата');
    }

    if ((int)$periodType === 0) {
        return array('', '');
    }

    $current = new DateTimeImmutable($currDate);

    if ((int)$periodType === 1) {
        return array($current->modify('monday this week')->format('Y-m-d'), $currDate);
    }

    if ((int)$periodType === 2) {
        return array($current->modify('first day of this month')->format('Y-m-d'), $currDate);
    }

    if ((int)$periodType === 3) {
        $previousMonth = $current->modify('first day of previous month');
        return array($previousMonth->format('Y-m-01'), $previousMonth->format('Y-m-t'));
    }

    if ((int)$periodType === 4) {
        $startMonth = ((int)floor(((int)$current->format('n') - 1) / 3) * 3) + 1;
        $start = $current->setDate((int)$current->format('Y'), $startMonth, 1);
        return array($start->format('Y-m-d'), $currDate);
    }

    if ((int)$periodType === 5) {
        $currentQuarterStartMonth = ((int)floor(((int)$current->format('n') - 1) / 3) * 3) + 1;
        $currentQuarterStart = $current->setDate((int)$current->format('Y'), $currentQuarterStartMonth, 1);
        $previousQuarterStart = $currentQuarterStart->modify('-3 months');
        return array(
            $previousQuarterStart->format('Y-m-d'),
            $currentQuarterStart->modify('-1 day')->format('Y-m-d')
        );
    }

    if ((int)$periodType === 7) {
        return normalizeStaffLeaveRange($startDateManual, $stopDateManual);
    }

    return array('', '');
}

function formatArchiveDateRu($dateStr)
{
    $date = normalize_date_value((string)$dateStr);
    return $date === null ? (string)$dateStr : date('d.m.Y', strtotime($date));
}

function getArchivePeriodFilterName($periodType)
{
    switch ((int)$periodType) {
        case 1: return 'С начала недели';
        case 2: return 'С начала месяца';
        case 3: return 'За предыдущий месяц';
        case 4: return 'С начала квартала';
        case 5: return 'За предыдущий квартал';
        case 7: return 'Задать вручную';
        default: return 'Все даты';
    }
}

function getArchivePeriodTitle($periodType, $filterStartDate, $filterStopDate)
{
    $periodName = getArchivePeriodFilterName($periodType);

    if ($periodName === 'Все даты' || $filterStartDate === '' || $filterStopDate === '') {
        return $periodName;
    }

    return $periodName . ' (' . formatArchiveDateRu($filterStartDate) . ' - ' . formatArchiveDateRu($filterStopDate) . ')';
}

function getArchiveEventTitle($event)
{
    return $event === '' ? 'Все события' : (string)$event;
}

function getArchiveEmployeeTitle($link, $employeeId)
{
    if ((int)$employeeId <= 0) {
        return 'Все сотрудники';
    }

    $result = db_query(
        $link,
        'SELECT surname, firstname FROM employees WHERE id = ? LIMIT 1',
        'i',
        array((int)$employeeId)
    );

    if ($row = db_fetch_one($result)) {
        return $row['surname'] . ' ' . $row['firstname'];
    }

    return 'Выбранный сотрудник';
}

function buildStaffLeavesArchiveQuery($employeeId, $event, $filterStartDate, $filterStopDate, &$types, &$params)
{
    $where = array('stop_date < CURDATE()');
    $params = array();
    $types = '';

    if ((int)$employeeId > 0) {
        $where[] = 'user_id = ?';
        $params[] = (int)$employeeId;
        $types .= 'i';
    }

    if ($event !== '') {
        $where[] = 'event = ?';
        $params[] = $event;
        $types .= 's';
    }

    if ($filterStartDate !== '' && $filterStopDate !== '') {
        $where[] = 'start_date <= ? AND stop_date >= ?';
        $params[] = $filterStopDate;
        $params[] = $filterStartDate;
        $types .= 'ss';
    }

    return ' WHERE ' . implode(' AND ', $where);
}

function mapStaffLeaveRow($row)
{
    return array(
        'id' => (int)$row['id'],
        'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : 0,
        'fio' => $row['fio'],
        'name' => $row['fio'],
        'start_date' => $row['start_date'],
        'stop_date' => $row['stop_date'],
        'event' => $row['event'],
        'total_days' => getStaffLeaveDaysCount($row['start_date'], $row['stop_date']),
    );
}

function fetchStaffLeavesArchiveRows($link, $employeeId, $event, $filterStartDate, $filterStopDate, $limit)
{
    $types = '';
    $params = array();
    $whereSql = buildStaffLeavesArchiveQuery($employeeId, $event, $filterStartDate, $filterStopDate, $types, $params);
    $sql = 'SELECT id, user_id, fio, start_date, stop_date, event FROM staff_leaves '
        . $whereSql
        . ' ORDER BY fio ASC, start_date DESC, stop_date DESC';

    if ((int)$limit > 0) {
        $sql .= ' LIMIT ' . (int)$limit;
    }

    $result = db_query($link, $sql, $types, $params);
    if (!$result) {
        throw new RuntimeException(db_error($link));
    }

    $rows = array();
    while ($row = db_fetch_one($result)) {
        $rows[] = mapStaffLeaveRow($row);
    }

    return $rows;
}

function fetchActiveStaffLeaves($link, $event)
{
    $result = db_query(
        $link,
        'SELECT id, user_id, fio, start_date, stop_date, event '
            . 'FROM staff_leaves WHERE event = ? AND stop_date >= CURDATE() '
            . 'ORDER BY fio ASC, start_date ASC, stop_date ASC',
        's',
        array($event)
    );

    if (!$result) {
        throw new RuntimeException(db_error($link));
    }

    $rows = array();

    while ($row = db_fetch_one($result)) {
        $rows[] = mapStaffLeaveRow($row);
    }

    return $rows;
}

function fetchStaffLeaveById($link, $id)
{
    $result = db_query(
        $link,
        'SELECT id, start_date, stop_date, fio, event FROM staff_leaves WHERE id = ?',
        'i',
        array((int)$id)
    );

    if (!$result) {
        throw new RuntimeException(db_error($link));
    }

    return db_fetch_one($result);
}

function createStaffLeave($link, $userId, $startDate, $stopDate, $event)
{
    list($start, $stop) = normalizeStaffLeaveRange($startDate, $stopDate);
    $event = normalizeStaffLeaveEvent($event);
    $userId = (int)$userId;

    if ($userId <= 0) {
        throw new InvalidArgumentException('Сотрудник не выбран');
    }

    $result = db_query(
        $link,
        'SELECT surname, firstname FROM employees WHERE id = ? LIMIT 1',
        'i',
        array($userId)
    );
    $employee = db_fetch_one($result);

    if (!$employee) {
        throw new InvalidArgumentException('Сотрудник не найден');
    }

    $fio = trim($employee['surname'] . ' ' . $employee['firstname']);
    if (!db_execute(
        $link,
        'INSERT INTO staff_leaves (user_id, fio, start_date, stop_date, event) VALUES (?, ?, ?, ?, ?)',
        'issss',
        array($userId, $fio, $start, $stop, $event)
    )) {
        throw new RuntimeException(db_error($link));
    }
}

function updateStaffLeave($link, $id, $startDate, $stopDate, $event)
{
    list($start, $stop) = normalizeStaffLeaveRange($startDate, $stopDate);
    $event = normalizeStaffLeaveEvent($event);
    $id = (int)$id;

    if ($id <= 0) {
        throw new InvalidArgumentException('Некорректный ID записи');
    }

    if (!db_execute(
        $link,
        'UPDATE staff_leaves SET start_date = ?, stop_date = ?, event = ? WHERE id = ?',
        'sssi',
        array($start, $stop, $event, $id)
    )) {
        throw new RuntimeException(db_error($link));
    }
}

function deleteStaffLeave($link, $id)
{
    $id = (int)$id;
    if ($id <= 0) {
        throw new InvalidArgumentException('Некорректный ID записи');
    }

    if (!db_execute($link, 'DELETE FROM staff_leaves WHERE id = ?', 'i', array($id))) {
        throw new RuntimeException(db_error($link));
    }
}
