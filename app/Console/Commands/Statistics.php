<?php


namespace App\Console\Commands;

use App\Helper\tool;
use App\Model\AccountModel;
use App\Model\PublishModel;
use App\Model\VideoModel;
use App\Model\VideoStatisticModel;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Statistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistics';

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
        ini_set('memory_limit', '4096M');
        $accounts = $this->getAccounts();

        $publishes = PublishModel::get();
        $publishes = $publishes->toArray();
        $today = date('Y-m-d');

        $statistic = [];
        foreach ($publishes as $publish) {
            $type = $publish['type'];//0养号 1推广
            if (!isset($accounts[$publish['account_id']])) {
                continue;
            }
            $account = $accounts[$publish['account_id']];
            $video = VideoModel::where('id', $publish['video_id'])->first();
            if (!$video) {
                continue;
            }
            if ($type == 0) {
                $data = $this->requestApi($account['app_id'], $account['app_token'], $video->article_id1);
            } else {
                $data = $this->requestApi($account['app_id'], $account['app_token'], $video->article_id2);
            }
            if (empty($data)) {
                continue;
            }

            $data = [
                'video_id' => $publish['video_id'],
                'type' => $type,
                'recommend_count' => self::checkNum($data['data']['recommend_count']),
                'comment_count' => self::checkNum($data['data']['comment_count']),
                'view_count' => self::checkNum($data['data']['view_count']),
                'share_count' => self::checkNum($data['data']['share_count']),
                'collect_count' => self::checkNum($data['data']['collect_count']),
                'likes_count' => self::checkNum($data['data']['likes_count']),
                'created_at' => $today
            ];
            $statistic[] = $data;
        }
        VideoStatisticModel::insert($statistic);
    }

    private static function checkNum($num)
    {
        if ($num > 0) {
            return $num;
        }
        return 0;
    }

    private function getAccounts()
    {
        $accounts = AccountModel::get();
        $accounts = $accounts->toArray();
        $data = [];
        foreach ($accounts as $account) {
            $data[$account['id']] = $account;
        }
        return $data;
    }

    private function requestApi($app_id, $app_token, $article_id)
    {
        $i = 0;
        do {
            if ($i > 3) {
                return [];
            }
            $result = tool::curlRequest(
                "http://baijiahao.baidu.com/builderinner/open/resource/query/articleStatistics",
                [
                    "app_id" => $app_id,
                    "app_token" => $app_token,
                    "article_id" => $article_id]);
            $i++;
            $data = (array)json_decode($result, true);
            if (!isset($data['errno']) && $data['errno'] != 0) {
                usleep(500000);
                continue;
            }
            return $data;
        } while (true);
    }
}
