<?php
namespace infinite\deferred;

use infinite\base\Cron;
use infinite\base\Daemon;
use yii\base\BootstrapInterface;
use yii\base\Event;

/**
 * Bootstrap [[@doctodo class_description:infinite\deferred\Bootstrap]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * [[@doctodo method_description:bootstrap]].
     */
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
