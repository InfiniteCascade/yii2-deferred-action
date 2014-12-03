<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\deferred\widgets;

use Yii;
use infinite\helpers\Html;


/**
 * ActiveField [@doctodo write class description for ActiveField]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class NavItem extends \yii\base\Widget
{
    public static function widget($config = [])
    {
        $config['class'] = get_called_class();
        $widget = Yii::createObject($config);
        $view = $widget->getView();
        \infinite\deferred\components\AssetBundle::register($view);
        
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
