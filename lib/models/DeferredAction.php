<?php

namespace canis\deferred\models;

use canis\helpers\Date;
use canis\helpers\StringHelper;
use Yii;

/**
 * DeferredAction is the model class for table "deferred_action".
 *
 * @property string $id
 * @property string $user_id
 * @property string $session_id
 * @property integer $priority
 * @property resource $action
 * @property integer $peak_memory
 * @property string $status
 * @property string $started
 * @property string $ended
 * @property string $expires
 * @property string $created
 * @property string $modified
 * @property User $user
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class DeferredAction extends \canis\db\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:_actionObject]] [[@doctodo var_description:_actionObject]]
     */
    protected $_actionObject;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_VALIDATE, [$this, 'serializeAction']);
    }

    /**
     * [[@doctodo method_description:serializeAction]].
     */
    public function serializeAction()
    {
        if (isset($this->_actionObject)) {
            try {
                $this->action = serialize($this->_actionObject);
            } catch (\Exception $e) {
                \d($this->_actionObject);
                exit;
            }
        }
    }
    /**
     * @inheritdoc
     */
    public static function isAccessControlled()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'deferred_action';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['priority', 'peak_memory'], 'integer'],
            [['action', 'session_id', 'action_signature'], 'required'],
            [['action', 'status', 'action_signature'], 'string'],
            [['started', 'ended', 'expires', 'created', 'modified'], 'safe'],
            [['user_id'], 'string', 'max' => 36],
            [['session_id'], 'string', 'max' => 40],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'session_id' => 'Session ID',
            'priority' => 'Priority',
            'action' => 'Action',
            'peak_memory' => 'Peak Memory',
            'status' => 'Status',
            'started' => 'Started',
            'ended' => 'Ended',
            'expires' => 'Expires',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }

    /**
     * Get user.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * [[@doctodo method_description:findMine]].
     *
     * @param [[@doctodo param_type:sessionId]] $sessionId [[@doctodo param_description:sessionId]] [optional]
     *
     * @return [[@doctodo return_type:findMine]] [[@doctodo return_description:findMine]]
     */
    public static function findMine($sessionId = null)
    {
        $query = static::find();
        $where = ['or'];
        if ($sessionId === null) {
            if (isset(Yii::$app->session)) {
                if (empty(Yii::$app->session->id)) {
                    Yii::$app->session->open();
                }
                $sessionId = Yii::$app->session->id;
            }
        }
        if (!empty($sessionId)) {
            $where[] = ['session_id' => $sessionId];
        }
        if (!Yii::$app->user->isGuest) {
            $where[] = ['user_id' => Yii::$app->user->id];
        }
        if (count($where) === 1) {
            $where[] = '1=0';
        }
        $query->where($where);
        $query->orderBy(['modified' => SORT_DESC]);

        return $query;
    }

    /**
     * Set action object.
     *
     * @param [[@doctodo param_type:ao]] $ao [[@doctodo param_description:ao]]
     */
    public function setActionObject($ao)
    {
        $this->_actionObject = $ao;
    }

    /**
     * Get action object.
     *
     * @return [[@doctodo return_type:getActionObject]] [[@doctodo return_description:getActionObject]]
     */
    public function getActionObject()
    {
        if (!isset($this->_actionObject) && !empty($this->action)) {
            $this->_actionObject = unserialize($this->action);
            $this->_actionObject->model = $this;
        }

        return $this->_actionObject;
    }

    /**
     * [[@doctodo method_description:package]].
     *
     * @param boolean $details [[@doctodo param_description:details]] [optional]
     *
     * @return [[@doctodo return_type:package]] [[@doctodo return_description:package]]
     */
    public function package($details = false)
    {
        $p = [];
        $p['id'] = $this->primaryKey;
        $p['status'] = $this->status;
        $p['duration'] = $this->niceDuration;
        $p['date'] = date("F d, Y g:i:a", strtotime($this->created));
        $p['started'] = empty($this->started) ? null : date("F d, Y g:i:a", strtotime($this->started));
        $p['ended'] = empty($this->ended) ? null : date("F d, Y g:i:a", strtotime($this->ended));
        $p['data'] = $this->actionObject->packageData($details);

        return $p;
    }

    /**
     * Get nice duration.
     *
     * @return [[@doctodo return_type:getNiceDuration]] [[@doctodo return_description:getNiceDuration]]
     */
    public function getNiceDuration()
    {
        return Date::niceDuration($this->duration);
    }

    /**
     * Get peak memory.
     *
     * @return [[@doctodo return_type:getPeakMemory]] [[@doctodo return_description:getPeakMemory]]
     */
    public function getPeakMemory()
    {
        if (empty($this->peak_memory)) {
            return '0b';
        }

        return StringHelper::humanFilesize($this->peak_memory);
    }

    /**
     * Get duration.
     *
     * @return [[@doctodo return_type:getDuration]] [[@doctodo return_description:getDuration]]
     */
    public function getDuration()
    {
        $startTime = isset($this->started) ? strtotime($this->started) : strtotime($this->created);
        $endTime = isset($this->ended) ? strtotime($this->ended) : time();

        return $endTime - $startTime;
    }

    /**
     * [[@doctodo method_description:run]].
     *
     * @return [[@doctodo return_type:run]] [[@doctodo return_description:run]]
     */
    public function run()
    {
        $this->status = 'running';
        $this->started = gmdate("Y-m-d G:i:s");
        $this->save();
        if ($this->actionObject && $this->actionObject->run()) {
            $this->status = 'success';
        } else {
            $this->status = 'error';
        }
        $this->expires = gmdate("Y-m-d G:i:s", $this->actionObject->getExpireTime());
        $this->peak_memory = memory_get_peak_usage();
        $this->ended = gmdate("Y-m-d G:i:s");

        return $this->save();
    }

    /**
     * [[@doctodo method_description:clearResult]].
     *
     * @return [[@doctodo return_type:clearResult]] [[@doctodo return_description:clearResult]]
     */
    public function clearResult()
    {
        $object = $this->actionObject;
        if (!empty($object)) {
            return $object->clearResult();
        }

        return true;
    }

    /**
     * [[@doctodo method_description:dismiss]].
     *
     * @param boolean $changeExpires [[@doctodo param_description:changeExpires]] [optional]
     *
     * @return [[@doctodo return_type:dismiss]] [[@doctodo return_description:dismiss]]
     */
    public function dismiss($changeExpires = true)
    {
        if ($changeExpires) {
            $this->expires = gmdate("Y-m-d G:i:s");
        }
        if ($this->clearResult()) {
            $this->status = 'cleared';
        }

        return $this->save();
    }

    /**
     * [[@doctodo method_description:cancel]].
     *
     * @return [[@doctodo return_type:cancel]] [[@doctodo return_description:cancel]]
     */
    public function cancel()
    {
        if ($this->status !== 'queued') {
            return false;
        }
        $object = $this->actionObject;
        if (!empty($object) && !$object->cancel()) {
            return false;
        }

        return $this->delete();
    }
    /**
     * [[@doctodo method_description:bumpExpires]].
     *
     * @param [[@doctodo param_type:timeShift]] $timeShift [[@doctodo param_description:timeShift]]
     *
     * @return [[@doctodo return_type:bumpExpires]] [[@doctodo return_description:bumpExpires]]
     */
    public function bumpExpires($timeShift)
    {
        $this->expires = gmdate("Y-m-d G:i:s", strtotime($timeShift));

        return $this->save();
    }
}
