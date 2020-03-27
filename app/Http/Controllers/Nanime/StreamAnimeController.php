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
use \Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\Client as Client2;
use \Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Support\Facades\DB;

use Cache;
use Config;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done but masih proses debuging
class StreamAnimeController extends Controller
{
    // keyEpisode
        public function StreamAnime(Request $request){
            $awal = microtime(true);
            $param = $request->all();
            $ApiKey = $request->header("X-API-KEY");
            $Users = MainModel::getUser($ApiKey);
            $Token = $Users[0]['token'];
            if($Token){
                // try{
                    return $this->StreamValue($param,$awal);
                // }catch(\Exception $e){
                //     return $this->InternalServerError();
                // }
            }else{
                return ResponseConnected::InvalidToken("Server Stream Anime","Invalid Token", $awal);
            }
        }

        public function StreamValue($param,$awal){
            
            $IDStream = (isset($param['params']['ID_Stream']) || !empty(isset($param['params']['id_Stream']))) ? $param['params']['id_Stream'] : '';
            $id_list_episode = (isset($param['params']['id_list_episode']) || !empty(isset($param['params']['id_list_episode']))) ? (int)$param['params']['id_list_episode'] : '';
            $slugEps = (isset($param['params']['slug_eps']) || !empty(isset($param['params']['slug_eps']))) ? $param['params']['slug_eps'] : '';

            if(!empty($IDStream) || !empty($slugEps) || !empty($id_list_episode)){
                $getDataStream = MainModel::getDataStream([
                    'ID_Stream' => $IDStream,
                    'slug_eps' => $slugEps,
                    'id_list_episode' => $id_list_episode,
                ]);
            }else{
                $getDataStream['collection'] = array();
            }
            
            if(count($getDataStream['collection']) > 0){
                // for get iframe from javascript
                $ListInfo = array();
                $ListServer = array();
                $ListDownload = array();
                $dataStreamAs = $getDataStream['collection'];
                    foreach($dataStreamAs['data_server'] as $ServerList){
                        $ListServer[] = array(
                            "IdServer" => $ServerList['id_server'],
                            "NameServer" => $ServerList['name_server'],
                            'IframeSrc' => $ServerList['iframe_src'],
                        );
                    }   
                    foreach($dataStreamAs['data_download'] as $key => $DownloadList){
                        foreach($DownloadList as $donloadList){
                            $ListDownload[$key][] = [
                                'IdDownload' => $donloadList['id_download'],
                                'NameDownload' => $donloadList['name_download'],
                                'AdflyLink' => $donloadList['adfly_link']
                            ];
                        }
                    }
                    
                    $dataDetail = MainModel::getDetailAnime([
                        'id_detail' => $dataStreamAs['id_detail_anime'],
                    ]);
                    
                    // dd($sluug); 
                    if(count($dataDetail['collection']) > 0){
                        foreach($dataDetail['collection'] as $detailAnime){
                            
                            $TotalEpisode = count($detailAnime['episode']);
                            // $NextStream = (int)$TotalEpisode - $NextStreamEps;
                            $PrevStream = (self::searchForKey($detailAnime['episode'],'slug' ,$dataStreamAs['slug'])) ? (int)(self::searchForKey($detailAnime['episode'],'slug' ,$dataStreamAs['slug']) - 1) : '';
                            $NextStream = (self::searchForKey($detailAnime['episode'],'slug' ,$dataStreamAs['slug'])) ? (int)(self::searchForKey($detailAnime['episode'],'slug' ,$dataStreamAs['slug']))+1 : '';
                            // $search = self::searchForKey($detailAnime['episode'],'slug' ,$dataStreamAs['slug']);
                            // dd($dataStreamAs);
                            $IdNextStream = !empty($detailAnime['episode'][$NextStream]) ? $detailAnime['episode'][$NextStream]['id_stream_anime'].'-'.$detailAnime['episode'][$NextStream]['slug'] : '';
                            $IdPrevStream = !empty($detailAnime['episode'][$PrevStream]) ? $detailAnime['episode'][$PrevStream]['id_stream_anime'].'-'.$detailAnime['episode'][$PrevStream]['slug'] : '';
                            
                                $ListInfo = array(
                                    "Tipe" => $detailAnime['type'],
                                    "Status" => $detailAnime['status'],
                                    "Episode" => $detailAnime['episode_total'],
                                    "Years" => '',
                                    "Score" => $detailAnime['score'],
                                    "Rating" => $detailAnime['rating'],
                                    "Studio" => $detailAnime['studio'],
                                    "Duration" => $detailAnime['duration'],
                                    "IdNextStream" => $IdNextStream,
                                    "IdPrevStream" => $IdPrevStream,
                                    "IdDetailAnime" => $dataStreamAs['id_detail_anime'],
                                    "SlugDetail" => $detailAnime['slug'],
                                );
                        }
                        
                    }
                    
                    $ListDetail[] =array(
                        "ListInfo" => $ListInfo,
                        "Synopsis" => $dataStreamAs['synopsis']
                    );
                    $Title = ucwords(str_replace('-',' ',$dataStreamAs['slug']));
                    $StreamAnime[] = array(
                        "Title" => $Title,
                        "Image" => $dataStreamAs['image'],
                        "SlugEp" => $dataStreamAs['slug'],
                        "ListDetail" => $ListDetail,
                        "ListServer" => $ListServer,
                        "DownloadList" => $ListDownload
                    );
                $LogSave = [
                    'StreamAnime' => $StreamAnime
                ];

                
                return ResponseConnected::Success("Server Stream Anime", NULL, $LogSave, $awal);
            }else{
                return ResponseConnected::PageNotFound("Server Stream Anime","Page Not Found.", $awal);
            }
        }

        public function searchForKey($products, $field, $value){
            
            foreach($products as $key => $product)
            {   
                if($product[$field] === $value )
                    return $key;
                 
            }
            return false;
        }

}