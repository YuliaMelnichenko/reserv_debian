<?php

function staffLeavesJsonResponse($payload)
{
    while (ob_get_level() > 1) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function getStaffLeavesArchiveRequest($query)
{
    $employeeId = (int)($query['employee_id'] ?? 0);
    $event = trim((string)($query['event'] ?? ''));
    $periodType = (int)($query['period_type'] ?? 0);

    if ($event !== '') {
        $event = normalizeStaffLeaveEvent($event);
    }

    list($filterStartDate, $filterStopDate) = getArchivePeriodDates(
        $periodType,
        $query['start_date'] ?? '',
        $query['stop_date'] ?? ''
    );

    return array(
        'employee_id' => $employeeId,
        'event' => $event,
        'period_type' => $periodType,
        'start_date' => $filterStartDate,
        'stop_date' => $filterStopDate,
    );
}

function handleStaffLeavesRequest($link, $server, $query, $post)
{
    $action = (string)($query['action'] ?? '');

    try {
        if ($action === 'get') {
            $id = (int)($query['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Некорректный ID записи');
            }

            $record = fetchStaffLeaveById($link, $id);
            staffLeavesJsonResponse($record === null
                ? array('status' => 'error', 'message' => 'Запись не найдена')
                : array('status' => 'success', 'record' => $record));
            return true;
        }

        if ($action === 'load') {
            $event = normalizeStaffLeaveEvent($query['type'] ?? 'Отпуск');
            staffLeavesJsonResponse(fetchActiveStaffLeaves($link, $event));
            return true;
        }

        if ($action === 'archive' || $action === 'archive_excel_preview' || $action === 'archive_excel_export') {
            $filter = getStaffLeavesArchiveRequest($query);
            $limit = $action === 'archive_excel_preview' ? 50 : 0;
            $rows = fetchStaffLeavesArchiveRows(
                $link,
                $filter['employee_id'],
                $filter['event'],
                $filter['start_date'],
                $filter['stop_date'],
                $limit
            );

            if ($action === 'archive') {
                staffLeavesJsonResponse($rows);
                return true;
            }

            $periodTitle = getArchivePeriodTitle($filter['period_type'], $filter['start_date'], $filter['stop_date']);
            $employeeTitle = getArchiveEmployeeTitle($link, $filter['employee_id']);
            $eventTitle = getArchiveEventTitle($filter['event']);

            if ($action === 'archive_excel_preview') {
                staffLeavesJsonResponse(array(
                    'status' => 'success',
                    'filters' => array(
                        'period' => $periodTitle,
                        'employee' => $employeeTitle,
                        'event' => $eventTitle,
                    ),
                    'rows' => $rows,
                    'preview_limit' => 50,
                ));
                return true;
            }

            $exportTime = trim((string)($query['export_time'] ?? date('d.m.Y H:i:s')));
            sendStaffLeavesArchiveXlsx($rows, $periodTitle, $employeeTitle, $eventTitle, $exportTime);
            return true;
        }

        if (($server['REQUEST_METHOD'] ?? '') !== 'POST') {
            return false;
        }

        $postAction = (string)($post['action'] ?? '');

        if ($postAction === 'add') {
            createStaffLeave(
                $link,
                $post['employee_id'] ?? 0,
                $post['start_date'] ?? '',
                $post['stop_date'] ?? '',
                $post['event'] ?? ''
            );
            staffLeavesJsonResponse(array('status' => 'success'));
            return true;
        }

        if ($postAction === 'update') {
            updateStaffLeave(
                $link,
                $post['record_id'] ?? 0,
                $post['start_date'] ?? '',
                $post['stop_date'] ?? '',
                $post['event'] ?? ''
            );
            staffLeavesJsonResponse(array('status' => 'success'));
            return true;
        }

        if ($postAction === 'delete') {
            deleteStaffLeave($link, $post['record_id'] ?? 0);
            staffLeavesJsonResponse(array('status' => 'success'));
            return true;
        }

        return false;
    }
    catch (Throwable $e) {
        error_log('[TORI] Staff leaves: ' . $e->getMessage());

        if ($action === 'archive_excel_export') {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: text/plain; charset=utf-8');
            echo application_error_message('Staff leave XLSX export', $e->getMessage());
        }
        else {
            staffLeavesJsonResponse(array('status' => 'error', 'message' => $e->getMessage()));
        }

        return true;
    }
}
