<?php

use JbSchmitt\model\Cmd\GenerateAll;
use JbSchmitt\model\Cmd\GenerateModel;
use JbSchmitt\model\Cmd\GeneratorScript;

if (count($argv) == 1) {
    echo "\033[31mPlease choose a cmd:\033[0m\n";
    echo "  generate-all\n";
    exit();
}

$autoloadFileCandidates = [
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../autoload.php',
    __DIR__ . '/../autoload.php.dist',
    __DIR__ . '/../vendor/autoload.php'
];
foreach ($autoloadFileCandidates as $file) {
    if (file_exists($file)) {
        require_once $file;

        break;
    }
}

try {
    $generator = new GeneratorScript([
        'generate-all' => new GenerateAll,
        'generate' => new GenerateModel
    ]);

    if ($generator->execute() === FALSE) {
        echo "\033[31mCommand not found.\033[0m\n";
    }
} catch (\Throwable $th) {
    echo "\033[31m" . $th->getMessage() . "\033[0m\n";
}

