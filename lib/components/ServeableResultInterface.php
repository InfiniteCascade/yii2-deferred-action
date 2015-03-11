<?php
namespace teal\deferred\components;

interface ServeableResultInterface
{
    public function serve();
    public function getServeableFilePath();
    public function getMimeType();
    public function getNiceFilename();
}
