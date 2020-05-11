<?php


namespace App\Console\Commands;

use App\Helper\tool;
use App\Model\AccountModel;
use App\Model\AccountStatisticModel;
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

    protected $today = '';

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
        $this->today = date('Y-m-d');
        $check = VideoStatisticModel::whereBetween('created_at',[
            date('Y-m-d 00:00:00', time()),$todayEnd= date('Y-m-d 23:59:59', time())])->first();
        if($check){
            die('已经统计！');
        }

        $accounts = $this->getAccounts();

        $total = PublishModel::count();
        $limit = 1000;
        $page = ceil($total / $limit);

        $statistic = [];
        $accountStatistic = [];

        for ($i = 0; $i <= $page; $i++) {
            $start = $i * $limit;
            $publishes = PublishModel::offset($start)->limit($limit)->get();
            $publishes = $publishes->toArray();

            $this->deal($publishes, $accounts, $statistic,$accountStatistic);
        }
        VideoStatisticModel::insert($statistic);
        AccountStatisticModel::insert($accountStatistic);
    }

    private function deal($publishes, $accounts, &$statistic,&$accountStatistic)
    {
        foreach ($publishes as $publish) {
            $type = $publish['type'];//0养号 1推广
            $account_id = $publish['account_id'];
            if (!isset($accounts[$account_id])) {
                continue;
            }
            $account = $accounts[$account_id];
            $video = VideoModel::where('id', $publish['video_id'])->first();
            if (!$video) {
                continue;
            }
            if ($type == 0) {
                $d = $this->requestApi($account['app_id'], $account['app_token'], $video->article_id1);
            } else {
                $d = $this->requestApi($account['app_id'], $account['app_token'], $video->article_id2);
            }
            if (empty($d)) {
                continue;
            }

            $data = [
                'video_id' => $publish['video_id'],
                'type' => $type,
                'recommend_count' => self::checkNum($d['data']['recommend_count']),
                'comment_count' => self::checkNum($d['data']['comment_count']),
                'view_count' => self::checkNum($d['data']['view_count']),
                'share_count' => self::checkNum($d['data']['share_count']),
                'collect_count' => self::checkNum($d['data']['collect_count']),
                'likes_count' => self::checkNum($d['data']['likes_count']),
                'created_at' => $this->today
            ];
            if ($data['recommend_count'] == 0 &&
                $data['comment_count'] == 0 &&
                $data['view_count'] == 0 &&
                $data['share_count'] == 0 &&
                $data['collect_count'] == 0 &&
                $data['likes_count'] == 0) {
                continue;
            }
            $statistic[] = $data;
            /*account statistic*/
            $account_key = $account_id.':';
            if(!isset($accountStatistic[$account_key])){
                $accountStatistic[$account_key] = [
                    'account_id' => $account_id,
                    'recommend_count' => self::checkNum($d['data']['recommend_count']),
                    'comment_count' => self::checkNum($d['data']['comment_count']),
                    'view_count' => self::checkNum($d['data']['view_count']),
                    'share_count' => self::checkNum($d['data']['share_count']),
                    'collect_count' => self::checkNum($d['data']['collect_count']),
                    'likes_count' => self::checkNum($d['data']['likes_count']),
                    'created_at' => $this->today
                ];
                continue;
            }
            $accountStatistic[$account_key]['recommend_count']+=  self::checkNum($d['data']['recommend_count']);
            $accountStatistic[$account_key]['comment_count']+=  self::checkNum($d['data']['comment_count']);
            $accountStatistic[$account_key]['view_count']+=  self::checkNum($d['data']['view_count']);
            $accountStatistic[$account_key]['share_count']+=  self::checkNum($d['data']['share_count']);
            $accountStatistic[$account_key]['collect_count']+=  self::checkNum($d['data']['collect_count']);
            $accountStatistic[$account_key]['likes_count']+=  self::checkNum($d['data']['likes_count']);
        }
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
            if (!isset($data['errno'])) {
                usleep(500000);
                continue;
            }
            if (isset($data['errno']) && $data['errno'] != 0) {
                usleep(500000);
                continue;
            }
            return $data;
        } while (true);
    }
}
