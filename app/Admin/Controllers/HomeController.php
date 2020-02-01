<?php

namespace App\Admin\Controllers;

use App\Helper\tool;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        $result = tool::curlRequest("http://baijiahao.baidu.com/builderinner/open/resource/video/publish",[
            "app_id"=>"1648637698438034",
            "app_token"=>"12105ee5e3532ed92011cf63ac23d007",
            "title"=>"测这个视频一下",
            "video_url"=>"http://180.178.58.130/resource/vedio/v3.mp4",
            "cover_images"=>"http://180.178.58.130/resource/image/t.jpg",
            "is_original"=>0,
        ]);
        var_dump(json_decode($result,true));
exit;
        return $content
            ->title('Dashboard')
            ->description('Description...')
            ->row(Dashboard::title())
            ->row(function (Row $row) {

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });
            });
    }
}
