<?php

function test_assert_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function test_assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . '; expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}
