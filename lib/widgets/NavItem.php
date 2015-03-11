<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2014 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\deferred\widgets;

use teal\helpers\Html;
use Yii;

/**
 * NavItem [[@doctodo class_description:teal\deferred\widgets\NavItem]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class NavItem extends \yii\base\Widget
{
    /**
     * @inheritdoc
     */
    public static function widget($config = [])
    {
        $config['class'] = get_called_class();
        $widget = Yii::createObject($config);
        $view = $widget->getView();
        \teal\deferred\components\AssetBundle::register($view);

        $package = Yii::$app->getModule('deferredAction')->navPackage();
        $visible = !empty($package['items']);
        $spanHtmlOptions = ['class' => 'menu-icon fa fa-gear', 'title' => 'Background Tasks'];
        if (!empty($package['running'])) {
            Html::addCssClass($spanHtmlOptions, 'fa-spin-slow');
        }
        $htmlOptions = [];
        $linkHtmlOptions = [];
        $htmlOptions['data-deferred-action'] = json_encode($package);
        if (!$visible) {
            Html::addCssClass($htmlOptions, 'hidden');
        }

        return ['label' => Html::tag('span', '', $spanHtmlOptions), 'url' => '#', 'options' => $htmlOptions, 'linkOptions' => $linkHtmlOptions];
    }
}
