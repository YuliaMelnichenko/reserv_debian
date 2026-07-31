<?php

require_once __DIR__ . '/request.php';

function staffLeavesJsonResponse($payload)
{
    while (ob_get_level() > 1) {
        ob_end_clean();
    }

    ajax_json_response($payload);
}

function getStaffLeavesArchiveRequest($query)
{
    $employeeId = request_int_value($query, 'employee_id');
    $event = request_trimmed_string_value($query, 'event');
    $periodType = request_int_value($query, 'period_type');

    if ($event !== '') {
        $event = normalizeStaffLeaveEvent($event);
    }

    list($filterStartDate, $filterStopDate) = getArchivePeriodDates(
        $periodType,
        request_string_value($query, 'start_date'),
        request_string_value($query, 'stop_date')
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
    $action = request_string_value($query, 'action');

    try {
        if ($action === 'get') {
            $id = request_int_value($query, 'id');
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
            $event = normalizeStaffLeaveEvent(request_string_value($query, 'type', 'Отпуск'));
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
                $limit,
                $action !== 'archive'
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

            $exportTime = request_trimmed_string_value($query, 'export_time', date('d.m.Y H:i:s'));
            sendStaffLeavesArchiveXlsx($rows, $periodTitle, $employeeTitle, $eventTitle, $exportTime);
            return true;
        }

        if (request_string_value($server, 'REQUEST_METHOD') !== 'POST') {
            return false;
        }

        $postAction = request_string_value($post, 'action');

        if ($postAction === 'add') {
            createStaffLeave(
                $link,
                request_int_value($post, 'employee_id'),
                request_string_value($post, 'start_date'),
                request_string_value($post, 'stop_date'),
                request_string_value($post, 'event')
            );
            staffLeavesJsonResponse(array('status' => 'success'));
            return true;
        }

        if ($postAction === 'update') {
            updateStaffLeave(
                $link,
                request_int_value($post, 'record_id'),
                request_string_value($post, 'start_date'),
                request_string_value($post, 'stop_date'),
                request_string_value($post, 'event')
            );
            staffLeavesJsonResponse(array('status' => 'success'));
            return true;
        }

        if ($postAction === 'delete') {
            deleteStaffLeave($link, request_int_value($post, 'record_id'));
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
            ajax_text_response(application_error_message('Staff leave XLSX export', $e->getMessage()), 500);
        }
        else {
            staffLeavesJsonResponse(array('status' => 'error', 'message' => $e->getMessage()));
        }

        return true;
    }
}
