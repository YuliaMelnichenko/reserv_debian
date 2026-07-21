<?php

require_once __DIR__ . '/../inc/database.php';

return function () {
    $commits = 0;
    $rollbacks = 0;
    $transaction = new DatabaseTransaction(
        function () use (&$commits) {
            $commits++;
            return true;
        },
        function () use (&$rollbacks) {
            $rollbacks++;
            return true;
        }
    );

    test_assert_same(true, $transaction->isActive(), 'A new transaction must be active');
    test_assert_same(true, $transaction->commit(), 'A successful transaction must commit');
    test_assert_same(1, $commits, 'Commit callback must run once');
    test_assert_same(0, $rollbacks, 'Successful commit must not roll back');
    test_assert_same(false, $transaction->isActive(), 'Committed transaction must become inactive');

    $transaction = new DatabaseTransaction(
        function () {
            return false;
        },
        function () use (&$rollbacks) {
            $rollbacks++;
            return true;
        }
    );
    test_assert_same(false, $transaction->commit(), 'Failed commit must be reported');
    test_assert_same(1, $rollbacks, 'Failed commit must trigger rollback');

    $transaction = new DatabaseTransaction(
        function () {
            return true;
        },
        function () use (&$rollbacks) {
            $rollbacks++;
            return true;
        }
    );
    unset($transaction);
    test_assert_same(2, $rollbacks, 'An abandoned transaction must roll back on destruction');
};
