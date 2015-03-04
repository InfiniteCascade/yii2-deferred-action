<?php
namespace infinite\deferred;

use infinite\base\Cron;
use infinite\base\Daemon;
use yii\base\BootstrapInterface;
use yii\base\Event;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->registerMigrationAlias('@infinite/deferred/migrations');
        $app->setModule('deferredAction', ['class' => Module::className()]);
        $module = $app->getModule('deferredAction');
        Event::on(Daemon::className(), Daemon::EVENT_TICK, [$module, 'daemonTick']);
        Event::on(Daemon::className(), Daemon::EVENT_POST_TICK, [$module, 'daemonPostTick']);
        Event::on(Cron::className(), Cron::EVENT_MIDNIGHT, [$module, 'cleanActions']);
    }
}
