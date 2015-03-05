<?php
namespace infinite\deferred\components;

use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * ServeableResult [[@doctodo class_description:infinite\deferred\components\ServeableResult]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class ServeableResult extends Result implements ServeableResultInterface
{
    /**
     * @var [[@doctodo var_type:_mimeType]] [[@doctodo var_description:_mimeType]]
     */
    protected $_mimeType;
    /**
     * @var [[@doctodo var_type:_niceFilename]] [[@doctodo var_description:_niceFilename]]
     */
    protected $_niceFilename;

    /**
     * [[@doctodo method_description:serve]].
     *
     * @throws NotFoundHttpException [[@doctodo exception_description:NotFoundHttpException]]
     */
    public function serve()
    {
        if (!$this->serveableFilePath) {
            throw new NotFoundHttpException("Background action result file is not found.");
        }
        // $this->action->model->bumpExpires('+1 hour');
        Yii::$app->response->sendFile($this->serveableFilePath, $this->niceFilename, ['mimeType' => $this->mimeType]);
    }

    /**
     * @inheritdoc
     */
    public function package()
    {
        $package = parent::package();
        if ($this->serveableFilePath) {
            $package['actions'][] = ['label' => 'Download', 'url' => Url::to(['/deferredAction/download', 'id' => $this->action->model->id])];
        }

        return $package;
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        if ($this->serveableFilePath) {
            @unlink($this->serveableFilePath);
        }

        return true;
    }

    /**
     * Get mime type.
     *
     * @return [[@doctodo return_type:getMimeType]] [[@doctodo return_description:getMimeType]]
     */
    public function getMimeType()
    {
        if (!isset($this->_mimeType)) {
            $this->_mimeType = null;
            if ($this->servableFilePath) {
                $this->_mimeType = FileHelper::getMimeType($file);
            }
        }

        return $this->_mimeType;
    }

    /**
     * Set mime type.
     *
     * @return [[@doctodo return_type:setMimeType]] [[@doctodo return_description:setMimeType]]
     */
    public function setMimeType($mimeType)
    {
        $this->_mimeType = $mimeType;

        return $this;
    }

    /**
     * Get nice filename.
     *
     * @return [[@doctodo return_type:getNiceFilename]] [[@doctodo return_description:getNiceFilename]]
     */
    public function getNiceFilename()
    {
        if (!isset($this->_niceFilename)) {
            $this->_niceFilename = date("Y-m-d-result");
        }

        return $this->_niceFilename;
    }

    /**
     * Set nice filename.
     *
     * @return [[@doctodo return_type:setNiceFilename]] [[@doctodo return_description:setNiceFilename]]
     */
    public function setNiceFilename($filename)
    {
        $this->_niceFilename = $filename;

        return $this;
    }
}
