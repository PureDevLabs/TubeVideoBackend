<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;

class Facebook extends Extractor
{
    // Constants
    const _PARAMS = array(
        'url_root' => array(
            'www.facebook.com/video/video.php?v=',
            'www.facebook.com/video.php?v=',
            'www.facebook.com/photo.php?v=',
            'www.facebook.com/watch?v=',
            'www.facebook.com/watch/?v=',
            'www.facebook.com/groups/#wildcard#/permalink/',
            'www.facebook.com/#wildcard#/posts/',
            'www.facebook.com/#wildcard#/videos/#wildcard#/',
            'www.facebook.com/#wildcard#/videos/',
            'web.facebook.com/#wildcard#/videos/',
            'm.facebook.com/story.php?story_fbid=',
            'fb.watch/',
            'fb.gg/v/'
        )
    );

    // Fields
    protected $_userAgent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36';
    private $_bkupUserAgent = 'facebookexternalhit/1.1';

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
        $vidID = $this->ExtractVideoId($vidUrl);
        if (preg_match('/^(https?:\/\/fb\.)/', $vidUrl) == 1)
        {
            $appRequest = Http::withHeaders(['User-Agent' => $this->_bkupUserAgent])->head($vidUrl);
            //die(print_r($appRequest->effectiveUri()->__toString()));
            $effectiveUri = $appRequest->effectiveUri();
            if (!is_null($effectiveUri))
            {
                $vidID = $this->ExtractVideoId($effectiveUri->__toString());
                $vidUrl = "https://" . self::_PARAMS['url_root'][0] . $vidID;
            }
            //die($vidUrl . " " . $vidID);
        }
        if ($vidID == "story.php")
        {
            $urlQueryStr = parse_url($vidUrl, PHP_URL_QUERY);
            if ($urlQueryStr !== false && !is_null($urlQueryStr))
            {
                parse_str($urlQueryStr, $qsVars);
                $vidID = $qsVars['story_fbid'];
                $vidUrl = 'https://www.facebook.com/' . $qsVars['id'] . '/videos/' . $vidID;
            }
        }
        $postUrlRegex = '/(\/posts\/[^\/]+\/?)$/';
        if (preg_match($postUrlRegex, $vidUrl) == 1)
        {
            $postPage = Http::get($vidUrl);
            //die($postPage);
            if (!empty($postPage) && preg_match('/<link\s+rel="canonical"\s+href="([^"]+)"\s*\/>/s', $postPage, $matched) == 1)
            {
                $vidID = trim((string)strrchr(trim($matched[1], "/"), "/"), "/");
                $vidUrl = $matched[1];
            }
        }
        $videoInfo = array('extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $vidID, 'title' => 'Unknown', 'thumb_preview' => 'https://img.youtube.com/vi/oops/1.jpg', 'lengthSeconds' => 0, 'videos' => [], 'audioOnly' => []);
        $vidPage = Http::withHeaders([
            'Host' => (string)parse_url($vidUrl, PHP_URL_HOST),
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'User-Agent' => $this->_userAgent
        ])->get($vidUrl);
        //die($vidPage);
        if ($vidPage->successful())
        {
            if (preg_match('/<meta.*?\s*property="og:image"\s*content="([^"]+)"\s*.*?\/>/is', $vidPage->body(), $imgmatch) == 1)
            {
                $videoInfo['thumb_preview'] = (isset($imgmatch[1]) && !empty($imgmatch[1])) ? preg_replace('/&amp;/', "&", $imgmatch[1]) : $videoInfo['thumb_preview'];
            }
            if (preg_match('/<title[^>]*>([^<]*)<\/title>/si', $vidPage->body(), $matches) == 1)
            {
                $vidTitle = preg_replace('/[^\p{L}\p{N}\p{P} ]+/u', "", html_entity_decode(trim($matches[1]), ENT_QUOTES));
                $vidTitle = preg_replace('/(\s*facebook)$/i', "", trim($vidTitle));
                $videoInfo['title'] = (!empty($vidTitle)) ? ((strlen($vidTitle) > 50) ? substr($vidTitle, 0, 50) . "..." : $vidTitle) : $videoInfo['title'];
            }
            $videoInfo['lengthSeconds'] = (preg_match('/"playable_duration_in_ms"\s*:\s*(\d+)/i', $vidPage->body(), $matches) == 1) ? (int)$matches[1] / 1000 : $videoInfo['lengthSeconds'];
            //die($vidPage->body());
            if ((int)preg_match_all('/\[\{"media":(\{.+?\})\}\]/s', $vidPage->body(), $jsonWithMedia) > 0)
            {
                //die(print_r($jsonWithMedia[1]));
                foreach ($jsonWithMedia[1] as $media)
                {
                    $mediaInfo = json_decode($media, true);
                    if (json_last_error() == JSON_ERROR_NONE)
                    {
                        if (isset($mediaInfo['__typename'], $mediaInfo['id'], $mediaInfo['browser_native_sd_url']) && $mediaInfo['__typename'] == "Video" && $mediaInfo['id'] == $vidID)
                        {
                            $videoInfo['title'] = (isset($mediaInfo['savable_description']['text'])) ? ((strlen($mediaInfo['savable_description']['text']) > 50) ? substr($mediaInfo['savable_description']['text'], 0, 50) . "..." : $mediaInfo['savable_description']['text']) : $videoInfo['title'];
                            $reqContext = stream_context_create(['http' => ['header' => "User-Agent: " . $this->_userAgent . "\r\n"]]);
                            if (isset($mediaInfo['browser_native_hd_url']) && !empty($mediaInfo['browser_native_hd_url']))
                            {
                                $reqHeadHd = get_headers($mediaInfo['browser_native_hd_url'], true, $reqContext);
                                //die(print_r($reqHeadHd));
                                $videoInfo['videos'][] = [
                                    'contentLength' => (isset($reqHeadHd['Content-Length'])) ? max((array)$reqHeadHd['Content-Length']) : '',
                                    'qualityLabel' => '720p',
                                    'url' => stripslashes($mediaInfo['browser_native_hd_url']),
                                    'ext' => 'mp4'
                                ];
                            }
                            $reqHeadSd = get_headers($mediaInfo['browser_native_sd_url'], true, $reqContext);
                            //die(print_r($reqHeadSd));
                            $videoInfo['videos'][] = [
                                'contentLength' => (isset($reqHeadSd['Content-Length'])) ? max((array)$reqHeadSd['Content-Length']) : '',
                                'qualityLabel' => '360p',
                                'url' => stripslashes($mediaInfo['browser_native_sd_url']),
                                'ext' => 'mp4'
                            ];
                            if (isset($mediaInfo['dash_manifest']))
                            {
                                $manifestXml = simplexml_load_string($mediaInfo['dash_manifest']);
                                if ($manifestXml !== false && !empty($manifestXml))
                                {
                                    $manifestJson = json_encode($manifestXml);
                                    $manifestArr = json_decode($manifestJson, true);
                                    //die(print_r($manifestArr));
                                    if (isset($manifestArr['Period']['AdaptationSet']) && is_array($manifestArr['Period']['AdaptationSet']))
                                    {
                                        foreach ($manifestArr['Period']['AdaptationSet'] as $urlSet)
                                        {
                                            $isAudio = isset($urlSet['@attributes']['mimeType']) && preg_match('/^((video|audio)\/(\w+))/', $urlSet['@attributes']['mimeType'], $avMatch) == 1 && $avMatch[2] == "audio";
                                            if (isset($urlSet['Representation']) && is_array($urlSet['Representation']))
                                            {
                                                if (!isset($urlSet['Representation'][0]))
                                                {
                                                    $urlSet['Representation'][0] = $urlSet['Representation'];
                                                    $urlSet['Representation'] = array_filter($urlSet['Representation'], "is_numeric", ARRAY_FILTER_USE_KEY);
                                                }
                                                foreach ($urlSet['Representation'] as $fileInfo)
                                                {
                                                    $isAudio = (!$isAudio) ? isset($fileInfo['@attributes']['mimeType']) && preg_match('/^((video|audio)\/(\w+))/', $fileInfo['@attributes']['mimeType'], $avMatch) == 1 && $avMatch[2] == "audio" : $isAudio;
                                                    if ($isAudio && isset($fileInfo['BaseURL']))
                                                    {
                                                        $reqHead = get_headers($fileInfo['BaseURL'], true, $reqContext);
                                                        $videoInfo['audioOnly'][] = [
                                                            'contentLength' => (isset($reqHead['Content-Length'])) ? max((array)$reqHead['Content-Length']) : '',
                                                            'qualityLabel' => ((isset($fileInfo['@attributes']['bandwidth'])) ? ceil((int)$fileInfo['@attributes']['bandwidth'] / 1000) : "128") . "kb",
                                                            'url' => $fileInfo['BaseURL'],
                                                            'ext' => $avMatch[3]
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                        $videoInfo['audioOnly'] = $this->MultiArrayVidSort($videoInfo['audioOnly']);
                                    }
                                }
                            }
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
