<?php
use infinite\helpers\Html;

echo Html::beginTag('div', ['class' => 'row deferrerd-action-log']);

// Details
echo Html::beginTag('div', ['class' => 'col-sm-5']);
echo Html::beginTag('div', ['class' => 'panel panel-default']);
echo Html::beginTag('div', ['class' => 'panel-heading']);
echo Html::tag('div', 'Details', ['class' => 'panel-title']);
echo Html::endTag('div'); // panel-heading

echo Html::beginTag('div', ['class' => 'panel-body']);
echo Html::beginTag('div', ['class' => 'list-group']);
$details = [];
$details[] = [
	'icon' => 'fa fa-play',
	'label' => 'Date Started',
	'value' => date("F d, Y g:i:sa", strtotime($deferredAction->started))
];
$details[] = [
	'icon' => 'fa fa-stop',
	'label' => 'Date Ended',
	'value' => date("F d, Y g:i:sa", strtotime($deferredAction->ended))
];
$details[] = [
	'icon' => 'fa fa-clock-o',
	'label' => 'Duration',
	'value' => $deferredAction->niceDuration
];
$details[] = [
	'icon' => 'fa fa-tachometer',
	'label' => 'Peak Memory',
	'value' => $deferredAction->peakMemory
];
foreach ($details as $detail) {
	echo Html::beginTag('div', ['class' => 'list-group-item row', 'title' => $detail['label']]);
	echo Html::tag('div', '', ['class' => $detail['icon'] .' pull-left detail-icon']);
	echo Html::tag('div', $detail['value'], ['class' => 'pull-left detail-value']);
	echo Html::endTag('div');
}
echo Html::endTag('div'); // list-group
echo Html::endTag('div'); // panel-body

echo Html::endTag('div'); // panel panel-default
echo Html::endTag('div'); //col-sm-6


// Messages
echo Html::beginTag('div', ['class' => 'col-sm-7']);
echo Html::beginTag('div', ['class' => 'panel panel-default']);
echo Html::beginTag('div', ['class' => 'panel-heading']);
echo Html::tag('div', 'Messages', ['class' => 'panel-title']);
echo Html::endTag('div'); // panel-heading

echo Html::beginTag('div', ['class' => 'panel-body']);
echo Html::beginTag('div', ['class' => 'list-group']);
foreach ($action->result->messages as $message) {
	$messageOptions = ['class' => 'list-group-item'];
	if (!empty($message['data'])) {
		Html::addCssClass($messageOptions, 'expandable');
	}
	switch($message['level']) {
		case '_e':
			Html::addCssClass($messageOptions, 'list-group-item-danger');
		break;
		case '_w':
			Html::addCssClass($messageOptions, 'list-group-item-warning');
		break;
		default:
			Html::addCssClass($messageOptions, 'list-group-item-info');
		break;
	}
	echo Html::beginTag('div', $messageOptions);
	echo Html::tag('div', $message['message'], ['class' => '']);
	if (!empty($message['data'])) {
		echo Html::tag('code', print_r($message['data'], true), ['class' => 'expanded-only log-data-output preformatted']);
	}
	echo Html::endTag('div');
}
echo Html::endTag('div'); // list-group
echo Html::endTag('div'); // panel-body

echo Html::endTag('div'); // panel panel-default
echo Html::endTag('div'); //col-sm-6