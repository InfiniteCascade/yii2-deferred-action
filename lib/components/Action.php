<?php
namespace infinite\deferred\components;

use Yii;
use infinite\deferred\models\DeferredAction;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

abstract class Action extends \yii\base\Component
{
	const PRIORITY_LOW = 1;
	const PRIORITY_MEDIUM = 2;
	const PRIORITY_HIGH = 3;
	const PRIORITY_CRITICAL = 4;

	public $model;
	public $configFatal = true;
	protected $_config;
	protected $_context;
	protected $_result;
	protected $_oldContext;
	public $guestExpiration = '+1 days';
	public $userExpiration = '+1 week';
	public $errorExpiration = '+1 days';

	public function __sleep()
    {
        $keys = array_keys((array) $this);
        $bad = ["\0*\0_cache", "model"];
        foreach ($keys as $k => $key) {
            if (in_array($key, $bad)) {
                unset($keys[$k]);
            }
        }
        return $keys;
    }

	public function save()
	{
		if (empty($this->model)) {
			return true;
		}
        $this->model->peak_memory = memory_get_peak_usage();
		return $this->model->save();
	}

	public function cancel()
	{
		return true;
	}

    public function getExpireTime()
    {
    	if ($this->isGuestAction) {
    		return strtotime($this->guestExpiration);
    	}
    	if ($this->model->status === 'error') {
    		return strtotime($this->errorExpiration);
    	}
    	return strtotime($this->userExpiration);
    }

    public function getIsGuestAction()
    {
    	if (isset($this->model) && !empty($this->model->user_id)) {
    		return false;
    	}
    	return true;
    }

    public function getPriority()
    {
    	return static::PRIORITY_LOW;
    }

	public static function setup($config = [])
	{
		$item = new static;
		$item->config = $config;
		$item->model = new DeferredAction;
		$item->model->actionObject = $item;
		$item->model->priority = $item->getPriority();
		static::prepareModel($item->model);
		if ($item->model->save()) {
			return $item;
		}
		\d($item->model->errors);exit;
		return false;
	}

	public static function prepareModel(DeferredAction $model)
	{
		if (isset(Yii::$app->user) && !Yii::$app->user->isGuest) {
			$model->user_id = Yii::$app->user->id;
		}
		if (isset(Yii::$app->session)) {
			$sessionId = Yii::$app->session->id;
			if (empty($sessionId)) {
				Yii::$app->session->open();
				$sessionId = Yii::$app->session->id;
			}
			if (empty($sessionId)) {
				$sessionId = 'unknown-web';
			}
			$model->session_id = $sessionId;
		} else {
			$model->session_id = 'console';
		}
		return $model;
	}

	public function setConfig($config)
	{
		$checkParams = false;
		if (!isset($this->_config)) {
			$checkParams = true;
		}
		$this->_config = $config;
		if ($checkParams) {
			$this->checkParams($this->configFatal);
		}
	}

	public function getConfig()
	{
		if (!isset($this->_config)) {
			return [];
		}
		return $this->_config;
	}

	public function checkParams($fatal = true)
	{
		foreach ($this->requiredConfigParams() as $param) {
			if (!isset($this->config[$param])) {
				if ($fatal) {
					throw new InvalidConfigException("Config setting {$param} is required for ". get_called_class());
				}
				return false;
			}
		}
		return true;
	}

	public function getResultConfig()
	{
		return [
			'class' => Result::className()
		];
	}

	public function package()
	{
		return $this->model->package();
	}

	public function getResult()
	{
		if (!isset($this->_result)) {
			$this->_result = Yii::createObject($this->resultConfig);
		}
		$this->_result->action = $this;
		return $this->_result;
	}

	public function clearResult()
	{
		$result = $this->result;
		if (!empty($result)) {
			return $result->clear();
		}
		return true;
	}

	public function requiredConfigParams()
	{
		return [];
	}

	public function context()
	{
		return $this->getBaseContext();;
	}

	public function packageData($details = false)
	{
		$d = [];
		$d['descriptor'] = $this->descriptor;
		$d['result'] = $this->result->package($details);

		$d['dismissUrl'] = false;
		if (in_array($this->model->status, ['success', 'error'])) {
			$d['dismissUrl'] = Url::to(['/deferredAction/dismiss', 'id' => $this->model->id]);
		}

		$d['actions'] = [];
		if (in_array($this->model->status, ['queued'])) {
			$d['actions'][] = ['label' => 'Cancel', 'url' => Url::to(['/deferredAction/cancel', 'id' => $this->model->id]), 'state' => 'warning', 'data-handler' => 'background'];
		}
		if (isset($d['result']['actions'])) {
			$d['actions'] = array_merge($d['actions'], $d['result']['actions']);
			unset($d['result']['actions']);
		}
		return $d;
	}

	protected function prepareContext()
	{
		$this->_oldContext = $this->context;
		$this->context = $this->context();
	}

	protected function resetContext()
	{
		if (isset($this->_oldContext)) {
			$this->setContext($this->_oldContext);
		}
	}

	public function setContext($context)
	{
		if (isset($context['idenity']) && isset(Yii::$app->user)) {
			Yii::$app->user->identity = $context['idenity'];
		}
		return true;
	}

	public function getContext()
	{
		if (!isset($this->_context)) {
			$this->_context = $this->baseContext;
		}
		return $this->_context;
	}

	protected function getBaseContext()
	{
		$context = [];
		if (isset(Yii::$app->user)) {
			if (Yii::$app->user->isGuest) {
				$guestGroup = Group::find()->where(['system' => 'guests'])->one();
				$context = $guestGroup;
			} else {
				$context = Yii::$app->user->identity;
			}
		}
		return $context;
	}
}
?>