<?php

require_once __DIR__ . '/journal_period.php';
require_once __DIR__ . '/request.php';

function load_supervisor_journal_period($sessionPrefix, $referenceDate, $query, $defaultMode = 4)
{
    $error = '';

    if (request_has_scalar_value($query, 'period_mode')) {
        $period = get_journal_period(
            request_int_value($query, 'period_mode'),
            request_date_value($query, 'start_date'),
            request_date_value($query, 'stop_date'),
            $referenceDate
        );

        if ($period === null) {
            $error = 'Укажите корректный период продолжительностью не более одного года.';
        } else {
            $_SESSION[$sessionPrefix . '_mode'] = $period['mode'];
            $_SESSION[$sessionPrefix . '_start_date'] = $period['start_date'];
            $_SESSION[$sessionPrefix . '_stop_date'] = $period['stop_date'];
        }
    }

    return array(
        'period' => get_journal_period_from_session($sessionPrefix, $referenceDate, $defaultMode),
        'error' => $error,
    );
}

function render_supervisor_journal_period_filter($page, $prefix, $period, $includeExtended = false)
{
    $options = array(
        1 => 'С начала недели',
        2 => 'С начала месяца',
        3 => 'За предыдущий месяц',
        4 => 'С начала квартала',
        5 => 'За предыдущий квартал',
    );

    if ($includeExtended) {
        $options[6] = 'С начала предыдущего квартала';
    }

    $options[7] = 'Задать вручную';
    $safePage = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
    $safePrefix = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8');
    $content = '<form class="journal-period-filter" data-journal-period-filter="' . $safePrefix
        . '" method="get" action="' . $safePage
        . '" onsubmit="return validate_journal_period_filter(\'' . $safePrefix . '\');">';
    $content .= '<label class="journal-period-filter-label" for="' . $safePrefix . '_type">Период:</label>';
    $content .= '<select id="' . $safePrefix . '_type" name="period_mode" class="flat journal-period-filter-select"'
        . ' onchange="toggle_journal_period_filter(\'' . $safePrefix . '\');">';

    foreach ($options as $mode => $title) {
        $selected = $period['mode'] === $mode ? ' selected' : '';
        $content .= '<option value="' . $mode . '"' . $selected . '>'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    $content .= '</select>';
    $manual = $period['mode'] === 7;
    $content .= '<span id="' . $safePrefix . '_manual" class="journal-period-filter-manual"'
        . ($manual ? '' : ' hidden style="display:none;"') . '>';
    $content .= '<input id="' . $safePrefix . '_start_date" name="start_date" type="date" value="'
        . htmlspecialchars($period['start_date'], ENT_QUOTES, 'UTF-8') . '"'
        . ($manual ? '' : ' disabled') . '>';
    $content .= ' - <input id="' . $safePrefix . '_stop_date" name="stop_date" type="date" value="'
        . htmlspecialchars($period['stop_date'], ENT_QUOTES, 'UTF-8') . '"'
        . ($manual ? '' : ' disabled') . '>';
    $content .= '</span>';
    $content .= '<button type="submit" class="button_style journal-period-filter-button">Показать</button></form>';

    return $content;
}
