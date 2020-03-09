<?php

namespace App\Admin\Controllers;

use App\Model\AccountModel;
use App\Model\AccountStatisticModel;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class AccountStatisticController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '账户视频统计';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AccountStatisticModel());
        $grid->model()->orderBy('created_at', 'desc')->whereBetween('created_at',
            [Carbon::parse('2 days ago')->toDateString(), Carbon::parse('today')->toDateString()]);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('account_id', '账户名')->display(function ($id) {
            $account = AccountModel::where('id', $id)->first();
            if (!$account) {
                return '';
            }
            return $account->name;
        });
        $grid->column('recommend_count', '推荐量');
        $grid->column('comment_count', '评论量');
        $grid->column('view_count', '阅读/播放量');
        $grid->column('share_count', '分享量');
        $grid->column('collect_count', '收藏量');
        $grid->column('likes_count', '点赞量');
        $grid->column('created_at', '统计日');
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->between('created_at', '创建时间')->datetime();
        });
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });
        return $grid;
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        $record = AccountStatisticModel::where('id', $id)->first();
        $account_id = $record->account_id;
        $data = AccountStatisticModel::where('account_id', $account_id)
            ->whereBetween('created_at', [
                Carbon::parse('6 days ago')->toDateString(),
                Carbon::parse('today')->toDateString()])->
            orderBy('created_at')->get();
        $script = config('app.url') . '/lib/echarts.min.js';

        $fill_time = '';
        $fill_recommend_count = '';
        $fill_comment_count = '';
        $fill_view_count = '';
        $fill_share_count = '';
        $fill_collect_count = '';
        $fill_likes_count = '';
        foreach ($data as $d) {
            $fill_time .= "'".$d->created_at . '\',';
            $fill_recommend_count .= $d->recommend_count . ',';
            $fill_comment_count .= $d->comment_count . ',';
            $fill_view_count .= $d->view_count . ',';
            $fill_share_count .= $d->share_count . ',';
            $fill_collect_count .= $d->collect_count . ',';
            $fill_likes_count .= $d->likes_count . ',';
        }

        $html = <<<EOF
<script src="{$script}"></script>
<div id="chart" style="height:400px;"></div>
<script type="text/javascript">
        var myChart = echarts.init(document.getElementById('chart'));
var option = {
    title: {
        text: '统计'
    },
    tooltip: {
        trigger: 'axis'
    },
    legend: {
        data: ["推荐量","评论量","阅读/播放量","分享量","收藏量","点赞量"]
    },
    grid: {
        left: '3em',
        right: '4em',
        bottom: '3em',
        containLabel: true
    },
    toolbox: {
        feature: {
            saveAsImage: {}
        }
    },
    xAxis: {
        type: 'category',
        boundaryGap: false,
        data: [%s]
    },
    yAxis: {
        type: 'value'
    },
    series: [
        {
            name: '推荐量',
            type: 'line',
            stack: '总量',
            data: [%s]
        },
        {
            name: '评论量',
            type: 'line',
            stack: '总量',
            data: [%s]
        },
        {
            name: '阅读/播放量',
            type: 'line',
            stack: '总量',
            data: [%s]
        },
        {
            name: '分享量',
            type: 'line',
            stack: '总量',
            data: [%s]
        },
        {
            name: '收藏量',
            type: 'line',
            stack: '总量',
            data: [%s]
        },
        {
            name: '点赞量',
            type: 'line',
            stack: '总量',
            data: [%s]
        }
    ]
};
        myChart.setOption(option);
</script>
EOF;

        $html = sprintf($html, $fill_time,
            $fill_recommend_count,
            $fill_comment_count,
            $fill_view_count,
            $fill_share_count,
            $fill_collect_count,
            $fill_likes_count);
        return $content
            ->header('百家账户')
            ->description('显示百家账户')
            ->body($html);
    }

}
