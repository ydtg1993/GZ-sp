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
        unlink(public_path().'/shell/statistic.sh');
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
