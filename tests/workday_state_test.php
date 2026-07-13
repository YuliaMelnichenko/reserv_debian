<?php

require_once __DIR__ . '/../inc/workday_state.php';

return function () {
    $cases = array(
        array(1, 1, 2, WORKDAY_ACTION_ARRIVE),
        array(2, 1, 3, WORKDAY_ACTION_START_LUNCH),
        array(3, 1, 4, WORKDAY_ACTION_FINISH_LUNCH),
        array(4, 1, 0, WORKDAY_ACTION_LEAVE),
        array(0, 0, 4, WORKDAY_ACTION_UNDO_LEAVE),
        array(1, 0, 1, WORKDAY_ACTION_NOOP),
        array(2, 0, 1, WORKDAY_ACTION_UNDO_ARRIVE),
        array(3, 0, 2, WORKDAY_ACTION_UNDO_START_LUNCH),
        array(4, 0, 3, WORKDAY_ACTION_UNDO_FINISH_LUNCH),
    );

    foreach ($cases as $case) {
        list($from, $next, $expectedState, $expectedAction) = $case;
        $transition = get_workday_transition($from, $next);

        test_assert_true($transition !== null, "Transition $from/$next must exist");
        test_assert_same($from, $transition['from'], "Unexpected source state for $from/$next");
        test_assert_same($expectedState, $transition['to'], "Unexpected target state for $from/$next");
        test_assert_same($expectedAction, $transition['action'], "Unexpected action for $from/$next");
    }

    test_assert_same(null, get_workday_transition(0, 1), 'Finished day cannot move forward');
    test_assert_same(null, get_workday_transition(5, 1), 'Unknown state must be rejected');
    test_assert_same(null, get_workday_transition(1, 2), 'Unknown direction must be rejected');
};
