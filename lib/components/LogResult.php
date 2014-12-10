<?php
namespace infinite\deferred\components;

use Yii;
use yii\web\NotFoundHttpException;
use yii\helpers\FileHelper;
use yii\helpers\Url;

abstract class LogResult extends Result
{
    const MESSAGE_LEVEL_INFO = '_i';
    const MESSAGE_LEVEL_WARNING = '_w';
    const MESSAGE_LEVEL_ERROR = '_e';

    protected $_messages = [];
    protected $_has_error = false;
    protected $_has_warning = false;
	
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

    public function package()
	{
		$package = parent::package();
		$package['viewLog'] = Url::to(['/deferredAction/view-log', 'id' => $this->action->model->id]);
		return $package;
	}
}
?>