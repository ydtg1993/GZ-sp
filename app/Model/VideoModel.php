<?php


namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class VideoModel extends Model
{
    protected $table = 'videos';

    public function task()
    {
        return $this->belongsTo(TaskModel::class, 'id','task_id');
    }
}
