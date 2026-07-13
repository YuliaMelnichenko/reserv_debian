<?php

const WORKDAY_ACTION_ARRIVE = 'arrive';
const WORKDAY_ACTION_START_LUNCH = 'start_lunch';
const WORKDAY_ACTION_FINISH_LUNCH = 'finish_lunch';
const WORKDAY_ACTION_LEAVE = 'leave';
const WORKDAY_ACTION_UNDO_LEAVE = 'undo_leave';
const WORKDAY_ACTION_UNDO_FINISH_LUNCH = 'undo_finish_lunch';
const WORKDAY_ACTION_UNDO_START_LUNCH = 'undo_start_lunch';
const WORKDAY_ACTION_UNDO_ARRIVE = 'undo_arrive';
const WORKDAY_ACTION_NOOP = 'noop';

function get_workday_transition($currentState, $next)
{
    $currentState = (int)$currentState;
    $next = (int)$next;

    $forwardTransitions = array(
        1 => array('to' => 2, 'action' => WORKDAY_ACTION_ARRIVE),
        2 => array('to' => 3, 'action' => WORKDAY_ACTION_START_LUNCH),
        3 => array('to' => 4, 'action' => WORKDAY_ACTION_FINISH_LUNCH),
        4 => array('to' => 0, 'action' => WORKDAY_ACTION_LEAVE),
    );

    $backwardTransitions = array(
        0 => array('to' => 4, 'action' => WORKDAY_ACTION_UNDO_LEAVE),
        1 => array('to' => 1, 'action' => WORKDAY_ACTION_NOOP),
        2 => array('to' => 1, 'action' => WORKDAY_ACTION_UNDO_ARRIVE),
        3 => array('to' => 2, 'action' => WORKDAY_ACTION_UNDO_START_LUNCH),
        4 => array('to' => 3, 'action' => WORKDAY_ACTION_UNDO_FINISH_LUNCH),
    );

    if ($next === 1 && isset($forwardTransitions[$currentState])) {
        return array(
            'from' => $currentState,
            'to' => $forwardTransitions[$currentState]['to'],
            'action' => $forwardTransitions[$currentState]['action'],
        );
    }

    if ($next === 0 && isset($backwardTransitions[$currentState])) {
        return array(
            'from' => $currentState,
            'to' => $backwardTransitions[$currentState]['to'],
            'action' => $backwardTransitions[$currentState]['action'],
        );
    }

    return null;
}
