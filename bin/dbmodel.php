<?php


use JbSchmitt\model\Generator\ModelBaseScript;
use JbSchmitt\model\Generator\TableScript;
use JbSchmitt\model\ModelDB;

echo getcwd() . "\n";

if (count($argv) == 1) {
    echo "\033[31mPlease choose a cmd:\033[0m\n";
    echo "  generate-all\n";
    exit();
}

function configCheck() {
    if (!file_exists(getcwd() . '/config.php')) {
        $configPath = readline("Dir to generate config file: (default) '" . getcwd() . "'");
        if ($configPath === FALSE) {
            echo "\n\033[32mexit.\033[0m\n";
            die();
        }
        if (empty($configPath)) {
            $configPath = getcwd();
        }
        if (file_exists($configPath . "/config.php")) {
            $GLOBALS['configPath'] = $configPath;
            return;
        }
        copy("default.config.php", $configPath . "/config.php");
        echo "Please fill out config.php and than continue...\n\n";
        exit();
    } else {
        $GLOBALS['configPath'] = getcwd();
    }
}

function mkdir_p(string $path) {
    if (!file_exists($path)) {
        return mkdir($path, 0777, TRUE);
    }
    return TRUE;
}

function requireComposer() {
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
}

switch ($argv[1]) {
    case 'generate-all':
        configCheck();
        requireComposer();
        $inputPath = readline("Path to generate DBModel in: (default) '" . ".../app/Model" . "'");
        $modelPath = $GLOBALS['configPath'] . (empty($inputPath) ? "/app/Model" : $inputPath);
        $_SERVER['DOCUMENT_ROOT'] = __DIR__ . "/public";
        if (!mkdir_p($modelPath . "/Table")) {
            echo "\033[31mCouldn't create $modelPath/Table.\033[0m\n";
            exit();
        } 
        if (!mkdir_p($modelPath . "/Base")) {
            echo "\033[31mCouldn't create $modelPath/Base.\033[0m\n";
            exit();
        }
        $c = include $GLOBALS['configPath'] . "/config.php";
        try {
            ModelDB::constructStatic($GLOBALS['configPath'] . "/config.php");
            $tablesDB = ModelDB::selectGroup("SHOW TABLES");
        } catch (\Throwable $th) {
            echo "\033[31m" . $th->getMessage() . "\033[0m\n";
            exit();
        }
        echo "\033[32mBuilt table map and model for:";
        foreach ($tablesDB as $key => $value) {
            $table = new TableScript($c['db_name'], $key);
            $table->createFile($modelPath . "/Table");

            $modelBase = new ModelBaseScript($c['db_name'], $key);
            $modelBase->createFile($modelPath . "/Base");
            echo ", $key";
        }
        echo "\033[0m\n";
        break;
    
    default:
        echo "\033[31mCommand not found.\033[0m\n";
        break;
}