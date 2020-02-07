<?php

namespace App\Admin\Controllers;

use App\Model\AccountModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;

class AccountController extends AdminController
{
    use HasResourceActions;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '百家号';


    public function index(Content $content)
    {
        $grid = new Grid(new AccountModel());
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name','账户名');
        $grid->column('app_id');
        $grid->column('app_token');
        $grid->column('type', '类型')->using([
            0=> '养号',
            1 => '推广号'
        ]);

        //$grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal('type', '类型')->select([
                0=> '养号',
                1 => '推广号'
            ]);
            $filter->between('created_at', '创建时间')->datetime();
        });
        return $content
            ->header('百家号')
            ->description('百家号账户管理')
            ->body($grid);
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        $form = new Form(new AccountModel());
        $form->tools(function (Form\Tools $tools) {
            $tools->disableList();
            $tools->disableDelete();
            $tools->disableView();
        });
        $form->text('name', '账户名称');
        $form->text('app_id', 'app_id');
        $form->text('app_token', 'app_token');
        $states = [
            'on' => ['value' => 0, 'text' => '养号', 'color' => 'default'],
            'off'  => ['value' => 1, 'text' => '推广', 'color' => 'success'],
        ];
        $form->switch('type','类型')->states($states);
        //$form->hidden('operate_id')->value(Admin::user()->id);
        return $content
            ->header('创建账户')
            ->description('创建百家账户')
            ->body($form);
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
            ->header('百家账户')
            ->description('显示百家账户')
            ->body($this->detail($id));
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
            ->header('百家账户')
            ->description('修改修改接入商户')
            ->body($this->form()->edit($id));
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(AccountModel::findOrFail($id));

        $show->id('ID');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AccountModel());

        $form->display('id', __('ID'));
        $form->display('name','账户名称');
        $form->display('app_id', __('app_id'));
        $form->display('app_token', __('app_token'));
        $states = [
            'on' => ['value' => 0, 'text' => '养号', 'color' => 'default'],
            'off'  => ['value' => 1, 'text' => '推广', 'color' => 'success'],
        ];
        $form->switch('type','类型')->states($states);
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }

}
