<?php
namespace infinite\deferred\components;

use infinite\helpers\Date;
use infinite\helpers\StringHelper;
use yii\helpers\Url;

/**
 * LogResult [[@doctodo class_description:infinite\deferred\components\LogResult]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class LogResult extends Result
{
    const MESSAGE_LEVEL_INFO = '_i';
    const MESSAGE_LEVEL_WARNING = '_w';
    const MESSAGE_LEVEL_ERROR = '_e';

    /**
     * @var [[@doctodo var_type:_messages]] [[@doctodo var_description:_messages]]
     */
    protected $_messages = [];
    /**
     * @var [[@doctodo var_type:_has_error]] [[@doctodo var_description:_has_error]]
     */
    protected $_has_error = false;
    /**
     * @var [[@doctodo var_type:_has_warning]] [[@doctodo var_description:_has_warning]]
     */
    protected $_has_warning = false;
    /**
     * @var [[@doctodo var_type:_total]] [[@doctodo var_description:_total]]
     */
    protected $_total = false;
    /**
     * @var [[@doctodo var_type:_completed]] [[@doctodo var_description:_completed]]
     */
    protected $_completed = 0;

    /**
     * Get messages.
     *
     * @return [[@doctodo return_type:getMessages]] [[@doctodo return_description:getMessages]]
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * [[@doctodo method_description:addMessage]].
     *
     * @param [[@doctodo param_type:message]]      $message      [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]         $data         [[@doctodo param_description:data]] [optional]
     * @param [[@doctodo param_type:messageLevel]] $messageLevel [[@doctodo param_description:messageLevel]] [optional]
     *
     * @return [[@doctodo return_type:addMessage]] [[@doctodo return_description:addMessage]]
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

    /**
     * [[@doctodo method_description:addInfo]].
     *
     * @param [[@doctodo param_type:message]] $message [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]    $data    [[@doctodo param_description:data]] [optional]
     *
     * @return [[@doctodo return_type:addInfo]] [[@doctodo return_description:addInfo]]
     */
    public function addInfo($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_INFO);
    }

    /**
     * [[@doctodo method_description:addWarning]].
     *
     * @param [[@doctodo param_type:message]] $message [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]    $data    [[@doctodo param_description:data]] [optional]
     *
     * @return [[@doctodo return_type:addWarning]] [[@doctodo return_description:addWarning]]
     */
    public function addWarning($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_WARNING);
    }

    /**
     * [[@doctodo method_description:addError]].
     *
     * @param [[@doctodo param_type:message]] $message [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]    $data    [[@doctodo param_description:data]] [optional]
     *
     * @return [[@doctodo return_type:addError]] [[@doctodo return_description:addError]]
     */
    public function addError($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_ERROR);
    }

    /**
     * [[@doctodo method_description:tickCompletion]].
     *
     * @param boolean $save [[@doctodo param_description:save]] [optional]
     *
     * @return [[@doctodo return_type:tickCompletion]] [[@doctodo return_description:tickCompletion]]
     */
    public function tickCompletion($save = true)
    {
        $this->_completed++;
        if ($save) {
            return $this->save();
        }

        return true;
    }

    /**
     * Set total.
     *
     * @param [[@doctodo param_type:total]] $total [[@doctodo param_description:total]]
     * @param boolean                       $save  [[@doctodo param_description:save]] [optional]
     *
     * @return [[@doctodo return_type:setTotal]] [[@doctodo return_description:setTotal]]
     */
    public function setTotal($total, $save = true)
    {
        $this->_total = $total;
        if ($save) {
            $this->save();
        }

        return $this;
    }

    /**
     * Get total.
     *
     * @return [[@doctodo return_type:getTotal]] [[@doctodo return_description:getTotal]]
     */
    public function getTotal()
    {
        return $this->_total;
    }

    /**
     * Get progress.
     *
     * @return [[@doctodo return_type:getProgress]] [[@doctodo return_description:getProgress]]
     */
    public function getProgress()
    {
        if (!empty($this->total)) {
            return round(($this->_completed / $this->total) * 100, 1);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function package($details = false)
    {
        $package = parent::package($details);
        $package['viewLog'] = Url::to();
        $package['actions'][] = ['label' => 'View Log', 'url' => Url::to(['/deferredAction/view-log', 'id' => $this->action->model->id]), 'data-handler' => 'background'];
        $package['progress'] = $this->progress;
        if ($details) {
            $package['messages'] = [];
            $lastTime = $started = null;
            if (!empty($this->action->model->started)) {
                $lastTime = $started = strtotime($this->action->model->started);
            }
            foreach ($this->_messages as $key => $message) {
                $key = $key . '-' . substr(md5($key), 0, 5);
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
                $package['messages'][$key] = [
                    'message' => $message['message'],
                    'duration' => Date::shortDuration($duration),
                    'fromStart' => Date::shortDuration($fromStart),
                    'level' => $message['level'],
                    'data' => empty($message['data']) ? null : print_r($message['data'], true),
                    'memory' => StringHelper::humanFilesize($message['memory']),
                ];
            }
        }

        return $package;
    }
}
