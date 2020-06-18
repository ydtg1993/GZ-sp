<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\RestartAllTask;
use App\Admin\Extensions\StopTask;
use App\Helper\tool;
use App\Model\AccountRoleModel;
use App\Model\AdminUserModel;
use App\Model\TaskModel;
use App\Model\VideoModel;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\Log;

class UploadController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '本地上传任务';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TaskModel);
        $U = AccountRoleModel::where('user_id',Admin::user()->id)->first();
        if($U->role_id > 1){
            $grid->model()->where('operate_id',Admin::user()->id)->orderBy('created_at', 'desc')
                ->orderByRaw( "FIELD(status, 0,1,3,4,2)" )->where('task_type',2);
        }else{
            $grid->model()->orderBy('created_at', 'desc')
                ->orderByRaw( "FIELD(status, 0,1,3,4,2)" )->where('task_type',2);
        }

        $grid->column('id', __('ID'))->sortable();
        $grid->column('task_type', '任务类型')->display(function ($type) {
            if ($type == 0) {
                return '单任务';
            }
            return '批量任务';
        });
        $grid->column('status', '任务状态')->using([
            0 => '待处理',
            1 => '处理中',
            2 => '处理完成',
            3 => '处理等待中',
            4 => '处理失败'
        ])->dot([
            0 => 'default',
            1 => 'danger',
            2 => 'success',
            3 => 'primary',
            4 => 'warning'
        ])->expand(function ($model) {
            $id = $model->id;
            $videos = VideoModel::where('task_id', $id)
                ->orderBy('created_at', 'DESC')
                ->whereBetween('created_at', [
                    Carbon::today()->startOfDay(),
                    Carbon::today()->endOfDay()
                ])->get(['title', 'author', 'id', 'created_at']);
            $videos = $videos->toArray();
            $url = config('app.url');
            foreach ($videos as &$video) {
                $video['id'] = "<a href='{$url}/admin/video/{$video['id']}/edit' target='_blank'>详情</a>";
            }
            return new Table(['标题', '作者', '详情', '发布时间'], $videos);
        });

        $grid->column('operate_id', '操作人')->display(function ($id) {
            $a = AdminUserModel::where('id',$id)->first();
            return "<div title={$a->username} style='width:70px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;'>$a->username</div>";
        });
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '修改时间');

        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal('status', '任务状态')->select([
                0 => '待处理',
                1 => '处理中',
                2 => '处理完成',
                3 => '处理等待中',
                4 => '处理失败'
            ]);
            $filter->between('created_at', '创建时间')->datetime();
        });
        //$grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
            //$actions->append(new StopTask($actions->getKey()));
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
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        set_time_limit(0);
        $form = new Form(new TaskModel());

        $form->ignore('category');
        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $v = VideoModel::where('task_id',$id)->first();
            $result = tool::curlRequest(config('app.url') . ":8002/api/video/upload/", json_encode([
                'task_id' => $id,
                'video_id' => $v->id
            ]), ["Content-type: application/json;charset='utf-8'"]);
            $result = (array)json_decode($result, true);
            if (!isset($result['status']) || $result['status'] != 0) {
                Log::channel('createTask')->info('上传任务失败', $result);
                TaskModel::where('id', $id)->update(['status' => 4]);
            }
        });

        $form->display('id', __('ID'));
        $form->hidden('task_type', '任务类型')->default(2);

        $form->text('video.title','标题');
        $form->file('video.resource', '视频')->uniqueName();
        $form->hidden('video.author','作者')->default(Admin::user()->username);

        $form->divider('插入开始短片');
        $form->file('op_video', '开始短片')->uniqueName();

        $form->divider('视频添加音乐');
        $form->file('audio', '音频文件')->uniqueName();
        $form->number('audio_time', '插入音频时间(秒)')->min(0)->default(0);

        $form->divider('视频插入全屏图片');
        $form->image('cover', '全屏图片')->uniqueName();
        $form->file('cover_audio', '背景音乐')->uniqueName();
        $form->number('cover_time', '插入图片时间(秒)')->min(0)->default(0);

        $form->divider('视频加入水印');
        $form->image('mark', '水印图片')->uniqueName();

        $form->hidden('mark_width', '水印宽')->default(0);
        $form->hidden('mark_height', '水印高')->default(0);

        $form->number('mark_x', '水印横坐标X')->min(0)->default(0);
        $form->number('mark_y', '水印纵坐标Y')->min(0)->default(0);
        $form->number('mark_start', '水印开始时间')->min(0)->default(15);
        $form->number('mark_duration', '持续时间')->min(0)->default(15);

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));
        $form->hidden('operate_id')->value(Admin::user()->id);

        return $content
            ->header('百家账户')
            ->description('修改修改接入商户')
            ->body($form->edit($id));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        set_time_limit(0);
        $form = new Form(new TaskModel());

        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $v = VideoModel::where('task_id',$id)->first();
            $result = tool::curlRequest(config('app.url') . ":8002/api/video/upload/", json_encode([
                'task_id' => $id,
                'video_id' => $v->id
            ]), ["Content-type: application/json;charset='utf-8'"]);
            $result = (array)json_decode($result, true);
            if (!isset($result['status']) || $result['status'] != 0) {
                Log::channel('createTask')->info('上传任务失败', $result);
                TaskModel::where('id', $id)->update(['status' => 4]);
            }
        });

        $form->display('id', __('ID'));
        $form->hidden('task_type', '任务类型')->default(2);

        $form->text('video.title','标题');
        $form->file('video.resource', '视频')->uniqueName();
        $form->hidden('video.author','作者')->default(Admin::user()->username);

        $form->divider('插入开始短片');
        $form->file('op_video', '开始短片')->uniqueName();

        $form->divider('视频添加音乐');
        $form->file('audio', '音频文件')->uniqueName();
        $form->number('audio_time', '插入音频时间(秒)')->min(0)->default(0);

        $form->divider('视频插入全屏图片');
        $form->image('cover', '全屏图片')->uniqueName();
        $form->file('cover_audio', '背景音乐')->uniqueName();
        $form->number('cover_time', '插入图片时间(秒)')->min(0)->default(0);

        $form->divider('视频加入水印');
        $form->image('mark', '水印图片')->uniqueName();

        $form->hidden('mark_width', '水印宽')->default(0);
        $form->hidden('mark_height', '水印高')->default(0);

        $form->number('mark_x', '水印横坐标X')->min(0)->default(0);
        $form->number('mark_y', '水印纵坐标Y')->min(0)->default(0);
        $form->number('mark_start', '水印开始时间')->min(0)->default(15);
        $form->number('mark_duration', '持续时间')->min(0)->default(15);

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));
        $form->hidden('operate_id')->value(Admin::user()->id);
        return $form;
    }
}
