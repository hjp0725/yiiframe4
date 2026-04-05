<?php

use common\helpers\Url;

$this->title = Yii::t('app','指示板');
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>

<style>
    .info-box-number {
        font-size: 20px;
    }

    .info-box-content {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<div class="row">
    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i class="icon ion-person-stalker blue"></i> <?= $member??0 ?></span>
                <span class="info-box-text"><?=Yii::t('app','用户数');?></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>

    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i class="icon ion-card cyan"></i> <?= $behavior ?? 0 ?></span>
                <span class="info-box-text"><?=Yii::t('app','行为监控');?></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i class="icon ion-ios-pulse orange"></i> <?= $logCount ?? 0 ?></span>
                <span class="info-box-text"><?=Yii::t('app','全局日志');?></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i class="icon ion-arrow-graph-up-right red"></i> <?= $attachment ?? 0 ?></span>
                <span class="info-box-text"><?=Yii::t('app','资源文件');?></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i class="icon ion-ios-lightbulb-outline magenta"></i> <?= $attachmentSize ?? 0 ?>MiB</span>
                <span class="info-box-text"><?=Yii::t('app','附件大小');?></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i class="icon ion-ios-paper-outline purple"></i> <?= $mysql_size ?? 0 ?></span>
                <span class="info-box-text"><?=Yii::t('app','数据库大小');?></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-md-6 col-xs-12">
        <div class="box box-solid">
            <div class="box-header">
                <i class="fa fa-circle rf-circle" style="font-size: 8px"></i>
                <h3 class="box-title"><?=Yii::t('app','行为统计');?></h3>
            </div>
            <?= \common\widgets\echarts\Echarts::widget([
                'config' => [
                    'server' => Url::to(['login-count']),
                    'height' => '350px'
                ],
                'theme' => 'area-stack',
                'themeJs' => 'wonderland',
                'themeConfig' => [
                    'today' => '今天',
                    'yesterday' => '昨天',
                    // 'this7Day' => '最近7天',
                    // 'this30Day' => '最近30天',
                    'thisWeek' => '本周',
                    'thisMonth' => '本月',
                    'thisYear' => '本年',
                    'lastYear' => '去年',
                    'customData' => '自定义区间'
                 ],
            ]) ?>

            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
    <div class="col-md-3 col-xs-12">
        <div class="box box-solid">
            <div class="box-header">
                <i class="fa fa-circle rf-circle" style="font-size: 8px"></i>
                <h3 class="box-title"><?=Yii::t('app','日志统计');?></h3>
            </div>
           <?= \common\widgets\echarts\Echarts::widget([
                'config' => [
                    'server' => Url::to(['log-count']),
                    'height' => '350px',
                ],
                'theme' => 'wordcloud',
                'themeJs' => 'westeros',
                'themeConfig' => [
                        'all' => '全部',
                 ],
                
            ]) ?>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
    <div class="col-md-3 col-xs-12">
        <div class="box box-solid">
            <div class="box-header">
                <i class="fa fa-circle rf-circle" style="font-size: 8px"></i>
                <h3 class="box-title"><?=Yii::t('app','登陆统计');?></h3>
            </div>
            <?= \common\widgets\echarts\Echarts::widget([
                'config' => [
                    'server' => Url::to(['member-count']),
                    'height' => '350px'
                ],
                'theme' => 'pie',
                // 'themeJs' => 'wonderland',
                'themeConfig' => [
                        'all' => '全部',
                 ],
            ]) ?>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
    
</div>
