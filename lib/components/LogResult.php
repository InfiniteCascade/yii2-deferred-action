<?php
namespace infinite\deferred\components;

use Yii;
use yii\web\NotFoundHttpException;
use infinite\helpers\StringHelper;
use yii\helpers\FileHelper;
use infinite\helpers\Date;
use yii\helpers\Url;

abstract class LogResult extends Result
{
    const MESSAGE_LEVEL_INFO = '_i';
    const MESSAGE_LEVEL_WARNING = '_w';
    const MESSAGE_LEVEL_ERROR = '_e';

    protected $_messages = [];
    protected $_has_error = false;
    protected $_has_warning = false;
    protected $_total = false;
    protected $_completed = 0;
	
	public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * __method_addError_description__
     * @param __param_message_type__ $message __param_message_description__
     */
    public function addMessage($message, $data = null, $messageLevel = null)
    {
        if (is_null($messageLevel)) {
            $messageLevel = static::MESSAGE_LEVEL_INFO;
        }
        if ($messageLevel === static::MESSAGE_LEVEL_ERROR) {
            $this->_has_error = true;
        }
        if ($messageLevel === static::MESSAGE_LEVEL_WARNING) {
            $this->_has_warning = true;
        }
        $this->_messages[] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(),
            'message' => $message,
            'level' => $messageLevel,
            'data' => $data,
        ];
        return $this->save();
    }

    public function addInfo($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_INFO);
    }

    public function addWarning($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_WARNING);
    }

    public function addError($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_ERROR);
    }

    public function tickCompletion($save = true)
    {
        $this->_completed++;
        if ($save) {
            return $this->save();
        }
        return true;
    }

    public function setTotal($total, $save = true)
    {
        $this->_total = $total;
        if ($save) {
            $this->save();
        }
        return $this;
    }

    public function getTotal()
    {
        return $this->_total;
    }

    public function getProgress()
    {
        if (!empty($this->total)) {
            return round(($this->_completed / $this->total) * 100, 1);
        }
        return false;
    }

    public function package($details = false)
	{
		$p = parent::package($details);
		$p['viewLog'] = Url::to(['/deferredAction/view-log', 'id' => $this->action->model->id]);
        $p['progress'] = $this->progress;
        if ($details) {
            $p['messages'] = [];
            $lastTime = $started = null;
            if (!empty($this->action->model->started)) {
                $lastTime = $started = strtotime($this->action->model->started);
            }
            foreach ($this->_messages as $key => $message) {
                $key = $key.'-'.substr(md5($key), 0, 5);
                $timestamp = (float) $message['time'];
                if (!isset($lastTime)) {
                    $lastTime = $timestamp;
                }
                if (!isset($started)) {
                    $started = $timestamp;
                }
                $duration = $timestamp - $lastTime;
                $lastTime = $timestamp;
                $fromStart = $timestamp-$started;
                $p['messages'][$key] = [
                    'message' => $message['message'],
                    'duration' => Date::shortDuration($duration),
                    'fromStart' => Date::shortDuration($fromStart),
                    'level' => $message['level'],
                    'data' => empty($message['data']) ? null : print_r($message['data'], true),
                    'memory' => StringHelper::humanFilesize($message['memory']),
                ];
            }
        }
		return $p;
	}
}
?>