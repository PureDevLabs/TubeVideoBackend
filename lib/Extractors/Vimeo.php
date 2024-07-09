<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;

class Vimeo extends Extractor
{
    // Constants
    const _PARAMS = [
        'url_root' => [
            'vimeo.com/'
        ]
    ];

    #region Public Methods
    public function GetDownloadLinks(string $url): array
    {
        $id = $this->ExtractVideoId($url);
        $data = $this->RetrieveVidInfo($id);
        return $data;
    }
    #endregion

    #region Private "Helper" Methods
    private function RetrieveVidInfo(string $vidID): array
    {
        $videoInfo = ['extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $vidID, 'title' => 'Unknown', 'thumb_preview' => 'https://img.youtube.com/vi/oops/1.jpg', 'lengthSeconds' => 0, 'videos' => [], 'audioOnly' => []];
        $videoInfo = $this->UseNewApi($videoInfo, $vidID);
        if (empty($videoInfo['videos']))
        {
            $videoInfo = $this->UseOldApiAndPlayer($videoInfo, $vidID);
        }
        //die(print_r($videoInfo));
        return $videoInfo;
    }

    private function UseNewApi(array $videoInfo, string $vidID): array
    {
        $authResponse = Http::get("https://vimeo.com/_rv/viewer");
        if ($authResponse->successful())
        {
            $authResponse = $authResponse->json();
            if (isset($authResponse['jwt']))
            {
                $apiResponse = Http::withHeaders(['Authorization' => 'jwt ' . $authResponse['jwt']])->get("https://api.vimeo.com/videos/" . $vidID);
                if ($apiResponse->successful())
                {
                    $apiResponse = $apiResponse->json();
                    //die(var_dump($apiResponse));
                    if (isset($apiResponse['name'], $apiResponse['pictures']['base_link'], $apiResponse['duration']))
                    {
                        $videoInfo = ['extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $vidID, 'title' => $apiResponse['name'], 'thumb_preview' => $apiResponse['pictures']['base_link'], 'lengthSeconds' => $apiResponse['duration'], 'videos' => [], 'audioOnly' => []];
                        if (isset($apiResponse['download']) && !empty($apiResponse['download']) && is_array($apiResponse['download']))
                        {
                            foreach ($apiResponse['download'] as $source)
                            {
                                if (isset($source['link'], $source['size'], $source['rendition']) && $source['rendition'] != "source")
                                {
                                    $videoInfo['videos'][] = [
                                        'contentLength' => $source['size'],
                                        'qualityLabel' => $source['rendition'],
                                        'url' => $source['link'],
                                        'ext' => 'mp4'
                                    ];
                                }
                            }
                            $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
                        }
                    }
                }
            }
        }
        return $videoInfo;
    }

    private function UseOldApiAndPlayer(array $videoInfo, string $vidID): array
    {
        $apiResponse = Http::get("https://vimeo.com/api/v2/video/" . $vidID . ".json");
        if ($apiResponse->successful())
        {
            $apiResponse = $apiResponse->json();
            //die(var_dump($apiResponse));
            if (isset($apiResponse[0]['id'], $apiResponse[0]['title'], $apiResponse[0]['thumbnail_medium'], $apiResponse[0]['duration']))
            {
                $videoInfo = ['extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $apiResponse[0]['id'], 'title' => $apiResponse[0]['title'], 'thumb_preview' => $apiResponse[0]['thumbnail_medium'], 'lengthSeconds' => $apiResponse[0]['duration'], 'videos' => [], 'audioOnly' => []];
                $playerContents = Http::get('https://player.vimeo.com/video/' . $videoInfo['videoId']);
                if ($playerContents->successful())
                {
                    //if (preg_match('/(\w+)\.video\.id/', $playerContents->body(), $objName) == 1) die($objName[1]);
                    $jsonObjName = (preg_match('/(\w+)\.video\.id/', $playerContents->body(), $objName) == 1) ? $objName[1] : "r";
                    $pattern1 = preg_match('/var ' . preg_quote($jsonObjName, '/') . '=(\{.+?\});/s', $playerContents->body(), $matchArray);
                    $pattern2 = preg_match('/config\s*=\s*(\{.+?\})\s*;/s', $playerContents->body(), $matchArray2);
                    $matchArray = ($pattern1 != 1) ? (($pattern2 != 1) ? [] : $matchArray2) : $matchArray;
                    if (!empty($matchArray))
                    {
                        $json = json_decode(strip_tags($matchArray[1]), true);
                        //die(print_r($json));
                        if (isset($json['request']['files']['progressive']) && !empty($json['request']['files']['progressive']) && is_array($json['request']['files']['progressive']))
                        {
                            foreach ($json['request']['files']['progressive'] as $source)
                            {
                                if (isset($source['url']))
                                {
                                    $reqHead = get_headers($source['url'], true);
                                    $videoInfo['videos'][] = [
                                        'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                                        'qualityLabel' => (isset($source['quality'])) ? preg_replace('/\D/', '', $source['quality']) . "p" : '',
                                        'url' => $source['url'],
                                        'ext' => 'mp4'
                                    ];
                                }
                            }
                            $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
                        }
                    }
                }
            }
        }
        return $videoInfo;
    }
    #endregion
}
