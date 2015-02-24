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
    protected $_active;

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

    public function daemonPostTick()
    {
        if (isset($this->_active)) {
            $lastError = error_get_last();
            $lastErrorMessage = '';
            if (isset($lastError['file'])) {
                $lastErrorMessage .= $lastError['file'];
            }
            if (isset($lastError['line'])) {
                $lastErrorMessage .= ':'. $lastError['line'];
            }
            if (isset($lastError['message'])) {
                $lastErrorMessage .= ' :'. $lastError['message'];
            }
            $this->_active->status = 'error';
            $this->_active->actionObject->result->message .= ' Runner Error: '. $lastErrorMessage;
            $this->_active->save();
        }
    }

    public function daemonTick($event)
    {
        $this->handleOneQueued();
    }

    public function daemonPriority($event)
    {
        $running = DeferredAction::find()->where(['status' => 'running'])->orderBy(['priority' => SORT_DESC, 'created' => SORT_DESC])->all();
        foreach ($running as $action) {
            $action->status = 'error';
            $action->save();
        }
    }

    protected function pickOneQueued()
    {
        return DeferredAction::find()->where(['status' => 'queued'])->orderBy(['priority' => SORT_DESC, 'created' => SORT_DESC])->one();
    }

    protected function handleOneQueued()
    {
        $queued = $this->pickOneQueued();
        if ($queued) {
            try {
                $queued->run();
            } catch (\Exception $e) {
                $queued = DeferredAction::find()->where(['id' => $queued->id])->one();
                if ($queued) {
                    $queued->status = 'error';
                    $message = $e->getFile() .':'. $e->getLine().' '. $e->getMessage();
                    $queued->actionObject->result->message .= ' Runner Exception: '. $message;
                    $queued->save();
                }
            }
        }
    }

    public function cleanActions($event)
    {
        $items = DeferredAction::find()->where(['and', '`expires` < NOW()', '`status`=\'success\''])->all();
        foreach ($items as $item) {
            $item->dismiss(false);
        }
    }

    public function navPackage()
    {
        $package = ['_' => [], 'items' => [], 'running' => false, 'mostRecentEvent' => false];
        $package['_']['url'] = Url::to('/'.$this->id.'/nav-package');
        $items = DeferredAction::findMine()->andWhere(['and', '`status` != "cleared"', ['or', '`expires` IS NULL', '`expires` > NOW()']])->all();
        $package['items'] = [];
        foreach ($items as $item) {
            if (!empty($item->ended)) {
                $modified = strtotime($item->modified);
                if (!$package['mostRecentEvent'] || $modified > $package['mostRecentEvent']) {
                    $package['mostRecentEvent'] = $modified;
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
