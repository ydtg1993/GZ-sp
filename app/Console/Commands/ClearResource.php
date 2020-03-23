<?php
namespace App\Console\Commands;

use App\Model\DownloadModel;
use App\Model\VideoModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearResource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clear resource';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tree_days_ago = Carbon::parse('3 days ago')->toDateTimeString();
        $videos = VideoModel::where([
            ['created_at','<=',$tree_days_ago],
            ['resource_status','=',0]
        ])->get();
        foreach ($videos as $video){
            if (file_exists(public_path() . '/' . $video->resource)) {
                @unlink(public_path() . '/' . $video->resource);
            }
            if (file_exists(public_path() . '/' . $video->resource2)) {
                @unlink(public_path() . '/' . $video->resource2);
            }
            if(file_exists(public_path().'/upload/'.$video->avatar)){
                @unlink(public_path().'/upload/'.$video->avatar);
            }
            VideoModel::where('id',$video->id)->update(['resource_status'=>1]);
        }
        
        $downloads = DownloadModel::where([
            ['created_at','<=',$tree_days_ago],
        ])->get();
        foreach ($downloads as $download){
            if (file_exists(public_path() . '/' . $download->resource)) {
                @unlink(public_path() . '/' . $download->resource);
            }
        }
    }
}
