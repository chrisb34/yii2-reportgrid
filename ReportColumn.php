<?php
/**

 * 
 *
 * @author Chris Backhouse <support@chris-backhouse.com>
 * @package DataColumn
 * @licence: Open Source
 * @url: https://github.com/chrisb34/yii2-reportgrid
 * @since 0.1
 */

namespace common\components\ReportGrid;



use yii\helpers\Html;
use yii\base\InvalidConfigException;

class ReportColumn extends \yii\grid\DataColumn {

    /**
     * @var boolean/array Sub total column on which to break
     */
    public $subTotalOn = false;
    
    /**
     * @var boolean/array Sub total parameters
     */
    public $subTotal = false;
     
    /**
     * @var boolean/array page total parameters
     */
    public $pageTotal = false;
    

    /**
     * @var boolean whether the column is hidden from display. This is different than the `visible` property, in the
     * sense, that the column is rendered, but hidden from display. This will allow you to still export the column
     * using the export function.
     */
    public $hidden;

    /**
     * @var boolean|array whether the column is hidden in export output. If set to boolean `true`, it will hide the
     * column for all export formats. If set as an array, it will accept the list of GridView export `formats` and
     * hide output only for them.
     */
    public $hiddenFromExport = false;
    

    /**
     * @var string the horizontal alignment of each column. Should be one of [[self::ALIGN_LEFT]],
     * [[self::ALIGN_RIGHT]], or [[self::ALIGN_CENTER]].
     */
    public $hAlign;
    
    /**
     * @var string the vertical alignment of each column. Should be one of [[self::ALIGN_TOP]],
     * [[self::ALIGN_BOTTOM]], or [[self::ALIGN_MIDDLE]].
     */
    public $vAlign;
    
    /**
     * @var boolean whether to force no wrapping on all table cells in the column
     * @see http://www.w3schools.com/cssref/pr_text_white-space.asp
     */
    public $noWrap = false;
    
    
    /**
     * @var string the width of each column (matches the CSS width property).
     * @see http://www.w3schools.com/cssref/pr_dim_width.asp
     */
    public $width;
    
    
    /**
     * Horizontal **right** alignment for grid cells
     */
    const ALIGN_RIGHT = 'right';
    /**
     * Horizontal **center** alignment for grid cells
     */
    const ALIGN_CENTER = 'center';
    /**
     * Horizontal **left** alignment for grid cells
     */
    const ALIGN_LEFT = 'left';
    /**
     * Vertical **top** alignment for grid cells
     */
    const ALIGN_TOP = 'top';
    /**
     * Vertical **middle** alignment for grid cells
     */
    const ALIGN_MIDDLE = 'middle';
    /**
     * Vertical **bottom** alignment for grid cells
     */
    const ALIGN_BOTTOM = 'bottom';
    /**
     * CSS to apply to prevent wrapping of grid cell data
     */
    const NOWRAP = 'cb-nowrap';
    

    const TOTAL_BREAKDOWN = 1;

    public function init()
    {
        parent::init();
         
        $this->parseFormat();
    }
    
    public function format($value)
    {
        if ( !empty( $this->subTotal['format']) )
            $format = $this->subTotal['format'];
        elseif (!empty($this->format))
            $format=$this->format;
        else $format='Text';
            
        return $this->grid->formatter->format($value, $format);
    }


    public function renderHeaderCell()
    {
        $options = $this->headerOptions;
        
        $options = $this->parseOptions($options);
        return Html::tag('th', $this->renderHeaderCellContent(), $options);
    }
    
    /**
     * @inheritdoc
     */
    public function renderDataCell($model, $key, $index)
    {
        $options = $this->fetchContentOptions($model, $key, $index);
        return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
    }


    public function renderTotalContent($break, $i, $value = Null)
    {
        $breakParams = $this->subTotal;
             
        $options = $this->fetchTotalOptions($break);
        $content = '';
        if ( !empty($breakParams) ) {
            if ( !empty($breakParams['totalMethod']) && $breakParams['totalMethod'] == self::TOTAL_BREAKDOWN )
            {
                $content = $this->renderTotalBreakdown($break, $i);
            } elseif ($value) {
                $content =  $this->format($value);
            } else {
                $content =  $this->format($this->grid->subTotal[$break][$i]);
            }
        }
        
        return Html::tag('td', $content, $options);
    }
    
    public function renderTotalBreakdown($break, $i)
    {
        //var_dump(["renderTotalBreakdown", $break, $i, $this->grid->subBreakdown]);
        //return;
        $content = '<table class="table table-striped">';
        foreach ($this->grid->subBreakdown[$break][$i] as $key=>$total)
        {
            $content .= '<tr><td>'.$key."</td><td>".$this->format($total).'</td></tr>';
        }
        $content .= '</table>';
        return $content;
    }
    /**
     * Parses and formats a grid column
     */
    protected function parseFormat()
    {
        if ($this->isValidAlignment()) {
            $class = "cb-align-{$this->hAlign}";
            Html::addCssClass($this->headerOptions, $class);
        }
        if ($this->noWrap) {
            Html::addCssClass($this->headerOptions, self::NOWRAP);
        }
        if ($this->isValidAlignment('vAlign')) {
            $class = "cb-align-{$this->vAlign}";
            Html::addCssClass($this->headerOptions, $class);
        }
        if (trim($this->width) != '') {
            Html::addCssStyle($this->headerOptions, "width:{$this->width};");
        }
    }
    
    /**
     * Check if the alignment provided is valid
     *
     * @param string $type the alignment type
     *
     * @return boolean
     */
    protected function isValidAlignment($type = 'hAlign')
    {
        if ($type === 'hAlign') {
            return (
                    $this->hAlign === self::ALIGN_LEFT ||
                    $this->hAlign === self::ALIGN_RIGHT ||
                    $this->hAlign === self::ALIGN_CENTER
                    );
        } elseif ($type = 'vAlign') {
            return (
                    $this->vAlign === self::ALIGN_TOP ||
                    $this->vAlign === self::ALIGN_MIDDLE ||
                    $this->vAlign === self::ALIGN_BOTTOM
                    );
        }
        return false;
    }
    protected function fetchContentOptions($model, $key, $index)
    {
        if ($this->contentOptions instanceof \Closure) {
            $options = call_user_func($this->contentOptions, $model, $key, $index, $this);
        } else {
            $options = $this->contentOptions;
        }
        
        return $this->parseOptions($options);
    }
    protected function fetchTotalOptions($break)
    {
        $breakParams = $this->subTotal;
        
        $options = (!empty($breakParams['options'])) ? $breakParams['options'] : [];
        
        return $this->parseOptions($options);
    }
    protected function parseOptions($options)
    {
     
        if ($this->hidden === true) {
            Html::addCssClass($options, "cb-grid-hide");
        }
        if ($this->hiddenFromExport === true) {
            Html::addCssClass($options, "skip-export");
        }
        if (is_array($this->hiddenFromExport) && !empty($this->hiddenFromExport)) {
            $tag = 'skip-export-';
            $css = $tag . implode(" {$tag}", $this->hiddenFromExport);
            Html::addCssClass($options, $css);
        }
        if ($this->isValidAlignment()) {
            Html::addCssClass($options, "cb-align-{$this->hAlign}");
        }
        if ($this->noWrap) {
            Html::addCssClass($options, ReportColumn::NOWRAP);
        }
        if ($this->isValidAlignment('vAlign')) {
            Html::addCssClass($options, "cb-align-{$this->vAlign}");
        }
        if (trim($this->width) != '') {
            Html::addCssStyle($options, "width:{$this->width};");
        }
        $options['data-col-seq'] = array_search($this, $this->grid->columns);
        return $options;
    }
    public function getValue($value, $model, $key, $index, $break)
    {
        //if (!($value instanceof \Closure) && !is_array($value))
       //     echo ( $value );
        
        if ($value !== null) 
        {
            if ($value instanceof \Closure) {
                //echo 'closure';
                return  call_user_func($value, $model, $key, $index, $this, $break);
            } elseif (is_array( $value ))  {
                if (empty($value['value'])) 
                {
                    return 0;
                } else {
                    //go down another level
                    return $this->getValue( $value['value'], $model, $key, $index, $break );
                }
            } elseif ($value === true ) {
                // getDataCellValue always seemss to return a string and this confuses the sub-totalling
                $return = $this->getDataCellValue($model, $key, $index);
                if (is_numeric($return))
                    return (float) $return;
                else return $return;
            } else {
                //echo 'return value';
                return $value;
            }
        } elseif ($this->attribute !== null) {
            $att = $this->attribute;
            return $model->$att; //ArrayHelper::getValue($model, $this->attribute);
        }
        return null;
    }
}
