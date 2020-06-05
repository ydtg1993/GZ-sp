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
        $U = AccountRoleModel::where('user_id',Admin::user()->id)->first();
        if($U->role_id > 1){
            $grid->model()->where('operate_id',Admin::user()->id)->orderBy('created_at', 'desc')
                ->orderByRaw( "FIELD(status, 0,1,3,4,2)" );
        }else{
            $grid->model()->orderBy('created_at', 'desc')
                ->orderByRaw( "FIELD(status, 0,1,3,4,2)" );
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
            1 => '采集中',
            2 => '采集完成',
            3 => '采集等待中',
            4 => '采集失败',
            5 => '采集暂停'
        ])->dot([
            0 => 'default',
            1 => 'danger',
            2 => 'success',
            3 => 'primary',
            4 => 'warning',
            5 => 'danger'
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
        $grid->column('url', '采集地址')->style('width:200px')->display(function ($url) {
            $urls = explode(';',$url);
            $html = '';
            foreach ($urls as $url){
                $html.= <<<EOF
<a href="{$url}">{$url}</a></br>
EOF;
            }
            return $html;
        });
        $grid->column('time', '采集间隔时间');
        $grid->column('account', 'yutuber账号')->style('width:200px');
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
            $filter->equal('url', '采集地址');
            $filter->equal('status', '任务状态')->select([
                0 => '待处理',
                1 => '采集中',
                2 => '采集完成',
                3 => '采集等待中',
                4 => '采集失败',
                5 => '采集暂停'
            ]);
            $filter->between('created_at', '创建时间')->datetime();
        });
        //$grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableEdit();
            $actions->disableDelete();
            //$actions->append(new StopTask($actions->getKey()));
        });
        $grid->tools(function ($tools) {
            $tools->append(new RestartAllTask());
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
            $result = tool::curlRequest(config('app.url') . ":8002/api/video/task/", json_encode([
                'task_id' => $id,
            ]), ["Content-type: application/json;charset='utf-8'"]);
            $result = (array)json_decode($result, true);
            if (!isset($result['status']) || $result['status'] != 0) {
                Log::channel('createTask')->info('任务失败', $result);
                TaskModel::where('id', $id)->update(['status' => 4]);
            }
        });

        $form->display('id', __('ID'));
        $form->select('task_type', '任务类型')->options([0 => '单个视频', 1 => '定时任务'])->readonly();
        $form->display('account', 'yutuber账户');
        $form->textarea('url', '目标地址')->readonly();
        $form->number('time', '采集任务间隔时间(小时)')->min(1)->default(1);
        $form->divider('视频切割');
        //$form->number('cut_time', '切割时间(分钟)')->min(1)->default(3);
        $task = TaskModel::where('id',$id)->first();
        $html = <<<EOF
<div class="form-group">
<div class="col-sm-4">
            <input min="1" oninput="value=value.replace(/[^\d]/g,'')" style="width: 100px;float:left;text-align: center;" type="text" id="cut_time_m" name="cut_time_m" value="3" class="form-control" placeholder="切割时间(分)">
            <span style="float: left">分</span>
            <input min="0" max="59" oninput="value=value.replace(/[^\d]/g,'');if(value>59)value=59" style="width: 100px;float:left;text-align: center;" type="text" id="cut_time_s" name="cut_time_s" value="0" class="form-control" placeholder="切割时间(秒)">
            <span style="float: left">秒</span>
</div>
</div>
<script>
    $('#cut_time_m').change(function() {
      var m = $('#cut_time_m').val()
      var s = $('#cut_time_s').val()
      var t = m*60 + s;
      $('.cut_time').val(t)
    });
    $('#cut_time_s').change(function() {
      var m = $('#cut_time_m').val()
      var s = $('#cut_time_s').val()
      var t = m*60 + s;
      $('.cut_time').val(t)
    });
    $(function() {
      var cut_time = {$task->cut_time}
      cut_time = parseInt(cut_time)
      var s = cut_time % 60
      var m = (cut_time - s) / 60
      $('#cut_time_m').val(m)
      $('#cut_time_s').val(s)
    });
</script>
EOF;
        $form->html($html,'切割时间');
        $form->hidden('cut_time');

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

        $form->ignore('category');
        $form->saving(function (Form $form){
            $urls = explode(PHP_EOL,$form->url);
            $form->url = join(";",$urls);
        });
        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $result = tool::curlRequest(config('app.url') . ":8002/api/video/task/", json_encode([
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
        $form->textarea('url', '目标地址 多个换行表示');
        $form->number('time', '采集任务间隔时间(小时)')->min(1)->default(1);
        $form->divider('视频切割');
        //$form->number('cut_time', '切割时间(分钟)')->min(1)->default(3);
        $html = <<<EOF
<div class="form-group">
<div class="col-sm-4">
            <input min="1" oninput="value=value.replace(/[^\d]/g,'')" style="width: 100px;float:left;text-align: center;" type="text" id="cut_time_m" name="cut_time_m" value="3" class="form-control" placeholder="切割时间(分)">
            <span style="float: left">分</span>
            <input min="0" max="59" oninput="value=value.replace(/[^\d]/g,'');if(value>59)value=59" style="width: 100px;float:left;text-align: center;" type="text" id="cut_time_s" name="cut_time_s" value="0" class="form-control" placeholder="切割时间(秒)">
            <span style="float: left">秒</span>
</div>
</div>
<script>
    $('#cut_time_m').change(function() {
      var m = parseInt($('#cut_time_m').val())
      var s = parseInt($('#cut_time_s').val())
      var t = m*60 + s;
      $('.cut_time').val(t)
    });
    $('#cut_time_s').change(function() {
      var m = parseInt($('#cut_time_m').val())
      var s = parseInt($('#cut_time_s').val())
      var t = m*60 + s;
      $('.cut_time').val(t)
    });
</script>
EOF;
        $form->html($html,'切割时间');
        $form->hidden('cut_time')->default(180);

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
