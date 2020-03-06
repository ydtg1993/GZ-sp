<?php

namespace App\Admin\Controllers;

use App\Model\AccountModel;
use App\Model\AccountStatisticModel;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;

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
            [Carbon::parse('1 days ago')->toDateString(),Carbon::parse('today')->toDateString()]);

        $grid->column('id', '账户名')->display(function ($id){
            $account = AccountModel::where('id',$id)->first();
            if(!$account){
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
        return $grid;
    }

}
