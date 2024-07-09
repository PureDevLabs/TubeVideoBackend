<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;

class AOL extends Extractor
{
    // Constants
    const _PARAMS = array(
        'url_root' => array(
            'www.aol.com/video/play/#wildcard#/',
            'www.aol.com/video/#wildcard#/#wildcard#/',
            'www.aol.com/#wildcard#/',
            'www.aol.com/'
        )
    );

    #region Public Methods
    public function GetDownloadLinks($url)
    {
        $data = $this->RetrieveVidInfo($url);
        return $data;
    }
    #endregion

    #region Private "Helper" Methods
    private function RetrieveVidInfo($vidUrl)
    {
        $vidID = current(array_reverse(preg_split('/\//', parse_url($vidUrl, PHP_URL_PATH), -1, PREG_SPLIT_NO_EMPTY)));
        $videoInfo = array('extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $vidID, 'title' => 'Unknown', 'thumb_preview' => 'https://img.youtube.com/vi/oops/1.jpg', 'lengthSeconds' => 0, 'videos' => [], 'audioOnly' => []);
        if (preg_match('/(\.html)$/', $vidID) == 1)
        {
            $embedPage = Http::get($vidUrl);
            if ($embedPage->successful() && preg_match('/data-videoconfig="([^"]+)"/', $embedPage->body(), $embedMatch) == 1)
            {
                $embedJson = json_decode(html_entity_decode($embedMatch[1]), true);
                $videoInfo['videoId'] = $vidID = (isset($embedJson['playlist']['mediaItems'][0]['id']) && !empty($embedJson['playlist']['mediaItems'][0]['id'])) ? $embedJson['playlist']['mediaItems'][0]['id'] : $vidID;
            }
        }
        $infoPage = Http::get("https://video-api.yql.yahoo.com/v1/video/videos/" . $vidID);
        //die($infoPage->body());
        if ($infoPage->successful())
        {
            $json = $infoPage->json();
            //die(print_r($json));
            if (isset($json['videos']['result'][0]) && is_array($json['videos']['result'][0]))
            {
                $info = $json['videos']['result'][0];
                if (isset($info['streaming_url']))
                {
                    $m3u8 = Http::get($info['streaming_url']);
                    //die($m3u8->body());
                    if ($m3u8->successful())
                    {
                        $m3u8Lines = preg_split('/\n|\r/', $m3u8->body(), -1, PREG_SPLIT_NO_EMPTY);
                        $m3u8Lines = preg_grep('/^(#)/', $m3u8Lines, PREG_GREP_INVERT);
                        if (!empty($m3u8Lines))
                        {
                            //die(print_r(array_reverse($m3u8Lines)));
                            $m3u8Lines = array_reverse($m3u8Lines);
                            foreach ($m3u8Lines as $val)
                            {
                                if (preg_match('/_((\d+)x(\d+))_/i', $val, $matches) == 1)
                                {
                                    $videoInfo['videos'][] = [
                                        'contentLength' => '',
                                        'qualityLabel' => (string)min((int)$matches[2], (int)$matches[3]) . "p",
                                        'url' => $val,
                                        'ext' => 'mp4'
                                    ];
                                }
                            }
                            $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
                            $videoInfo['title'] = (isset($info['title']) && !empty($info['title'])) ? $info['title'] : $videoInfo['title'];
                            $videoInfo['thumb_preview'] = (isset($info['thumbnails'][0]['url']) && !empty($info['thumbnails'][0]['url'])) ? $info['thumbnails'][0]['url'] : $videoInfo['thumb_preview'];
                            $videoInfo['lengthSeconds'] = (isset($info['duration']) && !empty($info['duration'])) ? (int)$info['duration'] : $videoInfo['lengthSeconds'];
                        }
                    }
                }
            }
        }
        //die(print_r($videoInfo));
        return $videoInfo;
    }
    #endregion
}
