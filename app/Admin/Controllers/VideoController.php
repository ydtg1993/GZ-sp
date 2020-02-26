<?php

namespace App\Admin\Controllers;

use App\Helper\tool;
use App\Model\AccountModel;
use App\Model\CategoryModel;
use App\Model\DownloadModel;
use App\Model\PublishModel;
use App\Model\TagModel;
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
        $grid->model()->orderBy('created_at', 'desc');
        $grid->header(function ($query) {
            return <<<EOF
<script>function openVideo(source) {
  var dom = document.getElementById(source);
  var div = document.createElement("div");
  div.innerHTML = "<video width=230 height=120 controls>" +
   "<source src="+ source +" type=video/mp4>" +
    "<source src=movie.ogg type=video/ogg>" +
     "</video>";
  dom.append(div)
}</script>
EOF;
        });
        $grid->column('id', __('ID'))->sortable();
        $grid->column('title', '标题')->display(function ($title) {
            $title2 = preg_replace("/\"|\'|\n/","",$title);
            return <<<EOF
<div title="$title2" style='width:150px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;'>$title</div>
EOF;
        });
        $grid->column('author', '作者')->display(function ($title) {
            return "<div title={$title} style='width:150px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;'>$title</div>";
        });
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
        $grid->column('resource','原视频')->display(function($resource){
            $source = config('app.url') . '/' .$resource;
            return <<<EOF
<a href="javascript:void(0);" onclick=openVideo('{$source}')>查看</a>
<div id="{$source}"></div>
EOF;
        });
        $grid->column('resource2','编辑视频')->display(function($resource){
            $source = config('app.url') . '/' .$resource;
            return <<<EOF
<a href="javascript:void(0);" onclick=openVideo('{$source}')>查看</a>
<div id="{$source}"></div>
EOF;
        });
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
        $script = config('app.url').'/lib/echarts.min.js';
        $html = <<<EOF
<script src="{$script}"></script>%s
EOF;

        $video = VideoModel::where('id',$id)->first();
        $publishes = PublishModel::where('video_id',$id)->orderBy('type')->get();
        $publishes = $publishes->toArray();
        $chartsPanel = '';
        foreach ($publishes as $publish){
            $account_id = $publish['account_id'];
            $type = $publish['type'];
            $account = AccountModel::where('id',$account_id)->first();
            $result = tool::curlRequest("http://baijiahao.baidu.com/builderinner/open/resource/query/articleStatistics",[
                "app_id"=>$account->app_id,
                "app_token"=>$account->app_token,
                "article_id"=>$type == 0 ? $video->article_id1 : $video->article_id2
            ]);
            $data = json_decode($result,true);
            if(!isset($data['errno']) && $data['errno'] != 0){
                continue;
            }
            $title = $type == 0 ? '养号视频实时数据' : '推广视频实时数据';
            $recommend_count = $data['data']['recommend_count'] > 0 ? $data['data']['recommend_count'] : 0;
            $comment_count = $data['data']['comment_count'] > 0 ? $data['data']['comment_count'] : 0;
            $view_count = $data['data']['view_count'] > 0 ? $data['data']['view_count'] : 0;
            $share_count = $data['data']['share_count'] > 0 ? $data['data']['share_count'] : 0;
            $collect_count = $data['data']['collect_count'] > 0 ? $data['data']['collect_count'] : 0;
            $likes_count = $data['data']['likes_count'] > 0 ? $data['data']['likes_count'] : 0;
                $panel = <<<EOF
<div id="chart{$type}" style="height:400px;"></div>
<script type="text/javascript">
        var myChart = echarts.init(document.getElementById('chart{$type}'));
        var option = {
            title: {
                text: '{$title}'
            },
            color: ['#3398DB'],
            tooltip: {},
            legend: {
                data:['统计数']
            },
            xAxis: {
                data: ["推荐量","评论量","阅读/播放量","分享量","收藏量","点赞量"]
            },
            yAxis: {},
            series: [{
                name: '销量',
                type: 'bar',
                data: [$recommend_count,$comment_count,$view_count,$share_count,$collect_count,$likes_count]
            }]
        };
        myChart.setOption(option);
</script>
EOF;
            $chartsPanel.= $panel;
        }

        $html = sprintf($html,$chartsPanel);
        return $content
            ->header('视频')
            ->description('视频详情')
            ->body($html);
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
        $show->tags('标签');
        $show->resource('原视频')->unescape()->as(function ($resource){
            $source = config('app.url') . '/' .$resource;
            return <<<EOF
<video width="350" height="240" controls>
    <source src="{$source}" type="video/mp4">
    <source src="movie.ogg" type="video/ogg">
</video>
EOF;
        });
        $show->resource2('已编辑视频')->unescape()->as(function ($resource){
            $source = config('app.url') . '/' .$resource;
            return <<<EOF
<video width="350" height="240" controls>
    <source src="{$source}" type="video/mp4">
    <source src="movie.ogg" type="video/ogg">
</video>
EOF;
        });

        $publishUrl = config('app.url') . '/admin/publishToBj';
        $publishReturn = config('app.url') . '/admin/video';
        $show->html('发布')->unescape()->as(function () use ($show, $publishUrl, $publishReturn, $id) {
            $disable = "";
            if (!$show->getModel()->resource2) {
                $disable = "disabled";
            }
            return <<<EOF
<div class="col-sm-8">
    <select name="toAccountType" id="toAccountType" class="form-control">
        <option value="0">养号</option>
        <option value="1" {$disable}>推广号</option>
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
            console.log(d);
            if(d.status == 1){
                window.location.href = '{$publishReturn}';
            }else {
                alert(d.message);
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
        $flag = PublishModel::where(['video_id'=>$videoId,'type'=>$toAccountType])->exists();
        if($flag){
            return response()->json(['status' => 2, 'message' => '该视频已经发布过']);
        }
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
                        "tag" => $video->tags
                    ]
                );
                $result = (array)json_decode($result, true);
                Log::channel('publish')->info('推送失败', [
                    "app_id" => $account->app_id,
                    "app_token" => $account->app_token,
                    "title" => $video->title,
                    "video_url" => $resource,
                    "cover_images" => $video->avatar,
                    "is_original" => 0,
                    "tag" => $video->tags
                ]);
                if ($result['errno'] !== 0) {
                    Log::channel('publish')->info('推送失败', $result);
                    continue;
                }
                PublishModel::insert([
                    'video_id' => $videoId,
                    'account_id' => $account->id,
                    'type'=>$toAccountType
                ]);
                if ($toAccountType == 0) {
                    VideoModel::where('id', $videoId)->update(['publish_status1' => 1,'article_id1'=>$result['data']['article_id']]);
                } else {
                    VideoModel::where('id', $videoId)->update(['publish_status2' => 1,'article_id2'=>$result['data']['article_id']]);
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
        $form->saving(function (Form $form){
            $len = mb_strlen($form->title);
            if($len < 8 || $len > 40){
                throw new \Exception('视频标题，限定 8-40 个中英文字符以内');
                return;
            }
        });
        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            return redirect('/admin/publish/' . $id);
        });
        $form->display('id', __('ID'));
        $help = "视频标题，限定 8-40 个中英文字符以内";
        if($id){
            $video = VideoModel::where('id',$id)->first();
            $len = mb_strlen($video->title);
            $help = "视频标题，限定 8-40 个中英文字符以内 当前长度({$len})";
        }
        $form->text('title', '标题')->help($help);
        $form->image('avatar', '封面')->help('封面图尺寸不小于660*370')->uniqueName();
        $form->hidden('tags');

        $tags = TagModel::get();
        $tagButton = "<div class='btn btn-primary v-tag' style='margin-right: 8px;margin-bottom: 8px'>%s</div>";
        $tagHtml = '';
        foreach ($tags as $tag) {
            $tagHtml .= sprintf($tagButton, $tag->name);
        }
        $html = <<<EOF
    <div style="width: 100%;display: grid; grid-template-rows: 45px 140px;border: 1px solid #ccc;border-radius: 10px">
        <div id="tags-select" style="overflow-y: auto;border-bottom: 1px solid #ccc;padding: 5px"></div>
        <div id="tags-content" style="overflow-y: auto;padding: 5px">
            {$tagHtml}
        </div>
    </div>
    <script>
        var tags_select_dom = $('#tags-select');
        var tags_content_dom = $('#tags-content');
        var tags_input = $(".tags");
        $('#tags-content .v-tag').click(tagSelect)

        function tagSelect() {
            var cdom = this.cloneNode(true);
            cdom.addEventListener('click',tagCancel)
            tags_select_dom.append(cdom);
            this.remove();
            addVal();
        }

        function tagCancel() {
             var cdom = this.cloneNode(true);
             cdom.addEventListener('click',tagSelect)
             tags_content_dom.append(cdom)
             this.remove();
             addVal();
        }

        function addVal() {
            var val = '';
            tags_select_dom.children().each(function(i,n){
                if(val){
                    val+= ","+n.textContent;
                }else {
                    val = n.textContent;
                }
            });
            tags_input.val(val);
        }
    </script>
EOF;
        $form->html($html, '标签选择');
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
        $video = VideoModel::where('id', $id)->first();
        if (file_exists(BASE_PATH . $video->resource)) {
            @unlink(BASE_PATH . $video->resource);
        }
        if (file_exists(BASE_PATH . $video->resource2)) {
            @unlink(BASE_PATH . $video->resource2);
        }
        DownloadModel::where('origin_id',$video->origin_id)->delete();
        if ($this->form()->destroy($id)) {
            return response()->json([
                'status' => true,
                'message' => '成功',
            ]);
        };

    }
}
