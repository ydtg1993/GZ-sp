<?php

namespace App\Admin\Controllers;

use App\Helper\tool;
use App\Model\AccountModel;
use App\Model\TaskModel;
use DenDroGram\Controller\AdjacencyList;
use DenDroGram\Controller\DenDroGram;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Log;

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
        $grid->column('url', '采集地址')->link()->style('width:200px');
        $grid->column('time', '采集时间');
        $grid->column('account', 'yutuber账号')->style('width:200px');
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
        $grid->actions(function ($actions) {
            // 去掉编辑
            $actions->disableEdit();

            // 去掉查看
            $actions->disableView();
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

        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $result = tool::curlRequest(config('app.url').":8002/api/video/download/",json_encode([
                'task_id'=>$id,
            ]),["Content-type: application/json;charset='utf-8'"]);
            $result = (array)json_decode($result,true);
            dd($result);
            if(!isset($result['status']) || $result['status'] != 0){
                Log::channel('createTask')->info('任务失败', $result);
                TaskModel::where('id',$id)->update(['status'=>4]);
            }
        });

        $form->display('id', __('ID'));
        $form->select('task_type','任务类型')->options([0 => '单个视频', 1 => '定时任务']);
        $form->text('account','yutuber账户');
        $form->url('url','目标地址');
        $form->number('time','间隔时间(小时)')->min(1)->default(1);

        $form->hidden('category','');
        $select = (new DenDroGram(AdjacencyList::class))->buildSelect(1);
        $style = '<style>.dendrogram-select-dropdown{max-height: 240px;overflow-y: auto}</style>';
        $script = <<<EOF
        <script>
        var dom = document.getElementsByClassName('category')[0];
        dom.value = dendrogramUS.storage();
        dendrogramUS.callback = function() {
            var data = dendrogramUS.storage();
            dom.value = data;
        };
        </script>
EOF;

        $form->html($select.$style.$script, '分类');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }
}
