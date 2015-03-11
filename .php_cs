<?php
$path = __DIR__;
$docBlockSettings = [];
$docBlockSettings['package'] = 'teal-deferred';

return include(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'yii2-teal-lib' . DIRECTORY_SEPARATOR . '.php_cs');
?>