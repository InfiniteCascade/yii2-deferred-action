<?php
namespace infinite\deferred;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Application;
use yii\base\Event;
use infinite\base\Daemon;
use infinite\base\Cron;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
    	$app->registerMigrationAlias('@infinite/deferred/migrations');
        $app->setModule('deferredAction', ['class' => Module::className()]);
        $module = $app->getModule('deferredAction');
    	Event::on(Daemon::className(), Daemon::EVENT_TICK, [$module, 'daemonTick']);
    	Event::on(Cron::className(), Cron::EVENT_MIDNIGHT, [$module, 'cleanActions']);
    }
}