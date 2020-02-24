<?php
namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Form\Field;

class StopTask extends Field
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        $url = config('app.url').':8002/api/server/crontab/?switch=off';
        return <<<SCRIPT
$('.toStop').on('click', function () {
    $.ajax({
        url:'{$url}',
        type:'GET',
        success:function(data){
				alert('成功');
		}
    })
});
SCRIPT;
    }

    public function render()
    {
        Admin::script($this->script());
        return <<<EOF
<a href="javascript:void(0)" class="toStop" data-id="{$this->id}"><i class="fa fa-stop-circle"></i></a>
EOF;
    }

    public function __toString()
    {
        return $this->render();
    }
}
