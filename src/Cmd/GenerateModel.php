<?php

namespace JbSchmitt\model\Cmd;

use JbSchmitt\model\Exception\FalseConfigurationException;
use JbSchmitt\model\Generator\ModelBaseScript;
use JbSchmitt\model\Generator\TableScript;

class GenerateModel implements Command {

    private $table, $config, $modelPath;
    
    public function execute() {
        if (!self::mkdir_p($this->modelPath . "/Table")) {
            throw new FalseConfigurationException("Couldn't create $this->modelPath/Table.");
        }
        if (!self::mkdir_p($this->modelPath . "/Base")) {
            throw new FalseConfigurationException("Couldn't create $this->modelPath/Base.");
        }

        $table = new TableScript($this->config['db_name'], $this->table);
        $table->createFile($this->modelPath . "/Table");

        $modelBase = new ModelBaseScript($this->config['db_name'], $this->table);
        $modelBase->createFile($this->modelPath . "/Base");
    }

    public function config(array $config) {
        $this->config = $config;
        if (!empty($config['model_path'])) {
            $this->modelPath = (substr($config['model_path'], 0, 1) == ".") ? getcwd() . substr($config['model_path'], 1) : $config['model_path'];
        } else {
            $inputPath = readline("Relative path to generate DBModel in: ");
            $this->modelPath = $inputPath;
        }
        $option = getopt('', ['generate', 'table:']);
        var_dump($option);
        if (!empty($option['table'])) {
            $this->table = $option['table'];
        } else if (!empty($config['model_table_name'])) {
            $this->table = $config['model_table_name'];
        } else {
            $inputPath = readline("Table name: ");
            $this->table = $inputPath;
            
        }
    }

    static protected function mkdir_p(string $path) {
        if (!file_exists($path)) {
            return mkdir($path, 0777, TRUE);
        }
        return TRUE;
    }

}
