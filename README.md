
Yii2-reportgrid
===============
Yii-Framework extension for reporting with totals and sub-totals.  It supports export to CSV as well.  There is no limit to the number of sub-totalling levels that you can have. You can also defer a sub-total or total to be displayed at the bottom of the grid in the Footer.

Warning
-------
This is a development project and should not yet be relied on for production systems.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist chrisb34/yii2-reportgrid "*"
```

or add

```
"chrisb34/yii2-reportgrid": "*"
```

to the require section of your `composer.json` file.


Usage
-----

This extension is powerful reporting gridview.  Unlike other totalling girdviews, all the totalling and sub-totaliing is done within the grid widget.  this means that you sub-totals and totals can include closure functions and therefore reference other model attributes or relationships.

Once the extension is installed, use it like a normal gridview with a few extra options.

```
echo ReportGrid::widget([
        'dataProvider' => $dataProvider,
        'controlBreak' => true,
        'totalRowOptions' => ['class'=>'total-row'],
        'exportCSV' => true, 
        'afterRow' => function() {},
        'totalRowOptions'
        'totalsHeader => true|false 
        'columns' => [        
           'attribute_name' => [
               'name' => optional: array key name
               'subTotal' => true/false/closure,
               'hAlign' => true
           ],
           'attribute_name' => [
               'name' => optional: array key name,
               // cause a break at break_level on this attribute
               'subTotalOn' => (int) break_level,
               'subTotal' => [
                   // note break level on closure
                   // useful for headings on break levels
                   // eg: function($model, $key, $index, $widget, $break) { if ($break==0) return 'Report Totals' ; 
                   //                                                       elseif ($break==1) return 'Break 1 Totals'; .... }
                   'value' => string|attribute name|closure ~ function($model, $key, $index, $widget, $break) {}
                   'breakValue' => string|attribute name|closure ~ function($model, $key, $index, $widget, $break) {}
                   'showOnBreak => (int) break level,
                   'hideOnBreak => (int) break level,
                   'format' =>
                   'totalMethod' => ReportColumn::TOTAL_BREAKDOWN,
                   'totalOn' => string|attribute name|closure ~ function($model, $key, $index, $widget, $break) { return $model->attribute;  },     
               ]
           ],
       ]
      ...
    ]);
```
    
You can see working demos at: [not yet available]
