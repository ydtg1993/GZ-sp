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
            "title"=>"测测这个视频",
            "video_url"=>"https://vdept.bdstatic.com/5039615352386737486b775256687559/7977734c704d794a/e6d834c7fdd8e546b050fcac46965296f67f336c527f8649f0c76acd46ee01b22df5297db75c98f4ac51341d10a6eadb.mp4?auth_key=1580381428-0-0-90728a559010853ee14d5ef5cc8af129",
            "cover_images"=>"http://180.178.58.130/resource/image/castle.jpg",
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
