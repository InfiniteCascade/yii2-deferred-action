<?php
namespace canis\deferred;

use canis\cron\Cron;
use canis\daemon\Daemon;
use yii\base\BootstrapInterface;
use yii\base\Event;

/**
 * Bootstrap [[@doctodo class_description:canis\deferred\Bootstrap]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * [[@doctodo method_description:bootstrap]].
     *
     * @param [[@doctodo param_type:app]] $app [[@doctodo param_description:app]]
     */
    public function bootstrap($app)
    {
        $app->registerMigrationAlias('@canis/deferred/migrations');
        $app->setModule('deferredAction', ['class' => Module::className()]);
        $module = $app->getModule('deferredAction');
        Event::on(Daemon::className(), Daemon::EVENT_TICK, [$module, 'daemonTick']);
        Event::on(Daemon::className(), Daemon::EVENT_POST_TICK, [$module, 'daemonPostTick']);
        Event::on(Cron::className(), Cron::EVENT_MIDNIGHT, [$module, 'cleanActions']);
    }
}
