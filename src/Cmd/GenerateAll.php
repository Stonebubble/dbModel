<?php

namespace JbSchmitt\model\Cmd;

use JbSchmitt\model\Exception\FalseConfigurationException;
use JbSchmitt\model\ModelDB;

class GenerateAll implements Command {
    private array $config = [];

    public function execute() {
        try {
            ModelDB::constructStatic($this->config['config_path']);
            $tablesDB = ModelDB::selectGroup("SHOW TABLES");
        } catch (\Throwable $th) {
            throw new FalseConfigurationException($th->getMessage(), $th->getCode(), $th);
        }

        foreach ($tablesDB as $key => $value) {
            $cmd = new GenerateModel;
            $cmd->config($this->config . ['model_table_name' => $key]);
            $cmd->execute();
        }
        return "Built table map and models";
    }

    public function config(array $config) {
        $this->config = $config;
    }
    
}
