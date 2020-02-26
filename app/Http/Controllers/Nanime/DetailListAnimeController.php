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
use Illuminate\Support\Facades\DB;

use Cache;
use Config;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done
class DetailListAnimeController extends Controller
{

    public function testing(){
        echo "WELCOM TO API CONTENT Detail ANIME NIMEINDO V1";
    }
    // KeyListAnim
    public function DetailListAnim(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        
        if($Token){
            // try{
                return $this->DetailListAnimeValue($param,$awal);     
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Detail Anime","Internal Server Error",$awal);
            // }
        }else{
            return ResponseConnected::InvalidToken("Detail Anime","Invalid Token", $awal);
        }
    }
    
    public function DetailListAnimeValue($param,$awal){
        $idDetail = isset($param['params']['id_detail']) ? $param['params']['id_detail'] : '';
        $slugDetail = isset($param['params']['slug_detail']) ? $param['params']['slug_detail'] : '';
        if(!empty($idDetail) || !empty($slugDetail)){
            $dataDetail = MainModel::getDetailAnime([
                'id_detail' => $idDetail,
                'slug' => $slugDetail
            ]);
        }else{
            $dataDetail['collection'] = array();
        }
        
        if(count($dataDetail['collection']) > 0){
            $dataDetailAss = $dataDetail['collection'];
            $genre = '';
            foreach($dataDetailAss as $dataDetailAs){
                foreach($dataDetailAs['genre'] as $genree){
                    $genre .= $genree.' |';
                }
                $ListInfo = array(
                    "Tipe" =>$dataDetailAs['type'],
                    "Genre" => rtrim($genre,'|'),
                    "Status" => $dataDetailAs['status'],
                    "Episode" => $dataDetailAs['episode_total'],
                    "Years" => '',
                    "Score" => $dataDetailAs['score'],
                    "Rating" => $dataDetailAs['rating'],
                    "Studio" => $dataDetailAs['studio'],
                    "Duration" => $dataDetailAs['duration'],
                );
                foreach($dataDetailAs['episode'] as $episodeAs){
                    $ListEpisode[] = array(
                        'IDEpisode' => $episodeAs['id_episode'],
                        'IDStream' => $episodeAs['id_stream_anime'],
                        "SlugEp" => $episodeAs['slug'],
                        "Episode" => $episodeAs['episode']
                    );
                }
                $Synopsis = $dataDetailAs['synopsis'];
                $Title = $dataDetailAs['title'];
                $imageUrl = $dataDetailAs['image'];
                $Slug = $dataDetailAs['slug'];
                
                $ListDetail[] = array(
                    "ListInfo" => $ListInfo,
                    "Synopsis" => $Synopsis
                );
                $DetailListAnime[] = array(
                    "Title" => $Title,
                    "Image" => $imageUrl,
                    "SlugDetail" => $Slug,
                    "ListDetail" =>$ListDetail,
                    "ListEpisode" => $ListEpisode
                );
            }
            $LogSave = [
                'SingleListAnime' => $DetailListAnime
            ];
            return ResponseConnected::Success("Detail Anime", NULL, $LogSave, $awal);
        }else{
            return ResponseConnected::PageNotFound("Detail Anime","Page Not Found.", $awal);
        }
    }
}