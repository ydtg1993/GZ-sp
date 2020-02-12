<?php

namespace App\Admin\Controllers;

use App\Helper\tool;
use App\Model\AccountModel;
use App\Model\CategoryModel;
use App\Model\PublishModel;
use App\Model\TaskModel;
use App\Model\VideoModel;
use Carbon\Carbon;
use DenDroGram\Controller\AdjacencyList;
use DenDroGram\Controller\DenDroGram;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $grid->column('publish_status1', '养号发布')->using([
            0 => '待发布',
            1 => '已发布'
        ])->dot([
            0 => 'default',
            1 => 'success',
        ]);
        $grid->column('publish_status2', '推广发布')->using([
            0 => '待发布',
            1 => '已发布'
        ])->dot([
            0 => 'default',
            1 => 'success',
        ]);
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
        $show->title('标题');
        $show->task_id('任务')->unescape()->as(function ($task_id) {
            $task = TaskModel::where('id', $task_id)->first();
            $task_type = $task->task_type == 0 ? '单任务' : '定时任务';
            $task_status = '';
            switch ($task->status) {
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
        $show->resource('原视频')->unescape()->as(function ($resource) {
            $source = config('app.url') . '/' . $resource;
            return <<<EOF
<video width="350" height="240" controls>
    <source src="{$source}" type="video/mp4">
    <source src="movie.ogg" type="video/ogg">
</video>
EOF;
        });
        $show->resource2('已编辑视频')->unescape()->as(function ($resource) {
            $source = config('app.url') . '/' . $resource;
            return <<<EOF
<video width="350" height="240" controls>
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

    public function publish(Request $request, Content $content)
    {
        $id = $request->route('id');

        $show = new Show(VideoModel::findOrFail($id));
        $show->title('标题');
        $show->avatar()->unescape()->as(function ($avatar) {
            return "<img width='300px' src='{$avatar}' />";
        });

        $publishUrl = config('app.url').'/admin/publishToBj';
        $publishReturn = config('app.url').'/admin/video';
        $show->html('发布')->unescape()->as(function ()use($publishUrl,$publishReturn,$id) {
            return <<<EOF
<div class="col-sm-8">
    <select name="toAccountType" id="toAccountType" class="form-control">
        <option value="0">养号</option>
        <option value="1">推广号</option>
    </select>
    <button type="submit" id="publish" class="btn btn-primary" style="margin-top: 20px">确认发布</button>
</div>
<script>
$('#publish').click(function() {
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url:"{$publishUrl}",
        type:'POST',
        data:{
          id:{$id},
          toAccountType:$('#toAccountType').val(),
        },
        success:function(d) {
            if(d.status == 0){
                window.location.href = '{$publishReturn}';
            }else {
                admin_toastr(d.message, 'error');
            }
        }
    })
});
</script>
EOF;
        });
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });
        return $content
            ->header('发布')
            ->description('视频发布')
            ->body($show);
    }

    public function publishToBj(Request $request)
    {
        $videoId = (int)$request->input('id');
        $toAccountType = (int)$request->input('toAccountType');
        $accounts = AccountModel::where('type', $toAccountType)->inRandomOrder()->get();

        foreach ($accounts as $account) {
            $limit = PublishModel::whereBetween('created_at', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay()
            ])->where('account_id', $account->id)->count();
            if ($limit < 5) {
                $video = VideoModel::where('id', $videoId)->first();
                $resource = config('app.url') . '/' . $video->resource;
                if ($toAccountType == 1) {
                    $resource = config('app.url') . '/' . $video->resource2;
                }
                $result = tool::curlRequest(
                    "http://baijiahao.baidu.com/builderinner/open/resource/video/publish",
                    [
                        "app_id" => $account->app_id,
                        "app_token" => $account->app_token,
                        "title" => $video->title,
                        "video_url" => $resource,
                        "cover_images" => $video->avatar,
                        "is_original" => 0,
                    ]
                );
                $result = (array)json_decode($result, true);
                if($result !== 0){
                    Log::channel('publish')->info('推送失败', $result);
                    continue;
                }
                PublishModel::insert([
                    'video_id' => $videoId,
                    'account_id' => $account->id,
                ]);
                if($toAccountType == 0){
                    VideoModel::where('id',$videoId)->update(['publish_status1'=>1]);
                }else{
                    VideoModel::where('id',$videoId)->update(['publish_status2'=>1]);
                }
                return response()->json(['status' => 1, 'message' => '发布成功']);
            }
        }
        return response()->json(['status' => 2, 'message' => '暂时没有可用百家号']);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = '')
    {
        $form = new Form(new VideoModel());
        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            return redirect('/admin/publish/' . $id);
        });
        $form->display('id', __('ID'));
        $form->text('title', '标题');
        $form->display('author', __('作者'));
        $form->image('avatar', '封面')->uniqueName();
        $form->display('resource', __('资源路径'));
        $form->hidden('type1', '');
        $form->hidden('type2', '');

        if ($id) {
            $data = VideoModel::where('id', $id)->first();
            $source = config('app.url') . '/' . $data->resource;
            $source2 = config('app.url') . '/' . $data->resource2;
            $video = <<<EOF
<video width="350" height="240" controls>
    <source src="{$source}" type="video/mp4">
    <source src="movie.ogg" type="video/ogg">
</video>
<video width="350" height="240" controls>
    <source src="{$source2}" type="video/mp4">
    <source src="movie.ogg" type="video/ogg">
</video>
EOF;
            $form->html($video, '视频展示');
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

    public function destroy($id)
    {
        $video = VideoModel::where('id',$id)->first();
        if(file_exists(BASE_PATH.$video->resource)) {
            chmod(BASE_PATH . $video->resource,0777);
            @unlink(BASE_PATH . $video->resource);
            return response()->json([
                'status'  => true,
                'message' => BASE_PATH . $video->resource,
            ]);
        }
        if(file_exists(BASE_PATH . $video->resource2)) {
            chmod(BASE_PATH . $video->resource2,0777);
            unlink(BASE_PATH . $video->resource2);
        }
        if($this->form()->destroy($id)){
            return response()->json([
                'status'  => true,
                'message' => '成功',
            ]);
        };

    }
}
