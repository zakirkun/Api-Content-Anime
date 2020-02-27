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
#Load Component External
use Cache;
use Config;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\HelpersController as HelpersController;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

class SearchGenreAnimeController extends Controller
{
    // KeyListGenre
    public function SearchGenreAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                return $this->SearchGenreAnimValue($param,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Search Genre Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Search Genre Anime","Invalid Token", $awal);
        }       
    }

    public function SearchGenreAnimValue($param, $awal){
        $genre = (isset($param['params']['genre'])) ? $param['params']['genre'] : '';
        $limitRange = (isset($param['params']['limit_range'])) && (!empty($param['params']['limit_range'])) ? (int)($param['params']['limit_range']) : (int)20;
        $starIndex = (isset($param['params']['star_index'])) ? (int)($param['params']['star_index']) : 0;
        $minRowPegination = (isset($param['params']['min_row_pegination'])) ? (int)($param['params']['min_row_pegination']) : 5;
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        if(!empty($genre)){
            $dataSearch = MainModel::getSearchWithDetailAnime([
                'genre' => $genre,
                'limit_range' => $limitRange,
                'star_index' => $starIndex,
                'is_updated' => $isUpdated
            ]);
            $TotalSearch = MainModel::getSearchWithDetailAnime([
                'genre' => $genre,
                'cek_count' => TRUE
            ]);
        }else{
            $dataSearch['collection'] = array();
            $TotalSearch['collection'] = array();
        }
        
        if(count($dataSearch['collection']) > 0){
        // Get the latest post in this category and display the titles
            foreach($dataSearch['collection'] as $dataSearchAs){
                $genre = '';
                foreach($dataSearchAs['genre'] as $Genre){
                    $genre .= $Genre.' |';
                }
                $ListDetail[] = array(
                    "ListInfo" => array(
                        "Status" => $dataSearchAs['status'], 
                        "Score" => $dataSearchAs['score'], 
                        "Rating" => $dataSearchAs['rating'], 
                        "Genre" => rtrim($genre,'|'),
                    ),
                    "Synopsis "=> $dataSearchAs['synopsis'], 
                );
                $SearchGenreAnime [] = array(
                    "Title" => $dataSearchAs['title'],
                    "Image" => $dataSearchAs['image'],
                    "IdDetailAnime" => $dataSearchAs['id_detail_anime'],
                    "SlugDetail" => $dataSearchAs['slug'],
                    "ListDetail" => $ListDetail
                );
                $ListDetail = array();
            }
            $seachTotal = $TotalSearch['collection'];
            $TotalSearchPage = HelpersController::TotalSeachPage($limitRange, $seachTotal);
            $PageSearch = HelpersController::PageSearch($starIndex, $limitRange);
            $SearchGenreDataAnime = [
                "TotalSearchPage" => $TotalSearchPage,
                "PageSearch" => $PageSearch,
                "FirstPagination" => self::FirstPagination($PageSearch,$minRowPegination),
                'SearchGenreAnime' => $SearchGenreAnime
            ];
            return ResponseConnected::Success("Search Genre Anime", NULL, $SearchGenreDataAnime, $awal);
        }else{
            return ResponseConnected::PageNotFound("Search Genre Anime","Page Not Found.", $awal);
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