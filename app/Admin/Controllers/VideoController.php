<?php

namespace App\Admin\Controllers;

use App\Model\CategoryModel;
use App\Model\TaskModel;
use App\Model\VideoModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class VideoController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '下载视频';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VideoModel);
        $grid->column('id', __('ID'))->sortable();
        $grid->column('title', '标题')->style('width:150px');
        $grid->column('author', '作者');
        $grid->column('avatar')->image();
        $grid->column('type1', '类型1');
        $grid->column('type2', '类型2');

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

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('视频')
            ->description('视频详情')
            ->body($this->detail($id));
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(VideoModel::findOrFail($id));

        $show->id('ID');
        $show->task_id('任务')->unescape()->as(function ($task_id) {
            $task = TaskModel::where('id',$task_id)->first();
            $task_type = $task->task_type == 0 ? '单任务':'定时任务';
            $task_status = '';
            switch ($task->status){
                case 0:
                    $task_status = '待处理';
                    break;
                case 1:
                    $task_status = '采集中';
                    break;
                case 2:
                    $task_status = '采集完成';
                    break;
            }
            return <<<EOF
<table class="table table-hover">
    <tr>
        <th>任务类型</th>
        <th>状态</th>
        <th>yutube账户</th>
        <th>目标地址</th>
        <th>任务详情</th>
    </tr>
    <tr>
        <td>{$task_type}</td>
        <td>{$task_status}</td>
        <td>$task->account</td>
        <td><a href="{$task->url}">地址</a></td>
        <td></td>
    </tr>
</table>
EOF;
        });
        $show->avatar()->unescape()->as(function ($avatar) {
            return "<img width='300px' src='{$avatar}' />";
        });
        $show->author('作者');
        $show->type('类型')->unescape()->as(function ()use($show){
            $type1 = $show->getModel()->type1;
            $type2 = $show->getModel()->type2;
            $types = CategoryModel::whereIn('id',[$type1,$type2])->get();
            $result = '';
            foreach ($types as $type){
                $result.="<div class='btn btn-sm btn-primary' style='margin-left: 10px'>{$type->name}</div>";
            }
            return $result;
        });
        $show->resource()->unescape()->as(function ($resource) {
            return $resource;
        });
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }
}
