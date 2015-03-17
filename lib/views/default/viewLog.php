<?php
use canis\helpers\Html;
use yii\helpers\Url;

$params = [];
$params['data'] = $deferredAction->package(true);
$params['config'] = ['url' => Url::to(['view-log', 'id' => $deferredAction->id, 'package' => 1])];
echo Html::tag('div', '', ['data-deferred-action-log' => json_encode($params)]);

return;
