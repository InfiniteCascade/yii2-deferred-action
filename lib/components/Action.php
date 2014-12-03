<?php
namespace infinite\deferred\components;

use Yii;
use infinite\deferred\models\DeferredAction;
use yii\base\InvalidConfigException;

class Action extends \yii\base\Component
{
	public $model;
	public $configFatal = true;
	protected $_config = [];
	protected $_context;
	protected $_result;
	protected $_oldContext;
	public $guestExpiration = '+1 days';
	public $userExpiration = '+1 week';

	public function __sleep()
    {
        $keys = array_keys((array) $this);
        $bad = ["model"];
        foreach ($keys as $k => $key) {
            if (in_array($key, $bad)) {
                unset($keys[$k]);
            }
        }
        return $keys;
    }

    public function getExpireTime()
    {
    	if ($this->isGuestAction) {
    		return strtotime($this->guestExpiration);
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

	public static function setup($config = [])
	{
		$item = new static;
		$item->config = $config;
		$item->model = new DeferredAction;
		$item->model->actionObject = $item;
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
		$this->_config = $config;
		$this->checkParams($this->configFatal);
	}

	public function getConfig()
	{
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
		return $this->_result;
	}

	public function requiredConfigParams()
	{
		return [];
	}

	public function context()
	{
		return $this->getBaseContext();;
	}

	public function packageData()
	{
		$d = [];
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