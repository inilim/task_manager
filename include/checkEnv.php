<?php

$err = [];

if (!\array_key_exists('TASK', $_SERVER)) {
    $err[] = 'Env "TASK" not found';
}

if (!\array_key_exists('TASK_PARAMS', $_SERVER)) {
    $err[] = 'Env "TASK_PARAMS" not found';
}

if ($err) {
    exit('__err:' . \json_encode($err));
}
unset($err);
