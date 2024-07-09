<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use PureDevLabs\Extractors\Extractor;

class VK extends Extractor
{
    // Constants
    const _PARAMS = array(
        'url_root' => array(
            'vk.com/wall',
            'vk.com/#wildcard#?w=wall',
            'm.vk.com/video',
            'new.vk.com/video',
            'vk.com/video/#wildcard#?z=video',
            'vk.com/#wildcard#?z=video',
            'vk.com/video?z=video',
            'vk.com/video'
        )
    );

    // Fields
    protected $_userAgent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36';

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
        if (preg_match('/((wall)[0-9_-]+)$/', $vidUrl) == 1)
        {
            $wallPost = Http::withHeaders(['User-Agent' => $this->_userAgent])->get($vidUrl);
            if ($wallPost->successful() && preg_match('/<meta property="og:url" content="([^"]+)"\s*\/>/', $wallPost->body(), $wallMatch) == 1 && preg_match('/((wall)[0-9_-]+)$/', $wallMatch[1]) != 1)
            {
                return $this->RetrieveVidInfo($wallMatch[1]);
            }
        }
        foreach (self::_PARAMS['url_root'] as $urlRoot)
        {
            $vkPatternPart = preg_replace('/^(http)/', "$1(s)?", preg_replace(self::_WILDCARD_PATTERN, self::_WILDCARD_REPLACE, preg_quote("http://" . $urlRoot, '/')));
			$vidId = preg_replace('/^(' . $vkPatternPart . ')/', "", $vidUrl);
            //die($vidId);
            if ($vidId != $vidUrl)
            {
                $vidId = explode("/", urldecode($vidId))[0];
                $vidPage = Http::withHeaders(['User-Agent' => $this->_userAgent, 'Referer' => 'https://vk.com/al_video.php', 'X-Requested-With' => 'XMLHttpRequest'])->get('https://vk.com/al_video.php', ['act' => 'show', 'al' => '1', 'video' => $vidId]);
                if ($vidPage->successful())
                {
                    //die(print_r($vidPage->body()));
                    $vkInfo = iconv("cp1251", "utf-8", $vidPage->body());
                    $jsonData = json_decode($vkInfo, true);
                    if (json_last_error() == JSON_ERROR_NONE)
                    {
                        //die(print_r($jsonData));
                        if (isset($jsonData['payload'][1][4]['player']['params'][0]))
                        {
                            $jsonData = $jsonData['payload'][1][4]['player']['params'][0];
                            $videoInfo['videoId'] = (isset($jsonData['vid'])) ? $jsonData['vid'] : $vidId;
                            $videoInfo['title'] = (isset($jsonData['md_title'])) ? html_entity_decode($jsonData['md_title']) : $videoInfo['title'];
                            $videoInfo['thumb_preview'] = (isset($jsonData['jpg'])) ? $jsonData['jpg'] : $videoInfo['thumb_preview'];
                            $videoInfo['lengthSeconds'] = (isset($jsonData['duration'])) ? $jsonData['duration'] : $videoInfo['lengthSeconds'];
                            foreach (['url720', 'url480', 'url360', 'url240'] as $quality)
                            {
                                if (isset($jsonData[$quality]))
                                {
                                    $reqContext = stream_context_create(['http' => ['header' => "User-Agent: " . $this->_userAgent . "\r\n"]]);
                                    $reqHead = get_headers($jsonData[$quality], true, $reqContext);
                                    //die(print_r($reqHead));
                                    $videoInfo['videos'][] = [
                                        'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                                        'qualityLabel' => preg_replace('/\D/', "", $quality) . "p",
                                        'url' => $jsonData[$quality],
                                        'ext' => 'mp4'
                                    ];
                                }
                            }
                            $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
                            $videoInfo['audioOnly'] = (isset($jsonData['dash_sep'])) ? $this->ScrapeDashAudio($jsonData['dash_sep']) : $videoInfo['audioOnly'];
                            $videoInfo['audioOnly'] = (isset($jsonData['dash_webm'])) ? array_merge($videoInfo['audioOnly'], $this->ScrapeDashAudio($jsonData['dash_webm'])) : $videoInfo['audioOnly'];
                            $videoInfo['audioOnly'] = $this->MultiArrayVidSort($videoInfo['audioOnly']);
                            break;
                        }
                    }
                }
            }
        }
        //die(print_r($videoInfo));
        return $videoInfo;
    }

    private function ScrapeDashAudio($dashUrl)
    {
        $dashUrlArr = [];
        $dashXml = Http::withHeaders(['User-Agent' => $this->_userAgent])->get($dashUrl);
        if ($dashXml->successful())
        {
            $xml = simplexml_load_string(trim($dashXml->body()));
            if ($xml !== false && !empty($xml))
            {
                $xmlJson = json_encode($xml);
                $xmlArr = json_decode($xmlJson, true);
                //die(print_r($xmlArr));
                if (isset($xmlArr['Period']['AdaptationSet']) && is_array($xmlArr['Period']['AdaptationSet']))
                {
                    foreach ($xmlArr['Period']['AdaptationSet'] as $urlSet)
                    {
                        $isAudio = isset($urlSet['@attributes']['mimeType']) && preg_match('/^((video|audio)\/(\w+))/', $urlSet['@attributes']['mimeType'], $avMatch) == 1 && $avMatch[2] == "audio";
                        if (isset($urlSet['Representation']) && is_array($urlSet['Representation']))
                        {
                            foreach ($urlSet['Representation'] as $fileInfo)
                            {
                                $isAudio = (!$isAudio) ? isset($fileInfo['@attributes']['mimeType']) && preg_match('/^((video|audio)\/(\w+))/', $fileInfo['@attributes']['mimeType'], $avMatch) == 1 && $avMatch[2] == "audio" : $isAudio;
                                if ($isAudio && isset($fileInfo['BaseURL']))
                                {
                                    $fileUrlBase = "https://" . (string)parse_url($dashUrl, PHP_URL_HOST);
                                    $fileUrl = $fileUrlBase . $fileInfo['BaseURL'];
                                    $reqContext = stream_context_create(['http' => ['header' => "User-Agent: " . $this->_userAgent . "\r\n"]]);
                                    try
                                    {
                                        $reqHead = get_headers($fileUrl, true, $reqContext);
                                        $dashUrlArr[] = [
                                            'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                                            'qualityLabel' => ((isset($fileInfo['@attributes']['bandwidth'])) ? ceil((int)$fileInfo['@attributes']['bandwidth'] / 1000) : "128") . "kb",
                                            'url' => $fileUrl,
                                            'ext' => $avMatch[3]
                                        ];
                                    }
                                    catch (\Exception $ex) {}
                                }
                            }
                        }
                    }
                }
            }
        }
        return $dashUrlArr;
    }
    #endregion
}
