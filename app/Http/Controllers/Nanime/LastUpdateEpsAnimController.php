<?php
namespace App\Http\Controllers\Nanime;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \App\Http\Controllers\ConfigController;
use \Symfony\Component\DomCrawler\Crawler;
use \GuzzleHttp\Client;
use \Goutte\Client as GoutteClient; 
use \Tuna\CloudflareMiddleware;
use \GuzzleHttp\Cookie\FileCookieJar;
use \GuzzleHttp\Psr7;
use \Carbon\Carbon;
use \Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Support\Facades\DB;
use \Jenssegers\Agent\Agent;

use Cache;
use Config;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\HelpersController as HelpersController;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done tinggal token db
class LastUpdateEpsAnimController extends Controller
{
    public function LastUpdateAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                return $this->LastUpdateAnimValue($param,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Last Update Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Last Update Anime","Invalid Token", $awal);
        }

        
    }
    
    public function LastUpdateAnimValue($param,$awal){
        $limitRange = (isset($param['params']['limit_range'])) ? (int)($param['params']['limit_range']) : 20;
        $starIndex = (isset($param['params']['star_index'])) ? (int)($param['params']['star_index']) : 0;
        $minRowPegination = (isset($param['params']['min_row_pegination'])) ? (int)($param['params']['min_row_pegination']) : 5;
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        
        if(!empty($limitRange) || !empty($starIndex) || ($isUpdated)){
            $dataLastUpdate = MainModel::getDataLastUpdate([
                'limit_range' => $limitRange,
                'star_index' => $starIndex,
                'is_updated' => $isUpdated
            ]);
            $TotalSearch = MainModel::getDataLastUpdate([
                'cek_count' => TRUE
            ]);
        }else{
            $dataLastUpdate['collection'] = array();
            $TotalSearch['collection'] = array();
        }
        
        if(count($dataLastUpdate['collection']) > 0){
            foreach($dataLastUpdate['collection'] as $dataLastUpdateAs){
                $dataDetail = MainModel::getDetailAnime([
                    'id_detail' => $dataLastUpdateAs['id_detail_anime'],
                ]);
                $SlugDetail = '';
                foreach($dataDetail['collection'] as $dataDetailAs){
                    $SlugDetail = $dataDetailAs['slug'];
                }
                $Episode = substr(strrchr($dataLastUpdateAs['slug'], '-'), 1);
                $LastUpdateAnime[] = array(
                    "Image" => $dataLastUpdateAs['image'],
                    "Title" => $dataLastUpdateAs['title'],
                    "Status" => $dataLastUpdateAs['status'],
                    "Episode" => $Episode,
                    "IdDetailAnime" => $dataLastUpdateAs['id_detail_anime'],
                    "IdListEpisode" => $dataLastUpdateAs['id_list_episode'],
                    "SlugDetail" => $SlugDetail,
                    "SlugEp" => $dataLastUpdateAs['slug'],
                    "Date" => $dataLastUpdateAs['cron_at'],
                );
            }
            $seachTotal = $TotalSearch['collection'];
            $TotalSearchPage = HelpersController::TotalSeachPage($limitRange, $seachTotal);
            $PageSearch = HelpersController::PageSearch($starIndex, $limitRange);
            $getDataLastUpdate = [
                "TotalSearchPage" => $TotalSearchPage,
                "PageSearch" => $PageSearch,
                "FirstPagination" => self::FirstPagination($PageSearch,$minRowPegination),
                'LastUpdateAnime' => $LastUpdateAnime
            ];
            
            return ResponseConnected::Success("Last Update Anime", NULL, $getDataLastUpdate, $awal);
        }else{
            return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
        }
    }

    public function FirstPagination($PageSearch,$minRowPegination){
        if($PageSearch % $minRowPegination === 0){
            $FirstPagination = $PageSearch;
        }elseif((($PageSearch % $minRowPegination) >= 1) && ($PageSearch > 5)){
            $awal = floor($PageSearch / $minRowPegination);
            $FirstPagination = $awal * $minRowPegination;
        }else{
            $FirstPagination = 1;
        }
        return $FirstPagination;
    }
}