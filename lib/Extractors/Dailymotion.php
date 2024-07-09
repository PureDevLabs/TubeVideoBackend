<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;

class Dailymotion extends Extractor
{
    // Constants
    const _PARAMS = array(
        'url_root' => array(
            'www.dailymotion.com/video/'
        )
    );

    // Fields
    private $_vidQualities = array(
        'hd' => '720',  // high definition
        'hq' => '480',  // high quality
        'sd' => '380',  // standard definition
        'ld' => '240'  // low definition
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
        $videoInfo = array('extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => '', 'title' => 'Unknown', 'thumb_preview' => 'https://img.youtube.com/vi/oops/1.jpg', 'lengthSeconds' => 0, 'videos' => [], 'audioOnly' => []);
        $apiResponse = Http::get(preg_replace('/^(https?:\/\/www)/i', "https://api", explode('?', $vidUrl)[0]) . "?fields=id,title,thumbnail_medium_url,duration");
        if ($apiResponse->successful())
        {
            $apiResponse = $apiResponse->json();
            //die(var_dump($apiResponse));
            if (isset($apiResponse['id'], $apiResponse['title'], $apiResponse['thumbnail_medium_url'], $apiResponse['duration']))
            {
                $videoInfo = array('extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $apiResponse['id'], 'title' => $apiResponse['title'], 'thumb_preview' => $apiResponse['thumbnail_medium_url'], 'lengthSeconds' => $apiResponse['duration'], 'videos' => [], 'audioOnly' => []);
                $playerContents = Http::get('https://www.dailymotion.com/player/metadata/video/' . $videoInfo['videoId']);
                if ($playerContents->successful())
                {
                    $jsonArr = $playerContents->json();
                    //die(print_r($jsonArr));
				    /*if (isset($jsonArr['error']['title'])) echo "<br><b>" . $jsonArr['error']['title'] . "<b><br>";*/
                    if (isset($jsonArr['qualities']['auto'][0]['url']) && preg_match('/^((\.m3u8)(.*))$/', (string)strrchr($jsonArr['qualities']['auto'][0]['url'], ".")) == 1)
                    {
                        $videoInfo['videos'] = $this->ParsePlaylist($jsonArr['qualities']['auto'][0]['url']);
                    }
                    elseif (isset($jsonArr['qualities']) && is_array($jsonArr['qualities']))
                    {
                        $jsonQualities = $jsonArr['qualities'];
                        //die(print_r($jsonQualities));
                        foreach ($this->_vidQualities as $fl => $fq)
                        {
                            if (isset($jsonQualities[$fq]) && !empty($jsonQualities[$fq]) && is_array($jsonQualities[$fq]))
                            {
                                foreach ($jsonQualities[$fq] as $sourceType)
                                {
                                    if ($sourceType['type'] == 'video/mp4')
                                    {
                                        $videoInfo['videos'][] = [
                                            'contentLength' => '',
                                            'qualityLabel' => $fq . "p",
                                            'url' => stripslashes($sourceType['url']),
                                            'ext' => 'mp4'
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
                }
            }
        }
        //die(print_r($videoInfo));
        return $videoInfo;
    }

    private function ParsePlaylist($playlistUrl)
    {
        $urls = array();
        $m3u8 = Http::get($playlistUrl);
        //die($m3u8);
        if ($m3u8->successful())
        {
            $m3u8Lines = preg_split('/\n|\r/', $m3u8->body(), -1, PREG_SPLIT_NO_EMPTY);
            if (!empty($m3u8Lines))
            {
                //die(print_r($m3u8Lines));
                $quality = '';
                foreach ($m3u8Lines as $val)
                {
                    if (preg_match('/^(#.*?NAME="(\d+)")/', $val, $cmatches) == 1)
                    {
                        $quality = $cmatches[2];
                        continue;
                    }
                    if (preg_match('/^([^#].+?\.m3u8)/i', $val, $matches) == 1)
                    {
                        $urls[] = [
                            'contentLength' => '',
                            'qualityLabel' => $quality . "p",
                            'url' => $matches[1],
                            'ext' => 'mp4'
                        ];
                    }
                }
                //die(print_r($urls));
            }
        }
        return $urls;
    }
    #endregion
}
