<?php

namespace JbSchmitt\model\Cmd;

interface Command {
    public function execute();
    public function config(array $config);
}