<?php
namespace canis\deferred\components;

/**
 * Result [[@doctodo class_description:canis\deferred\components\Result]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Result extends \yii\base\Component
{
    /**
     * @var [[@doctodo var_type:action]] [[@doctodo var_description:action]]
     */
    public $action;
    /**
     * @var [[@doctodo var_type:isSuccess]] [[@doctodo var_description:isSuccess]]
     */
    public $isSuccess = true;
    /**
     * @var [[@doctodo var_type:message]] [[@doctodo var_description:message]]
     */
    public $message;

    /**
     * Prepares object for serialization.
     *
     * @return [[@doctodo return_type:__sleep]] [[@doctodo return_description:__sleep]]
     */
    public function __sleep()
    {
        $keys = array_keys((array) $this);
        $bad = ["action"];
        foreach ($keys as $k => $key) {
            if (in_array($key, $bad)) {
                unset($keys[$k]);
            }
        }

        return $keys;
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
        $package = [];
        $package['isSuccess'] = $this->isSuccess;
        $package['message'] = $this->message;
        $package['actions'] = [];

        return $package;
    }

    /**
     * [[@doctodo method_description:clear]].
     *
     * @return [[@doctodo return_type:clear]] [[@doctodo return_description:clear]]
     */
    public function clear()
    {
        return true;
    }

    /**
     * [[@doctodo method_description:handleException]].
     *
     * @param Exception $e [[@doctodo param_description:e]]
     *
     * @return [[@doctodo return_type:handleException]] [[@doctodo return_description:handleException]]
     */
    public function handleException(\Exception $e)
    {
        return $e;
    }

    /**
     * [[@doctodo method_description:save]].
     *
     * @return [[@doctodo return_type:save]] [[@doctodo return_description:save]]
     */
    public function save()
    {
        if (empty($this->action)) {
            return true;
        }

        return $this->action->save();
    }
}
