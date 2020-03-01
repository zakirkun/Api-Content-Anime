<?php

namespace App\Http\Controllers\Nanime;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \App\Http\Controllers\ConfigController;
use \GuzzleHttp\Client;
use \Goutte\Client as GoutteClient; 
use \Tuna\CloudflareMiddleware;
use \GuzzleHttp\Cookie\FileCookieJar;
use \GuzzleHttp\Psr7;
use \Carbon\Carbon;
use \Sunra\PhpSimple\HtmlDomParser;
use \App\User;
use Illuminate\Support\Facades\DB;

#Load Component External
use Cache;
use Config;

#Load Helper V1
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\HelpersController as HelpersController;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done
class ListAnimeController extends Controller
{

    public function testing(){
        echo "WELCOM TO API CONTENT LIST ANIME NIMEINDO V1";
    }

    public function ListAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{
                return $this->ListAnimeValue($param,$awal);
            }catch(\Exception $e){
                return ResponseConnected::InternalServerError("List Anime","Internal Server Error",$awal);
            }
            
        }else{
            return ResponseConnected::InvalidToken("List Anime","Invalid Token", $awal);
        }
    }
    
    
    public function ListAnimeValue($param,$awal){
        $nameIndex = isset($param['params']['name_index']) ? $param['params']['name_index'] : '';
        $allIndex  = isset($param['params']['all_index']) ? filter_var($param['params']['all_index'], FILTER_VALIDATE_BOOLEAN): FALSE ;
        if(!empty($nameIndex) || ($allIndex)){
            $dataListAnime  = MainModel::getDataListAnime([
                'name_index' => $nameIndex,
                'All_index' => $allIndex
            ]);
        }else{
            $dataListAnime['collection'] = array();
        }
        
        if(count($dataListAnime['collection']) > 0){
            
            $NameIndex = array();
            foreach($dataListAnime['collection'] as $dataListAnimeAs){
                $NameIndex [] = $dataListAnimeAs['name_index'];
            }
            $ListAnime = array();
            $NameIndex = HelpersController::__rearrangeArrayIndex($NameIndex);
            for($i = 0 ; $i < count($NameIndex); $i++){
                $ListSubIndex = array();
                
                foreach($dataListAnime['collection'] as $dataListAnimeAss){
                    $NameIndexVal = $dataListAnimeAss['name_index'];
                    $Title = ucwords(str_replace('-',' ',$dataListAnimeAss['slug']));
                    if($NameIndexVal == $NameIndex[$i]){
                        $ListSubIndex[] = array(
                            'IdDetailAnime' => $dataListAnimeAss['id_detail_anime'],
                            "Title" => $Title,
                            "SlugDetail" => $dataListAnimeAss['slug'],
                            "Image" => $dataListAnimeAss['image'],
                            "Status" => $dataListAnimeAss['status'],
                            "DatePublish" => $dataListAnimeAss['cron_at']
                        );
                    }
                }
                $ListAnime[] = [
                    "NameIndex" => $NameIndex[$i],
                    "ListSubIndex" => $ListSubIndex
                ];   
            }
            $LogSave = [
                'ListAnime' => $ListAnime
            ];
            return ResponseConnected::Success("List Anime", NULL, $LogSave, $awal);
        }else{
            return ResponseConnected::PageNotFound("List Anime","Page Not Found.", $awal);
        }
    }

    //
}
