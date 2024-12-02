<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Inilim\Dump\Dump;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

Dump::init();



// $phpBinaryFinder = new PhpExecutableFinder();
// $phpBinaryPath = $phpBinaryFinder->find();

// de($phpBinaryPath);
// $process = new Process(['php', '-v']);

// de($_ENV);

$task = 'test_child_process.php';
$task = \realpath($task);
$task = \str_replace('\\', '\\\\', $task);

$files = [];
$files[] = \str_replace('\\', '\\\\', \realpath(__DIR__ . '/../include/checkEnv.php'));
$files[] = $task;

// de($files);

$script = \sprintf(
    '<?php
    include "%s";
    include "%s";
    ',
    ...$files,
);

$process = new PhpProcess($script, null, ['BLA' => 123]);
$process->setTimeout(5.0);
$process->start();

try {
    $process->wait();
} catch (\Throwable) {
}

dde($process->getOutput());

de();
foreach ($results as $type => $data) {
    if ($process::OUT === $type) {
        d([
            '$type' => $type,
            '$data' => \sprintf(
                '"%s"',
                \trim($data)
            ),
        ]);
    } else {
        d([
            '$type' => $type,
            '$data' => \sprintf(
                '"%s"',
                \trim($data)
            ),
        ]);
    }
}
