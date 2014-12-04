<?php
namespace infinite\deferred\components;

use Yii;
use yii\web\NotFoundHttpException;
use yii\helpers\FileHelper;
use yii\helpers\Url;

abstract class ServeableResult extends Result implements ServeableResultInterface
{
	protected $_mimeType;
	protected $_niceFilename;

	public function serve()
	{
		if (!$this->serveableFilePath) {
			throw new NotFoundHttpException("Background action result file is not found.");
		}
		Yii::$app->response->sendFile($this->serveableFilePath, $this->niceFilename, ['mimeType' => $this->mimeType]);
	}

	public function package()
	{
		$package = parent::package();
		if ($this->serveableFilePath) {
			$package['download'] = Url::to(['/deferredAction/download', 'id' => $this->action->model->id]);
		}
		return $package;
	}

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

	public function setMimeType($mimeType)
	{
		$this->_mimeType = $mimeType;
		return $this;
	}

	public function getNiceFilename()
	{
		if (!isset($this->_niceFilename)) {
			$this->_niceFilename = date("Y-m-d-result");
		}
		return $this->_niceFilename;
	}

	public function setNiceFilename($filename)
	{
		$this->_niceFilename = $filename;
		return $this;
	}
}
?>