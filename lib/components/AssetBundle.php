<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\deferred\components;

/**
 * InfiniteAsset [@doctodo write class description for InfiniteAsset].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class AssetBundle extends \infinite\web\assetBundles\AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@infinite/deferred/assets';
    /**
     * @inheritdoc
     */
    public $css = ['css/infinite.deferred-action.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/infinite.deferred-action.js',
        'js/infinite.deferred-action-log.js',
        // 'js/infinite.deferred-action-interaction.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
        'infinite\web\assetBundles\UnderscoreAsset',
        'infinite\web\assetBundles\FontAwesomeAsset',
        'infinite\web\assetBundles\AjaxFormAsset',
        'infinite\web\assetBundles\InfiniteAsset',
    ];
}
