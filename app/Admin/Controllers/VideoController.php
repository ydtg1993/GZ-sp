<?php

namespace App\Admin\Controllers;

use App\Model\CategoryModel;
use App\Model\TaskModel;
use App\Model\VideoModel;
use DenDroGram\Controller\AdjacencyList;
use DenDroGram\Controller\DenDroGram;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
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
        $grid->column('type1', '类型1')->display(function ($type) {
            $category = CategoryModel::where('id',$type)->first();
            return $category->name;
        });
        $grid->column('type2', '类型2')->display(function ($type) {
            $category = CategoryModel::where('id',$type)->first();
            return $category->name;
        });
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->between('created_at', '创建时间')->datetime();
        });
        $grid->actions(function ($actions) {
            // 去掉删除
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
        $show->title('标题');
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
        <td><a href="{$task->url}" target="_BLANK">地址</a></td>
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
            $source = config('app.url').'/'.$resource;
            return <<<EOF
<video width="450" height="300" controls>
    <source src="{$source}" type="video/mp4">
    <source src="movie.ogg" type="video/ogg">
</video>
EOF;
        });
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('视频')
            ->description('视频操作')
            ->body($this->form($id)->edit($id));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id='')
    {
        $form = new Form(new VideoModel());

        $form->display('id', __('ID'));
        $form->text('title','标题');
        $form->display('author', __('作者'));
        $form->image('avatar','封面')->uniqueName();
        $form->display('resource', __('资源路径'));
        $form->hidden('type1','');
        $form->hidden('type2','');

        if($id) {
            $data = VideoModel::where('id', $id)->first();
            $source = config('app.url').'/'.$data->resource;
            $video = <<<EOF
<video width="450" height="300" controls>
    <source src="{$source}" type="video/mp4">
    <source src="movie.ogg" type="video/ogg">
</video>
EOF;
            $form->html($video, '视频展示');

            $select = (new DenDroGram(AdjacencyList::class))->buildSelect(1, 'name', 'id', [$data->type1, $data->type2]);
            $style = '<style>.dendrogram-select-dropdown{max-height: 240px;overflow-y: auto}</style>';
            $script = <<<EOF
        <script>
        var dom1 = document.getElementsByClassName('type1')[0];
        var dom2 = document.getElementsByClassName('type2')[0];
        var v = dendrogramUS.storage();
        dom1.value = v[0];
        dom2.value = v[1];
        dendrogramUS.callback = function() {
            var data = dendrogramUS.storage();
            dom1.value = data[0];
            dom2.value = data[1];
        };
        </script>
EOF;
            $form->html($select . $style . $script, '分类标签');
        }

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();

        });
        return $form;
    }
}
