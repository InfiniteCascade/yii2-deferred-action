<?php


namespace infinite\deferred\controllers;

use Yii;
use infinite\helpers\Html;


class DefaultController extends \infinite\web\Controller
{
    public function actionNavPackage()
    {
        $navPackage = Yii::$app->getModule('deferredAction')->navPackage();
        Yii::$app->response->data = $navPackage;
    }
}
