<?php

namespace App\Admin\Controllers;

use App\Model\TaskModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class TaskController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '任务栏';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TaskModel);
        $grid->header(function ($query) {
            return $this->operateGrid();
        });

        $grid->column('id', __('ID'))->sortable();
        $grid->column('task_type', '任务类型')->display(function ($type) {
            if($type==0){
                return '单任务';
            }
            return '批量任务';
        });
        $grid->column('status', '任务状态')->using([
            0=> '待处理',
            1 => '采集中',
            2 => '已成功',
        ])->dot([
            0=> 'warning',
            1 => 'danger',
            2 => 'success',
        ]);
        $grid->column('url', '采集地址')->link();
        $grid->column('time', '采集时间');
        $grid->column('account', 'yutuber账号');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '修改时间');

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal('url', '采集地址');
            $filter->equal('status', '任务状态')->select([
                0 => '待处理',
                1 => '采集中',
                2 => '已完成'
            ]);
            $filter->between('created_at', '创建时间')->datetime();
        });
        return $grid;
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('创建')
            ->description('创建订单')
            ->body($this->form());
    }

    public function operateGrid()
    {
        return <<<EOF
    <h3 class="hello">ffffff</h3>
<script>$('.hello').click(function() {
  alert('hello')
})</script>
EOF;

    }
}
