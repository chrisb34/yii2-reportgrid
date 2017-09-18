<?php
/**

 * 
 *
 * @author Chris Backhouse <support@chris-backhouse.com>
 * @package ReportGrid
 * @licence: Open Source
 * @url: https://github.com/chrisb34/yii2-reportgrid
 * @since 0.1
 */

namespace chrisb34\ReportGrid;

use yii\grid\GridView;
use yii\bootstrap\Html;
use yii\base\InvalidConfigException;

//use kartik\grid\GridView;

class ReportGrid extends GridView {

    /**
     *  [
     *      'controlBreak' => true,
            'totalRowOptions' => ['class'=>'total-row'],
            'exportCSV' => true, 
            'afterRow' =>
     *      'totalRowOptions'
     *      'totalsHeader => true|false 
     *    
     *      'attribute_name' => [
     *          'name' => optional: array key name
     *          'subTotal' => true/false/closure,
     *          'hAlign' => true
     *      ],
     *      'attribute_name' => [
     *          'name' => optional: array key name,
     *          // cause a break at break_level on this attribute
     *          'subTotalOn' => (int) break_level,
     *          'subTotal' => [
     *              // note break level on closure
     *              // useful for headings on break levels
     *              // eg: function($model, $key, $index, $widget, $break) { if ($break==0) return 'Report Totals' ; elseif ($break==1) return 'Break 1 Totals'; .... }
     *              'value' => string|attribute name|closure ~ function($model, $key, $index, $widget, $break) {}
     *              'breakValue' => string|attribute name|closure ~ function($model, $key, $index, $widget, $break) {}
     *              'showOnBreak => (int) break level,
     *              'hideOnBreak => (int) break level,
     *              'format' =>
     *              'totalMethod' => ReportColumn::TOTAL_BREAKDOWN,
     *              'totalOn' => string|attribute name|closure ~ function($model, $key, $index, $widget, $break) { return $model->attribute;  },
 *                  'total'  
     *          ]
     *      ],
     *      
     * ...
     * ]
     */
    
    /**
     *  @var array whether to perform control breaks, array of parameters in break order 
     */
    public $totalRowOptions = [];
    
    /**
     *  @var array whether to perform control breaks, array of parameters in break order 
     */
    public $totalsHeader;
    
    public $controlBreak = true;
    public $subTotal;
    public $subBreakdown;
    public $exportCSV;
    public $pageSummary = [];
    
    private $_rows = [];
    private $_break = [];
    private $_zeroTotals;
    private $_cells = [];
    
    public $dataColumnClass = 'chrisb34\ReportGrid\ReportColumn';

    public function init()
    {
        parent::init();
        if ($this->formatter === null) {
            $this->formatter = Yii::$app->getFormatter();
        } elseif (is_array($this->formatter)) {
            $this->formatter = Yii::createObject($this->formatter);
        }
        $this->dataProvider->pagination=false;
        //var_dump( $this->formatter );
        //if (!$this->formatter instanceof \Formatter) {
        //    throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
        //}
        //ini_set('xdebug.var_display_max_depth', 5);
        //ini_set('xdebug.var_display_max_children', 256);
        //ini_set('xdebug.var_display_max_data', 1024);

        $this->initStyling();
        
    }
    
    /**
     * Runs the widget.
     */
    public function run()
    {
        $view = $this->getView();
        ReportViewAsset::register($view);
        if ( $this->exportCSV )
        {
            $this->initExport();
        }
        $this->initTotals();
        parent::run();
    }
    
    /**
     * Renders a table row with the given data model and key.
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data model among the model array returned by [[dataProvider]].
     * @return string the rendering result
     */
    public function renderTableRow($model, $key, $index)
    {
        $this->_cells = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            $this->_cells[] = $column->renderDataCell($model, $key, $index);
        }
        if ($this->rowOptions instanceof Closure) {
            $options = call_user_func($this->rowOptions, $model, $key, $index, $this);
        } else {
            $options = $this->rowOptions;
        }
        $options['data-key'] = is_array($key) ? json_encode($key) : (string) $key;
    
        return Html::tag('tr', implode('', $this->_cells), $options);
    }
    
    /**
     * Renders the table body.
     * @return string the rendering result.
     */
public function renderTableBody()
    {
        //var_dump( $this->columns );
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();
        $this->_rows = [];
        $count = count($models)-1;
       // var_dump( $this->_break );
        
        for ($index=0; $index<=$count; $index++)
        {
            $model = $models[$index];
            $key = $keys[$index];
            if ($this->beforeRow !== null) {
                $row = call_user_func($this->beforeRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $this->_rows[] = $row;
                }
            }

            $thisRow = $this->renderTableRow($model, $key, $index);
    
            if ($this->afterRow !== null) {
                $afterRow = call_user_func($this->afterRow, $model, $key, $index, $this);
            }
        
            $this->_rows[] = $thisRow;
            if (!empty($afterRow)) {
                $this->_rows[] = $afterRow;
            }
        
            $thisCells = $this->_cells;
            if ( $index < $count )
                $nextRow = $this->renderTableRow($models[$index+1], $key, $index);
            else {
                $nextRow = [];
                $this->_cells = $this->blankCells();
            }
            
            if (!empty($this->controlBreak)) {
                $this->checkControlBreak( $thisCells, $model, $key, $index);
            }
            
            //$this->_previousRow = $thisRow;
            //var_dump( $model );
             
        }

        if ($this->totalsHeader)
            $this->renderTableHeader();
        
        // print page totals
        if ( !empty($models) )
            $this->_rows[] = $this->printSubTotal(0, $model, $key, $index);
        
        if ( !empty($this->pageSummary) && !empty($this->_rows) )
            $this->renderTotalSummary();
        
        //var_dump( $this->pageSummary );    
        
        if ( empty($this->_rows) ) {
            $colspan = count($this->columns);
    
            return "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        } else {
            return "<tbody>\n" . implode("\n", $this->_rows) . "\n</tbody>";
        }
    }
    
    // 
    // CONTROL BREAKS
    //
    
    public function checkControlBreak( $thisRow, $model, $key, $index ) 
    {
        
        $row = '<tr><td colspan="9">';
        $break = false;
        
        $row.="add totals. <br>";
        $this->addTotals ($model, $key, $index);
            
        // foreach control break
        //  $break: break level 1,2,3,4
        //  $i: column used to detect break
        foreach ( $this->_break as $break => $i )
        {
            $column = $this->columns[$i];
                 
            // if closure?
            if ( $thisRow[$i] !=  $this->_cells[$i] )
            {   // do control break 
                $this->doControlBreak( $break, $i, $column, $model, $key, $index );
                break;
            } 
        }
        return '';//$row."</td></tr>";
    }

    // Found a break so go back to the lowest(highest number) level and execute break
    // - print totals
    // - roll-up totals to next level
    // - reset current level
    public function doControlBreak( $break, $i, $column, $model, $key, $index)
    {
        for ($breakLevel = count($this->_break); $breakLevel >= $break; $breakLevel-- )
        {

            $this->_rows[]=$this->printSubTotal($breakLevel, $model, $key, $index);
            //set to zero
            $this->rollUpTotals( $breakLevel, $model, $key, $index );
            
            $this->resetSubTotal($breakLevel);
            
        }
    }
    
    public function addTotals( $model, $key, $index )
    {
        $break = ( !empty($this->_break) ) ? max(array_keys($this->_break)) : 0;

        foreach ( $this->columns as $s => $sColumn )
        {
            // are totals required for this column, use zeroTotals to check
            if ( array_key_exists( $s, $this->_zeroTotals ) )
            {
                $this->addSubTotal($break, $s, $sColumn,  $model, $key, $index);
            }
            // only add page totals once
            //if ($break==1 && !empty($sColumn->pageTotal) )
            //    $this->addSubTotal(0, $s, $sColumn,  $model, $key, $index);
        }
        
    }

    public function addSubTotal($break, $s, $sColumn, $model, $key, $index)
    {

        $breakParams = $sColumn->subTotal;
        
        if ( $breakParams == false )
            return;
            
            // page total - take previous control break
            if ($break==0 && $breakParams===true && !empty($this->_break))
                $value = $this->subTotal[1][$s];
            else
                $value = $sColumn->getValue($breakParams, $model, $key, $index, $break);
            
            //if (is_string($value)) {
            if ($sColumn->format == 'text' || $sColumn->format=='html')
            {
                $this->subTotal[$break][$s] = $value;
                return;
            }
        
            //if ( empty($this->subTotal[$break][$s]) ) $this->subTotal[$break][$s] = $value;
            $this->subTotal[$break][$s] += $value;
            
            if ( !empty( $breakParams['totalMethod'] ) &&  $breakParams['totalMethod']==ReportColumn::TOTAL_BREAKDOWN )
            {
                $on = $sColumn->getValue($breakParams['totalOn'], $model, $key, $index, $break);
                
                if ( empty($this->subBreakdown[$break][$s][$on]) ) $this->subBreakdown[$break][$s][$on] = $value;
                else $this->subBreakdown[$break][$s][$on] += $value;                
            }
            
            // overwrite summed value with TOTAL parameter but not for page totals
            if ( !empty($breakParams['total']) ) 
            {
                $total = $sColumn->getValue($breakParams['total'], $model, $key, $index, $break);
                if ( $break == 0)
                    $this->subTotal[$break][$s] += $total;
                else 
                    $this->subTotal[$break][$s] = $total;
            }
            // overwrite summed value with TOTAL-SUM parameter but not for page totals
            if ( !empty($breakParams['totalSum']) )
            {
                $total = $sColumn->getValue($breakParams['total'], $model, $key, $index, $break);
                if ( $break == 0)
                    $this->subTotal[$break][$s] += $total;
                else {
                    $this->subTotal[$break][$s] = 0;
                    foreach ($breakParams['totalSum'] as $sumColumn)
                    {
                        $this->subTotal[$break][$s] += $this->subTotal[$break][$sumColumn];
                    }
                }
            }
            
    }

    public function rollUpTotals( $break, $model, $key, $index )
    {
        foreach ( $this->columns as $s => $sColumn )
        {
            // are totals required for this column, use zeroTotals to check
            if ( array_key_exists( $s, $this->_zeroTotals ) )
            {
                // use breakValue, if exists, otherwise use the previous level total
                if ( !empty($sColumn->subTotal['breakValue']) )
                {
                    $this->subTotal[$break-1][$s] =  $sColumn->getValue($sColumn->subTotal['breakValue'], $model, $key, $index, $break-1);
                } elseif ( array_key_exists($s,$this->subTotal[$break-1]) ) {                
                    $this->subTotal[$break-1][$s] += $this->subTotal[$break][$s];
                }
            }
            if ( !empty( $sColumn->subTotal['totalMethod'] ) &&  $sColumn->subTotal['totalMethod']==ReportColumn::TOTAL_BREAKDOWN )
            {
                $this->rollUpBreakdown($break, $s);
            }
            // only add page totals once
            //if ($break==1 && !empty($sColumn->pageTotal) )
            //    $this->addSubTotal(0, $s, $sColumn,  $model, $key, $index);
        }
    
    }
    
    // sum the value of identical keys of breakdown into next break breakdown
    public function rollUpBreakdown($break, $s)
    {
        $a1 = $this->subBreakdown[$break][$s];
        if (empty($this->subBreakdown[$break-1]))
        {
            $this->subBreakdown[$break-1][$s]=$this->subBreakdown[$break][$s];
            return;
        } else {
            $a2 = $this->subBreakdown[$break-1][$s];
        }
        $sums = [];
        foreach (array_keys($a1 + $a2) as $key) {
            $sums[$key] = (isset($a1[$key]) ? $a1[$key] : 0) + (isset($a2[$key]) ? $a2[$key] : 0);
        }
        $this->subBreakdown[$break-1][$s]=$sums;
    }

    
    public function printSubTotal( $break,  $model, $key, $index )
    {
        $colspan = count($this->columns);
        $breakClass = "cb-sub-total cb-sub-total-".$break;
        
        $options = $this->totalRowOptions;
        
        Html::addCssClass($options, $breakClass);
        
        $cells = [];
        foreach ( $this->columns as $i => $column )
        {   
            // todo: not sure about blanking the cells here rather than in renderTotalContent
            $breakParams = $column->subTotal;
            if ( !empty($breakParams['hideOnBreak']) && $breakParams['hideOnBreak'] == $break )
                    $breakParams['showSummary']='';
            
            if (!empty($breakParams['breakValue'])) 
            {
                $value =  $column->getValue($breakParams['breakValue'],  $model, $key, $index , $break);
                $cells[$i] =  $column->renderTotalContent( $break, $i, $value, $model, $key, $index );
            } elseif ( !empty($column->subTotalOn) && $breakParams === true && $break != array_search($i, $this->_break)) {
                // default to empty cell for break cells other than the break cell, unless break parameters are specified.
                $cells[$i] =  Html::tag('td', $this->emptyCell, $options);
            } else {
                $value =  NULL;
                $cells[$i] =  $column->renderTotalContent( $break, $i, $value, $model, $key, $index );
                
            }
            
            if ( !empty($breakParams['showSummary']))
            {
                $this->saveSummary($column, $cells, $break, $i);
                $cells[$i]=Html::tag('td', $this->emptyCell, $options);
            }
            if ( !empty($breakParams['hideOnBreak']) && $break == $breakParams['hideOnBreak'] ||
                 !empty($breakParams['showOnBreak']) && $break != $breakParams['showOnBreak'] )
            {
                $cells[$i] = Html::tag('td', $this->emptyCell, $options);
            } 
            
        }
        
        return Html::tag('tr',  implode('', $cells), $options);
        
        return $return;
    }
    
    /**
     * Save the summary title | content
     * @param unknown $column
     * @param unknown $break
     * @param unknown $i
     */
    public function saveSummary( $column, &$cells, $break, $i )
    {
        $content = $cells[$i];
        if ( $break > 0 )
        {
            $breakCol = $this->_break[$break];
            $title = $cells[$breakCol];
            preg_match("/<td[^>]*>(.*?)<\\/td>/si", $title, $match);
            $title = $match[1];
            $title = Html::tag('td',$title.': '.$column->header);
            //$title = str_replace('cb-hide-grid','');
        } else {
            $title = Html::tag('td','Report Total: '.$column->header);
        }
        
        $options = $this->totalRowOptions;
        Html::addCssClass($options, "cb-summary");
        
        $colspan = count($this->columns)-1;
        $content = preg_replace('/(<td\b[^><]*)>/i', '$1 colspan="'.$colspan.'">', $content);
        
        $this->pageSummary[] = Html::tag('tr', $title.$content, $options);
        
    }
    
    public function renderTotalSummary()
    {
        $this->_rows = array_merge($this->_rows, $this->pageSummary);
    }
    
    //
    // INITIALIZE 
    //

    /**
     * Setup and initialize the sub-totals
     * Store the break order => column-id
     * @param
     * @return
     */
    public function initTotals()
    {
        $columns = $this->columns;
        //var_dump($columns);
        $this->_zeroTotals = [];
        foreach ($columns as $i => $column)
        {
            if ( !empty($column->subTotal) )
            {
                $this->_zeroTotals[$i] = 0;
            }
        }
    
        //$this->subTotal[0] = $this->_zeroTotals;
        foreach ($columns as $i => $column)
        {
            if ( !empty($column->subTotalOn) )
            {
                if ( is_int($column->subTotalOn) )
                {
                    $this->_break[$column->subTotalOn] = $i;
                    $this->subTotal[$column->subTotalOn] = $this->_zeroTotals;
                }
            }
        }
        
        foreach ($this->_break as $break => $i)
        {
            $this->subTotal[$break] = $this->_zeroTotals;
        }
        $this->subTotal[0] = $this->_zeroTotals;
        
    }
    
    
    public function resetSubTotal( $break )
    {

        $breaks = array_keys($this->_break);
        if ($break <= max($breaks))
        {
            for ( $b=max($breaks); $b>=$break ;$b--)
            {
                $this->subTotal[$b] = $this->_zeroTotals;    
                $this->subBreakdown[$b] = [];
                }
        }
    }
    
    public function blankCells()
    {
        $return = [];
        foreach ($this->columns as $column)
            $return[] = 'Blank Cell';
        return $return;
    }
    
    public function initStyling()
    {
        Html::addCssClass($this->tableOptions, 'cb-report-grid');
        
        Html::addCssClass($this->tableOptions, 'table');
   
    }
    
    public function initExport()
    {
        $button = Html::a('<span><i class="glyphicon glyphicon-share"></i> CSV</span>','#',['id'=>'cb-export-btn', 'class'=>'btn btn-default pull-right']);
        $this->summary .= $button;
    }

    
    
}