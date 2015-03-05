<?php
namespace infinite\deferred\components;

use infinite\deferred\models\DeferredAction;
use Yii;
use yii\helpers\Url;

/**
 * Action [[@doctodo class_description:infinite\deferred\components\Action]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Action extends \infinite\action\WebAction
{
    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_CRITICAL = 4;

    /**
     * @var [[@doctodo var_type:model]] [[@doctodo var_description:model]]
     */
    public $model;
    /**
     * @var [[@doctodo var_type:configFatal]] [[@doctodo var_description:configFatal]]
     */
    public $configFatal = true;
    /**
     * @var [[@doctodo var_type:_context]] [[@doctodo var_description:_context]]
     */
    protected $_context;
    /**
     * @var [[@doctodo var_type:_result]] [[@doctodo var_description:_result]]
     */
    protected $_result;
    /**
     * @var [[@doctodo var_type:_oldContext]] [[@doctodo var_description:_oldContext]]
     */
    protected $_oldContext;
    /**
     * @var [[@doctodo var_type:guestExpiration]] [[@doctodo var_description:guestExpiration]]
     */
    public $guestExpiration = '+1 days';
    /**
     * @var [[@doctodo var_type:userExpiration]] [[@doctodo var_description:userExpiration]]
     */
    public $userExpiration = '+1 week';
    /**
     * @var [[@doctodo var_type:errorExpiration]] [[@doctodo var_description:errorExpiration]]
     */
    public $errorExpiration = '+1 days';

    /**
     * @inheritdoc
     */
    protected $_interactions = [];

    /**
     * Prepares object for serialization.
     *
     * @return [[@doctodo return_type:__sleep]] [[@doctodo return_description:__sleep]]
     */
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

    /**
     * @inheritdoc
     */
    public function save()
    {
        if (empty($this->model)) {
            return true;
        }
        $this->model->peak_memory = memory_get_peak_usage();

        return $this->model->save();
    }

    /**
     * @inheritdoc
     */
    public function cancel()
    {
        return true;
    }

    /**
     * Get expire time.
     *
     * @return [[@doctodo return_type:getExpireTime]] [[@doctodo return_description:getExpireTime]]
     */
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

    /**
     * Get is guest action.
     *
     * @return [[@doctodo return_type:getIsGuestAction]] [[@doctodo return_description:getIsGuestAction]]
     */
    public function getIsGuestAction()
    {
        if (isset($this->model) && !empty($this->model->user_id)) {
            return false;
        }

        return true;
    }

    /**
     * Get priority.
     *
     * @return [[@doctodo return_type:getPriority]] [[@doctodo return_description:getPriority]]
     */
    public function getPriority()
    {
        return static::PRIORITY_LOW;
    }

    /**
     * Set up.
     *
     * @param array $config [[@doctodo param_description:config]] [optional]
     *
     * @return [[@doctodo return_type:setup]] [[@doctodo return_description:setup]]
     */
    public static function setup($config = [])
    {
        $item = new static();
        $item->config = $config;
        $item->model = new DeferredAction();
        $item->model->actionObject = $item;
        $item->model->priority = $item->getPriority();
        static::prepareModel($item->model);
        if ($item->model->save()) {
            return $item;
        }
        \d($item->model->errors);
        exit;

        return false;
    }

    /**
     * [[@doctodo method_description:prepareModel]].
     *
     * @param infinite\deferred\models\DeferredAction $model [[@doctodo param_description:model]]
     *
     * @return [[@doctodo return_type:prepareModel]] [[@doctodo return_description:prepareModel]]
     */
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

    /**
     * Get result config.
     *
     * @return [[@doctodo return_type:getResultConfig]] [[@doctodo return_description:getResultConfig]]
     */
    public function getResultConfig()
    {
        return [
            'class' => Result::className(),
        ];
    }

    /**
     * [[@doctodo method_description:package]].
     *
     * @return [[@doctodo return_type:package]] [[@doctodo return_description:package]]
     */
    public function package()
    {
        return $this->model->package();
    }

    /**
     * Get result.
     *
     * @return [[@doctodo return_type:getResult]] [[@doctodo return_description:getResult]]
     */
    public function getResult()
    {
        if (!isset($this->_result)) {
            $this->_result = Yii::createObject($this->resultConfig);
        }
        $this->_result->action = $this;

        return $this->_result;
    }

    /**
     * [[@doctodo method_description:clearResult]].
     *
     * @return [[@doctodo return_type:clearResult]] [[@doctodo return_description:clearResult]]
     */
    public function clearResult()
    {
        $result = $this->result;
        if (!empty($result)) {
            return $result->clear();
        }

        return true;
    }

    /**
     * [[@doctodo method_description:context]].
     *
     * @return [[@doctodo return_type:context]] [[@doctodo return_description:context]]
     */
    public function context()
    {
        return $this->getBaseContext();
    }

    /**
     * @inheritdoc
     */
    public function packageData($details = false)
    {
        $d = parent::packageData($details);
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

    /**
     * [[@doctodo method_description:prepareContext]].
     */
    protected function prepareContext()
    {
        $this->_oldContext = $this->context;
        $this->context = $this->context();
    }

    /**
     * [[@doctodo method_description:resetContext]].
     */
    protected function resetContext()
    {
        if (isset($this->_oldContext)) {
            $this->setContext($this->_oldContext);
        }
    }

    /**
     * Set context.
     *
     * @return [[@doctodo return_type:setContext]] [[@doctodo return_description:setContext]]
     */
    public function setContext($context)
    {
        if (isset($context['idenity']) && isset(Yii::$app->user)) {
            Yii::$app->user->identity = $context['idenity'];
        }

        return true;
    }

    /**
     * Get context.
     *
     * @return [[@doctodo return_type:getContext]] [[@doctodo return_description:getContext]]
     */
    public function getContext()
    {
        if (!isset($this->_context)) {
            $this->_context = $this->baseContext;
        }

        return $this->_context;
    }

    /**
     * Get base context.
     *
     * @return [[@doctodo return_type:getBaseContext]] [[@doctodo return_description:getBaseContext]]
     */
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
