<?php

namespace App\Admin\Controllers;

use App\Helper\tool;
use App\Model\TaskModel;
use App\Model\VideoModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Table;
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
        $grid->model()->orderBy('created_at', 'desc');
        $grid->column('id', __('ID'))->sortable();
        $grid->column('task_type', '任务类型')->display(function ($type) {
            if ($type == 0) {
                return '单任务';
            }
            return '批量任务';
        });
        $grid->column('status', '任务状态')->using([
            0 => '待处理',
            1 => '采集中',
            2 => '采集完成',
            3 => '采集等待中',
            4 => '采集失败'
        ])->dot([
            0 => 'default',
            1 => 'danger',
            2 => 'success',
            3 => 'primary',
            4 => 'warning',
        ])->expand(function ($model) {
            $id = $model->id;
            $videos = VideoModel::where('task_id',$id)->get(['title', 'author','avatar','created_at']);
            $videos = $videos->toArray();
            foreach ($videos as &$video){
                $video['avatar'] = "<img width='150px' src='{$video['avatar']}' />";
            }
            return new Table(['标题', '作者','封面', '发布时间'], $videos);
        });
        $grid->column('url', '采集地址')->link()->style('width:200px');
        $grid->column('time', '采集间隔时间');
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
        $grid->disableActions();
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

        $form->ignore('category');
        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $result = tool::curlRequest(config('app.url') . ":8002/api/video/download/", json_encode([
                'task_id' => $id,
            ]), ["Content-type: application/json;charset='utf-8'"]);
            $result = (array)json_decode($result, true);
            if (!isset($result['status']) || $result['status'] != 0) {
                Log::channel('createTask')->info('任务失败', $result);
                TaskModel::where('id', $id)->update(['status' => 4]);
            }
        });

        $form->display('id', __('ID'));
        $form->select('task_type', '任务类型')->options([0 => '单个视频', 1 => '定时任务']);
        $form->text('account', 'yutuber账户');
        $form->url('url', '目标地址');
        $form->number('time', '任务间隔时间(小时)')->min(1)->default(1);
        $form->number('cut_time', '切割时间(分钟)')->min(1)->default(3);

        $form->file('audio','音频文件')->move('public/resource/audio')->uniqueName();
        $form->number('audio_time', '插入音频时间(秒)')->min(0)->default(0);
        $form->image('cover','全屏图片')->move('public/resource/image')->uniqueName();
        $form->number('cover_time', '插入图片时间(秒)')->min(0)->default(0);
        $form->image('mark','水印图片')->move('public/resource/image')->uniqueName();
        $form->number('mark_position_x', '水印横坐标X')->min(0)->default(0);
        $form->number('mark_position_y', '水印纵坐标Y')->min(0)->default(0);

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }
}
