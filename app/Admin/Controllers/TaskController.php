<?php

namespace App\Admin\Controllers;

use App\Model\AccountModel;
use App\Model\TaskModel;
use DenDroGram\Controller\AdjacencyList;
use DenDroGram\Controller\DenDroGram;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
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
        $form = new Form(new TaskModel());
        $form->tools(function (Form\Tools $tools) {
            $tools->disableList();
            $tools->disableDelete();
            $tools->disableView();
        });
        return $content
            ->header('创建任务')
            ->description('创建任务')
            ->body($this->form());
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TaskModel());

        $form->display('id', __('ID'));
        $form->select('task_type','任务类型')->options([0 => '单个视频', 1 => '定时任务']);
        $form->text('account','yutuber账户');
        $form->url('url','目标地址');
        $form->number('time','间隔时间(小时)')->min(1)->default(1);

        $form->hidden('category','');
        $select = (new DenDroGram(AdjacencyList::class))->buildSelect(1);
        $script = <<<EOF
        <script>dendrogramUS.callback = function() {
            var data = dendrogramUS.storage();
            var dom = document.getElementsByClassName('category')[0]
            dom.value = data;
        };</script>
EOF;

        $form->html($select.$script, '分类标签');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }
}
