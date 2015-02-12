<?php

namespace infinite\deferred\models;

use Yii;
use infinite\helpers\Date;
use infinite\helpers\StringHelper;

/**
 * This is the model class for table "deferred_action".
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
 *
 * @property User $user
 */
class DeferredAction extends \infinite\db\ActiveRecord
{
    protected $_actionObject;

    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_VALIDATE, [$this, 'serializeAction']);
    }

    public function serializeAction()
    {
        if (isset($this->_actionObject)) {
            $this->action = serialize($this->_actionObject);
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
            [['action', 'session_id'], 'required'],
            [['action', 'status'], 'string'],
            [['started', 'ended', 'expires', 'created', 'modified'], 'safe'],
            [['user_id'], 'string', 'max' => 36],
            [['session_id'], 'string', 'max' => 40]
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
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

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

    public function setActionObject($ao)
    {
        $this->_actionObject = $ao;
    }

    public function getActionObject()
    {
        if (!isset($this->_actionObject) && !empty($this->action)) {
            $this->_actionObject = unserialize($this->action);
            $this->_actionObject->model = $this;
        }
        return $this->_actionObject;
    }

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

    public function getNiceDuration()
    {
        return Date::niceDuration($this->duration);
    }

    public function getPeakMemory()
    {
        if (empty($this->peak_memory)) {
            return '0b';
        }
        return StringHelper::humanFilesize($this->peak_memory);
    }

    public function getDuration()
    {
        $startTime = isset($this->started) ? strtotime($this->started) : strtotime($this->created);
        $endTime = isset($this->ended) ? strtotime($this->ended) : time();
        return $endTime - $startTime;
    }

    public function run()
    {
        $this->status = 'running';
        $this->started = date("Y-m-d G:i:s");
        $this->save();
        if ($this->actionObject && $this->actionObject->run()) {
            $this->status = 'ready';
            $this->expires = date("Y-m-d G:i:s", $this->actionObject->getExpireTime());
        } else {
            $this->expires = date("Y-m-d G:i:s");
            $this->status = 'error';
        }
        $this->peak_memory = memory_get_peak_usage();
        $this->ended = date("Y-m-d G:i:s");
        return $this->save();
    }

    public function clearResult()
    {
        $object = $this->actionObject;
        if (!empty($object)) {
            return $object->clearResult();
        }
        return true;
    }

    public function dismiss($changeExpires = true)
    {
        if ($changeExpires) {
            $this->expires = date("Y-m-d G:i:s");
        }
        if ($this->clearResult()) {
            $this->status = 'cleared';
        }
        return $this->save();
    }
    public function bumpExpires($timeShift)
    {
        $this->expires = date("Y-m-d G:i:s", strtotime($timeShift));
        return $this->save();
    }
}
