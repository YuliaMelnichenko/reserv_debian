<?php

require_once __DIR__ . '/../inc/calendar.php';

return function () {
    test_assert_same('1', GetWeekDay('2026-07-20'), 'Monday must use the legacy weekday number');
    test_assert_same(7, GetWeekDayD('2026-07-19'), 'Sunday must be the seventh ISO weekday');
    test_assert_same(0, isWeekEnd('2026-07-20'), 'Monday must be a workday');
    test_assert_same(1, isWeekEnd('2026-07-19'), 'Sunday must be a weekend');
    test_assert_same('Понедельник', GetWeekDayNameD('2026-07-20'), 'The weekday name must be localized');
    test_assert_same('Июль', GetMonthNameByDate('2026-07-20'), 'The month name must be localized');

    test_assert_same(1, is_first_week_day('2026-07-20'), 'Monday must open a week');
    test_assert_same(1, is_first_month_day('2026-07-01'), 'The first date must open a month');
    test_assert_same(1, is_first_quarter_day('2026-07-01'), 'July 1 must open the third quarter');
    test_assert_same(0, is_first_quarter_day('2026-07-02'), 'Only the first date may open a quarter');
    test_assert_same(1, is_first_year_day('2026-01-01'), 'January 1 must open a year');

    test_assert_same(
        array('2026-04-01', '2026-05-15', '2026-05-16'),
        get_current_quarter_date_range(false, '2026-05-15 12:00:00'),
        'The current-quarter range must include the reference day'
    );
    test_assert_same(
        array('2026-04-01', '2026-05-14', '2026-05-15'),
        get_current_quarter_date_range(true, '2026-05-15 12:00:00'),
        'The closed-quarter range must stop at yesterday'
    );

    test_assert_same('2024-02-01', GetFirstMonthDay('2024-02-29'), 'The first leap-month date must be correct');
    test_assert_same('2024-02-29', GetLastMonthDay('2024-02-10'), 'The last leap-month date must be correct');
    test_assert_same('2026-04-01', GetFirstQuarterDayEx('2026-07-01'), 'A completed quarter must start three months earlier');
    test_assert_same('III', GetQuarterRomNumByDate('2026-07-20'), 'July must belong to the third quarter');
    test_assert_same('01.04.2026 - 30.06.2026', format_date_range_label('2026-04-01', '2026-06-30'), 'Date ranges must be readable');
};
