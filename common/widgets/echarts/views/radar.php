<?php
/**
 * 雷达图（完成率）
 */
echo $this->render("_nav", [
    'boxId' => $boxId,
    'config' => $config,
    'themeJs' => $themeJs,
    'themeConfig' => $themeConfig,
]);

$jsonConfig = \yii\helpers\Json::encode($config);
Yii::$app->view->registerJs(<<<JS
    var boxId = "$boxId";
    echartsList[boxId] = echarts.init(document.getElementById(boxId + '-echarts'), "$themeJs");
    echartsListConfig[boxId] = jQuery.parseJSON('$jsonConfig');

    $('#'+ boxId +' div span').click(function () {
        if (!$(this).data('type')) return;
        $(this).parent().find('span').removeClass('orange');
        $(this).addClass('orange');
        var type = $(this).data('type');
        var start = $(this).attr('data-start');
        var end = $(this).attr('data-end');
        var boxId = $(this).parent().parent().attr('id');
        var config = echartsListConfig[boxId];

        $.ajax({
            type:"get",
            url: config.server,
            dataType: "json",
            data: {type:type, echarts_type: 'radar', echarts_start: start, echarts_end: end},
            success: function(result){
                var data = result.data;
                if (parseInt(result.code) === 200) {
                    echartsList[boxId].setOption({
                        tooltip: {},
                        legend: {data:[data.seriesData[0].name], bottom:0},
                        radar: {
                            indicator: data.indicator,
                            shape: 'polygon'
                        },
                        series: [{
                            name: data.seriesData[0].name,
                            type: 'radar',
                            data: data.seriesData[0].data
                        }]
                    }, true);
                } else {
                    rfWarning(result.message);
                }
            }
        });
    });

    $('#'+ boxId +' div span:first').trigger('click');
JS
) ?>