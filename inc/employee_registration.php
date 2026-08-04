<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/errors.php';

function validate_employee_registration_input($input)
{
    $login = trim((string)($input['r_login'] ?? ''));
    $password = (string)($input['r_passwd'] ?? '');
    $passwordRepeat = (string)($input['r_passwd_rep'] ?? '');
    $surname = (string)($input['r_surname'] ?? '');
    $firstName = (string)($input['r_first_name'] ?? '');
    $secondName = (string)($input['r_second_name'] ?? '');
    $errors = array();

    if (strlen($login) < 3 || strlen($login) > 30) {
        $errors[] = 'Логин должен быть не меньше 3-х символов и не больше 30';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $login)) {
        $errors[] = 'Логин может состоять только из букв английского алфавита и цифр';
    }

    if ($password !== $passwordRepeat) {
        $errors[] = 'Пароль и его повтор не совпадают';
    } elseif (strlen($password) < 3 || strlen($password) > 30) {
        $errors[] = 'Пароль должен быть не меньше 3-х символов и не больше 30';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $errors[] = 'Пароль может состоять только из букв английского алфавита и цифр';
    }

    if (strlen($surname) < 1 || strlen($surname) > 50) {
        $errors[] = 'Поле ФАМИЛИЯ должно быть не пустым и не больше 50 символов';
    }

    if (strlen($firstName) < 1 || strlen($firstName) > 50) {
        $errors[] = 'Поле ИМЯ должно быть не пустым и не больше 50 символов';
    }

    if (strlen($secondName) < 1 || strlen($secondName) > 50) {
        $errors[] = 'Поле ОТЧЕСТВО должно быть не пустым и не больше 50 символов';
    }

    return array(
        'errors' => $errors,
        'employee' => array(
            'login' => $login,
            'password' => $password,
            'surname' => $surname,
            'first_name' => $firstName,
            'second_name' => $secondName,
        ),
    );
}

function register_employee($link, $input)
{
    $validation = validate_employee_registration_input($input);

    if ($validation['errors']) {
        return $validation['errors'];
    }

    $employee = $validation['employee'];
    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return array(database_error_message($link, __FILE__ . ':' . __LINE__));
    }

    $lastEmployeeResult = db_query(
        $link,
        'SELECT id FROM employees ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );

    if (!$lastEmployeeResult) {
        $transaction->rollback();
        return array(database_error_message($link, __FILE__ . ':' . __LINE__));
    }

    $lastEmployee = db_fetch_one($lastEmployeeResult);
    $newUserId = $lastEmployee ? (int)$lastEmployee['id'] + 1 : 1;
    $duplicateResult = db_query(
        $link,
        'SELECT 1 FROM employees WHERE login = ? LIMIT 1',
        's',
        array($employee['login'])
    );

    if (!$duplicateResult) {
        $transaction->rollback();
        return array(database_error_message($link, __FILE__ . ':' . __LINE__));
    }

    if (db_has_rows($duplicateResult)) {
        $transaction->rollback();
        return array('Пользователь с таким логином уже существует');
    }

    $passwordHash = md5(md5(trim($employee['password'])));
    $created = db_execute(
        $link,
        'INSERT INTO employees VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'isssssssi',
        array(
            $newUserId,
            $employee['login'],
            $passwordHash,
            $employee['first_name'],
            $employee['second_name'],
            $employee['surname'],
            '',
            '',
            -1,
        )
    );

    if (!$created) {
        $transaction->rollback();
        return array(database_error_message($link, __FILE__ . ':' . __LINE__));
    }

    if (!$transaction->commit()) {
        return array(database_error_message($link, __FILE__ . ':' . __LINE__));
    }

    return array();
}
