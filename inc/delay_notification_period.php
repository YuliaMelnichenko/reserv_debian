<?php

require_once __DIR__ . '/journal_period.php';

function get_delay_notification_period_from_session($referenceDate = null)
{
    return get_journal_period_from_session('delay_notification_period', $referenceDate);
}

function render_delay_notification_period_filter($selectedPeriod)
{
    $periodOptions = array(
        1 => 'С начала недели',
        2 => 'С начала месяца',
        3 => 'За предыдущий месяц',
        4 => 'С начала квартала',
        5 => 'За предыдущий квартал',
        7 => 'Задать вручную',
    );

    $html = '<div class="journal-period-filter">';
    $html .= '<span class="journal-period-filter-label">Период:</span>';
    $html .= '<select id="delay_notification_period_type" class="flat journal-period-filter-select" onchange="toggle_delay_notification_manual_period();">';

    foreach ($periodOptions as $periodMode => $periodTitle) {
        $selected = (int)$selectedPeriod['mode'] === $periodMode ? ' selected' : '';
        $html .= '<option value="' . $periodMode . '"' . $selected . '>' . html_escape($periodTitle) . '</option>';
    }

    $html .= '</select>';
    $manualDisplay = (int)$selectedPeriod['mode'] === 7 ? '' : ' style="display:none;"';
    $html .= '<span id="delay_notification_manual_period" class="journal-period-filter-manual"' . $manualDisplay . '>';
    $html .= '<input id="delay_notification_start_date" type="date" value="' . html_escape($selectedPeriod['start_date']) . '">';
    $html .= ' - <input id="delay_notification_stop_date" type="date" value="' . html_escape($selectedPeriod['stop_date']) . '">';
    $html .= '</span>';
    $html .= '<button class="button_style journal-period-filter-button" onclick="set_delay_notification_period();">Показать</button>';
    $html .= '</div>';

    return $html;
}
