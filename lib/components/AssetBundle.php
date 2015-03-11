<?php
/**
 * @link http://www.tealcascade.com/
 *
 * @copyright Copyright (c) 2014 Teal Software
 * @license http://www.tealcascade.com/license/
 */

namespace teal\deferred\components;

/**
 * AssetBundle [[@doctodo class_description:teal\deferred\components\AssetBundle]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class AssetBundle extends \teal\web\assetBundles\AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@teal/deferred/assets';
    /**
     * @inheritdoc
     */
    public $css = ['css/teal.deferred-action.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/teal.deferred-action.js',
        'js/teal.deferred-action-log.js',
        // 'js/teal.deferred-action-interaction.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
        'teal\web\assetBundles\UnderscoreAsset',
        'teal\web\assetBundles\FontAwesomeAsset',
        'teal\web\assetBundles\AjaxFormAsset',
        'teal\web\assetBundles\TealAsset',
    ];
}
