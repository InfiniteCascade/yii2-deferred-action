<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\deferred\components;

/**
 * AssetBundle [[@doctodo class_description:canis\deferred\components\AssetBundle]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class AssetBundle extends \canis\web\assetBundles\AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@canis/deferred/assets';
    /**
     * @inheritdoc
     */
    public $css = ['css/canis.deferred-action.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/canis.deferred-action.js',
        'js/canis.deferred-action-log.js',
        // 'js/canis.deferred-action-interaction.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
        'canis\web\assetBundles\UnderscoreAsset',
        'canis\web\assetBundles\FontAwesomeAsset',
        'canis\web\assetBundles\AjaxFormAsset',
        'canis\web\assetBundles\CanisAssetsLibAsset',
    ];
}
