<?php

namespace App\Admin\Controllers;

use App\Model\AccountModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;

class AccountController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '百家号';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AccountModel());
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name','账户名');
        $grid->column('app_id');
        $grid->column('app_token');
        $grid->column('type', '类型')->using([
            0=> '养号',
            1 => '推广号'
        ]);

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal('type', '类型')->select([
                0=> '养号',
                1 => '推广号'
            ]);
            $filter->between('created_at', '创建时间')->datetime();
        });
        return $grid;
    }
}
