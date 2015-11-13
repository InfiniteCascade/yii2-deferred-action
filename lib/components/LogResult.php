<?php
namespace canis\deferred\components;

use canis\helpers\Date;
use canis\helpers\StringHelper;
use canis\messageStore\MessageStoreInterface;
use canis\messageStore\MessageStoreTrait;
use yii\helpers\Url;

/**
 * LogResult [[@doctodo class_description:canis\deferred\components\LogResult]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class LogResult 
    extends Result
    implements MessageStoreInterface
{
    use MessageStoreTrait;

    /**
     * @var [[@doctodo var_type:_total]] [[@doctodo var_description:_total]]
     */
    protected $_total = false;
    /**
     * @var [[@doctodo var_type:_completed]] [[@doctodo var_description:_completed]]
     */
    protected $_completed = 0;


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

    public function afterAddMessage()
    {
        $this->save();
        return true;
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
        if (!$details) {
            unset($package['messages']);
        }
        return $package;
    }
}
