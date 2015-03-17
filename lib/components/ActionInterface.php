<?php
namespace canis\deferred\components;

interface ActionInterface {
	public function run();
	public function requiredConfigParams();
	public function getDescriptor();
	public function cancel();
	
	public static function setup();
	public function context();
	protected function prepareContext();
	protected function resetContext();
	public function getResult();
}
?>