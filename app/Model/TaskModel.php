<?php


namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class TaskModel extends Model
{
    protected $table = 'tasks';

    public function video()
    {
        return $this->hasOne(VideoModel::class, 'task_id','id');
    }
}
