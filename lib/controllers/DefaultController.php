<?php


namespace infinite\deferred\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use infinite\helpers\Html;
use infinite\deferred\models\DeferredAction;
use infinite\deferred\components\ServeableResultInterface;

class DefaultController extends \infinite\web\Controller
{
    public function actionNavPackage()
    {
        $navPackage = Yii::$app->getModule('deferredAction')->navPackage();
        Yii::$app->response->data = $navPackage;
    }
    public function actionDownload()
    {
    	if (!isset($_GET['id']) || !($deferredAction = DeferredAction::findMine()->andWhere(['id' => $_GET['id']])->one())) {
    		throw new NotFoundHttpException("Deferred action not found!");
    	}
    	$action = $deferredAction->actionObject;
    	if (!($action->result instanceof ServeableResultInterface)) {
    		throw new NotFoundHttpException("Deferred action does not have a serveable result");
    	}
    	$action->result->serve();
    }
}
