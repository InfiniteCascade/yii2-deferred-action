<?php
namespace infinite\deferred\components;

interface ActionInterface {
	public static function setup();
	public function context();
	protected function prepareContext();
	protected function resetContext();
	public function run();
	public function getResult();
	public function requiredConfigParams();
}
?>