<?php

namespace App\Admin\Controllers;

use App\Model\VideoModel;
use App\Model\VideoStatisticModel;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;

class VideoStatisticController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '视频统计';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VideoStatisticModel());
        $grid->model()->orderBy('created_at', 'desc')->whereBetween('created_at',
            [Carbon::parse('2 days ago')->toDateString(),Carbon::parse('today')->toDateString()]);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('video_id', '视频详情')->sortable();
        $grid->column('type', '类型')->using([0=>'养号',1=>'推广']);
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
        return $grid;
    }

}
