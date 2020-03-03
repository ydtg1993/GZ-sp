<?php
namespace App\Admin\Extensions;

use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class RestartAllTask extends AbstractTool
{
    protected function script()
    {
        $url = Request::fullUrlWithQuery(['gender' => '_gender_']);

        return <<<EOT
EOT;
    }

    public function render()
    {
        $url = config('app.url').':8002/api/server/crontab?switch=on';
        return <<<EOF
<div class="btn-group" data-toggle="buttons">
            <a href="#" id="restartAllTask" data-href="{{url('admin/exam/create')}}" class="btn btn-sm btn-primary">
                任务全重启</a>
</div>
<script>
    $('#restartAllTask').click(function () {
        $.get('{$url}',function(){
           alert('完成');
        });
    })
</script>
EOF;
    }
}
