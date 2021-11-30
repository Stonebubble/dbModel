<?php

namespace JbSchmitt\model\Cmd;

use InvalidArgumentException;
use JbSchmitt\model\Exception\FalseConfigurationException;

class GeneratorScript {
    private array $config = [];
    private array $parameter;

    public function __construct(private array $cmds) {
        $this->parameter = getopt("", [
            "config:",
            "model::"
        ], $p);
        $this->loadConfig();
    }

    private function loadConfig() {
        if (!empty($this->parameter['config'])) {
            if (file_exists($this->parameter['config'])) {
                $this->config = include $this->parameter['config'];
                $this->config['config_path'] = $this->parameter['config'];
            } else {
                copy("default.config.php", $this->parameter['config']);
                throw new FalseConfigurationException("Please fill out config.php and than continue.");
            }
            return;
        }
        if (file_exists(getcwd() . '/config/config.php')) {
            $this->config = include getcwd() . '/config/config.php';
            $this->config['config_path'] = getcwd() . '/config/config.php';
            return;
        }
        if (file_exists(getcwd() . '/config.php')) {
            $this->config = include getcwd() . '/config.php';
            $this->config['config_path'] = getcwd() . '/config.php';
            return;
        }
        if (file_exists(getcwd() . '/config')) {
            copy("default.config.php", getcwd() . "/config/config.php");
        } else {
            copy("default.config.php", getcwd() . "/config.php");
        }
        throw new FalseConfigurationException("Please fill out config.php and than continue...");
    }

    public function execute() {
        global $argv;
        var_dump($argv[1]);
        if (empty($argv[1]))
            throw new InvalidArgumentException("No command selected.");
        if (empty($this->cmds[$argv[1]])) {
            return FALSE;
        }
        $this->cmds[$argv[1]]->config($this->config);
        $this->cmds[$argv[1]]->execute();
        return TRUE;
    }
    
}
