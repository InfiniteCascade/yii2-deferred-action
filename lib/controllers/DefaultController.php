<?php

namespace infinite\deferred\controllers;

use infinite\action\Interaction;
use infinite\deferred\components\LogResult;
use infinite\deferred\components\ServeableResultInterface;
use infinite\deferred\models\DeferredAction;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * DefaultController [[@doctodo class_description:infinite\deferred\controllers\DefaultController]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class DefaultController extends \infinite\web\Controller
{
    /**
     * [[@doctodo method_description:actionNavPackage]].
     */
    public function actionNavPackage()
    {
        $navPackage = Yii::$app->getModule('deferredAction')->navPackage();
        Yii::$app->response->data = $navPackage;
    }
    /**
     * [[@doctodo method_description:actionDownload]].
     *
     * @throws NotFoundHttpException [[@doctodo exception_description:NotFoundHttpException]]
     */
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

    /**
     * [[@doctodo method_description:actionViewLog]].
     *
     * @throws NotFoundHttpException [[@doctodo exception_description:NotFoundHttpException]]
     * @return [[@doctodo return_type:actionViewLog]] [[@doctodo return_description:actionViewLog]]
     *
     */
    public function actionViewLog()
    {
        if (!isset($_GET['id']) || !($deferredAction = DeferredAction::findMine()->andWhere(['id' => $_GET['id']])->one())) {
            throw new NotFoundHttpException("Deferred action not found!");
        }
        $action = $deferredAction->actionObject;
        if (!($action->result instanceof LogResult)) {
            throw new NotFoundHttpException("Deferred action does not have a serveable result");
        }
        $this->params['deferredAction'] = $deferredAction;
        $this->params['action'] = $action;
        if (!empty($_GET['package'])) {
            Yii::$app->response->data = $deferredAction->package(true);

            return;
        }
        Yii::$app->response->task = 'message';
        Yii::$app->response->taskOptions = ['title' => $action->descriptor . ' on ' . date("F d, Y g:i:sa", strtotime($deferredAction->created)), 'modalClass' => 'modal-xl'];
        Yii::$app->response->view = 'viewLog';
    }

    /**
     * [[@doctodo method_description:actionCancel]].
     *
     * @throws NotFoundHttpException [[@doctodo exception_description:NotFoundHttpException]]
     * @return [[@doctodo return_type:actionCancel]] [[@doctodo return_description:actionCancel]]
     *
     */
    public function actionCancel()
    {
        if (!isset($_GET['id']) || !($deferredAction = DeferredAction::findMine()->andWhere(['id' => $_GET['id']])->one())) {
            throw new NotFoundHttpException("Deferred action not found!");
        }
        $action = $deferredAction->actionObject;
        if ($deferredAction->status === 'queued') {
            if ($deferredAction->cancel()) {
                Yii::$app->response->task = 'message';
                Yii::$app->response->content = 'Task was canceled!';
                Yii::$app->response->taskSet = [['task' => 'deferredAction']];
                Yii::$app->response->taskOptions = ['state' => 'success'];

                return;
            }
        }
        Yii::$app->response->task = 'message';
        Yii::$app->response->content = 'Task could not be canceled.';
        Yii::$app->response->taskOptions = ['state' => 'danger'];
    }

    /**
     * [[@doctodo method_description:actionDismiss]].
     *
     * @throws NotFoundHttpException [[@doctodo exception_description:NotFoundHttpException]]
     * @return [[@doctodo return_type:actionDismiss]] [[@doctodo return_description:actionDismiss]]
     *
     */
    public function actionDismiss()
    {
        if (!isset($_GET['id']) || !($deferredAction = DeferredAction::findMine()->andWhere(['id' => $_GET['id']])->one())) {
            throw new NotFoundHttpException("Deferred action not found!");
        }
        $action = $deferredAction->actionObject;
        if (in_array($deferredAction->status, ['success', 'error'])) {
            if ($deferredAction->dismiss()) {
                // Yii::$app->response->task = 'message';
                // Yii::$app->response->content = 'Task was dismissed!';
                Yii::$app->response->taskSet = [['task' => 'deferredAction']];
                //Yii::$app->response->taskOptions = ['state' => 'warning'];
                return;
            }
        }
        Yii::$app->response->task = 'message';
        Yii::$app->response->content = 'Task could not be dismissed.';
        Yii::$app->response->taskOptions = ['state' => 'danger'];
    }

    /**
     * [[@doctodo method_description:actionResolveInteraction]].
     *
     * @return [[@doctodo return_type:actionResolveInteraction]] [[@doctodo return_description:actionResolveInteraction]]
     */
    public function actionResolveInteraction()
    {
        if (!isset($_POST['id']) || !isset($_POST['value']) || !Interaction::saveResolution($_POST['id'], $_POST['value'])) {
            Yii::$app->response->task = 'message';
            Yii::$app->response->content = 'Resolution could not be saved';
            Yii::$app->response->taskOptions = ['state' => 'danger'];

            return;
        }
        Yii::$app->response->success = 'Success';
    }
}
