<?php

/**
 * @package   yii2-reportgrid
 * @author    Chris Backhouse
 * @copyright Copyright &copy; Chris Backhouse , 2014 - 2016
 * @version   1.0.0
 */

namespace common\components\ReportGrid;

use yii\web\AssetBundle;

/**
 * Asset bundle for the styling of the [[ReportGrid]] widget.
 *
 * @author Chris Backhouse <support@chris-backhouse.com>
 * @since 1.0
 */
class ReportViewAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/assets';
        $this->css = ['css/main.css'];
        $this->js = ['js/csv_export.js'];
        parent::init();
    }
}
