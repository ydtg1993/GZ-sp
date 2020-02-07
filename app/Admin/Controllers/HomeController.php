<?php

namespace App\Admin\Controllers;

use App\Helper\tool;
use App\Http\Controllers\Controller;
use DenDroGram\Controller\AdjacencyList;
use DenDroGram\Controller\DenDroGram;
use DenDroGram\Controller\NestedSet;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        /*$result = tool::curlRequest("http://baijiahao.baidu.com/builderinner/open/resource/video/publish",[
            "app_id"=>"1648637698438034",
            "app_token"=>"12105ee5e3532ed92011cf63ac23d007",
            "title"=>"鹈鹕鹈鹕鹈鹕鹈鹕鹈鹕鹈鹕",
            "video_url"=>"http://180.178.58.130/resource/vedio/vm.mp4",
            "cover_images"=>"http://180.178.58.130/resource/image/t.jpg",
            "is_original"=>0,
        ]);
        var_dump(json_decode($result,true));
exit;*/
        echo (new DenDroGram(AdjacencyList::class))->buildCatalog(1,config('app.url').'/api/cat',['name']);exit;
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
