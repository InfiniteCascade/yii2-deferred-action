<?php
namespace infinite\deferred;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Application;
use yii\base\Event;
use infinite\base\Daemon;
use infinite\base\Cron;
use infinite\deferred\models\DeferredAction;
use yii\helpers\Url;

class Module extends \yii\base\Module
{
    public $recentlyFinished = 60;

    public function init()
    {
        parent::init();
        $app = Yii::$app;
        if ($app instanceof \yii\web\Application) {
            $app->getUrlManager()->addRules([
                $this->id . '/<action:[\w\-]+>' => $this->id . '/default/<action>',
            ], false);
        }
    }

    public function daemonTick($event)
    {
        
        $this->handleOneQueued();
    }

    public function daemonPriority($event)
    {

    }

    protected function pickOneQueued()
    {
        return DeferredAction::find()->where(['status' => 'queued'])->orderBy(['priority' => SORT_DESC, 'created' => SORT_DESC])->one();
    }

    protected function handleOneQueued()
    {
        $queued = $this->pickOneQueued();
        if ($queued) {
            $queued->run();
        }
    }

    public function cleanActions($event)
    {
    }

    public function navPackage()
    {
        $package = ['_' => [], 'items' => [], 'running' => false, 'recentlyFinished' => false];
        $package['_']['url'] = Url::to('/'.$this->id.'/nav-package');
        $items = DeferredAction::findMine()->all();
        $package['items'] = [];
        $old = time() - $this->recentlyFinished;
        foreach ($items as $item) {
            if (!empty($item->ended)) {
                $ended = strtotime($item->ended);
                if ($ended > $old) {
                    $package['recentlyFinished'] = true;
                }
            }
            if (in_array($item->status, ['running', 'queued'])) {
                $package['running'] = true;
            }
            $package['items'][$item->primaryKey] = $item->package();
        }
        return $package;
    }
}