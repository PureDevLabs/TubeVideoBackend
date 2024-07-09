<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use PureDevLabs\Extractors\Extractor;

class Tiktok extends Extractor
{
    // Constants
    const _PARAMS = [
        'url_root' => [
            'www.tiktok.com/#wildcard#/video/',
            'vm.tiktok.com/'
        ]
    ];
    const _API_APP_VERSIONS = [
        ['26.1.3', '260103'],
        ['26.1.2', '260102'],
        ['26.1.1', '260101'],
        ['25.6.2', '250602']
    ];
    const _API_APP_NAME = 'trill';
    const _API_AID = 1180;
    const _API_HOSTNAME = 'api22-normal-c-useast2a.tiktokv.com';

    // Fields
    protected $_userAgent = 'facebookexternalhit/1.1';
    private $_avSources = [
        'videos' => [
            'bit_rate\.\d+\.play_addr',
            'download_addr',
            'play_addr'
        ]
    ];
    private $_imgSources = [
        'cover',
        'origin_cover'
    ];

    #region Public Methods
    public function GetDownloadLinks($url)
    {
        $data = $this->RetrieveVidInfo($url);
        return $data;
    }
    #endregion

    # region Private "Helper" Methods
    private function RetrieveVidInfo($vidUrl)
    {
        $vidID = $this->ExtractVideoId($vidUrl);
        $videoInfo = array('extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $vidID, 'title' => 'Unknown', 'thumb_preview' => 'https://img.youtube.com/vi/oops/1.jpg', 'lengthSeconds' => 0, 'reqDetails' => [], 'videos' => [], 'audioOnly' => []);
        if (preg_match('/^(https?:\/\/vm\.)/', $vidUrl) == 1)
        {
            $appRequest = Http::withHeaders(['User-Agent' => $this->_userAgent])->head($vidUrl);
            //die(print_r($appRequest->effectiveUri()->__toString()));
            $effectiveUri = $appRequest->effectiveUri();
            $videoInfo['id'] = $vidID = (!is_null($effectiveUri)) ? $this->ExtractVideoId($effectiveUri->__toString()) : $vidID;
        }
        $videoInfo = $this->TryTikTokApi($vidID, $videoInfo);
        if (empty($videoInfo['videos']))
        {
            $videoInfo = $this->TryTikTokWebpage($vidUrl, $videoInfo);
        }
        return $videoInfo;
    }

    private function TryTikTokApi($vidID, array $videoInfo)
    {
        $response = null;
        $isValidResponse = false;
        foreach (self::_API_APP_VERSIONS as $version)
        {
            $reqVars = [
                'aweme_id' => $vidID,
                'version_name' => $version[0],
                'version_code' => $version[1],
                'build_number' => $version[0],
                'manifest_version_code' => $version[1],
                'update_version_code' => $version[1],
                'openudid' => str_shuffle('0123456789abcdef'),
                'uuid' => $this->GenRandomString('0123456789', 16),
                '_rticket' => time() * 1000,
                'ts' => time(),
                'device_brand' => 'Google',
                'device_type' => 'Pixel 7',
                'device_platform' => 'android',
                'resolution' => '1080*2400',
                'dpi' => 420,
                'os_version' => '13',
                'os_api' => '29',
                'carrier_region' => 'US',
                'sys_region' => 'US',
                'region' => 'US',
                'app_name' => self::_API_APP_NAME,
                'app_language' => 'en',
                'language' => 'en',
                'timezone_name' => 'America/New_York',
                'timezone_offset' => '-14400',
                'channel' => 'googleplay',
                'ac' => 'wifi',
                'mcc_mnc' => '310260',
                'is_my_cn' => 0,
                'aid' => self::_API_AID,
                'ssmix' => 'a',
                'as' => 'a1qwert123',
                'cp' => 'cbfhckdckkde1'
            ];
            $reqHeaders = [
                'Cookie' => 'odin_tt=' . $this->GenRandomString('0123456789abcdef', 160),
                'User-Agent' => 'com.ss.android.ugc.' . self::_API_APP_NAME . '/' . $version[1] . ' (Linux; U; Android 13; en_US; Pixel 7; Build/TD1A.220804.031; Cronet/58.0.2991.0)',
                'Accept' => 'application/json'
            ];
            $response = Http::withHeaders($reqHeaders)->get('https://' . self::_API_HOSTNAME . '/aweme/v1/feed/?' . http_build_query($reqVars));
            $isValidResponse = $response->successful() && !empty($response->body());
            if ($isValidResponse) break;
        }
        //die($response->body());
        if ($isValidResponse)
        {
            $json = $response->json();
            //die(print_r($json));
            if (json_last_error() == JSON_ERROR_NONE)
            {
                $vidInfo = (isset($json['aweme_list'][0]['video'])) ? (array)$json['aweme_list'][0]['video'] : (array)$json['aweme_detail']['video'];
                $vidInfoSrc = Arr::dot($vidInfo);
                $vidInfoKeys = array_keys($vidInfoSrc);
                foreach ($this->_avSources as $mediaType => $sources)
                {
                    $srcRegex = '/(' . implode(")|(", $sources) . ')/';
                    $matchedKeys = preg_grep($srcRegex, $vidInfoKeys);
                    //die(print_r($matchedKeys));
                    $vidInfoArr = [];
                    foreach ($matchedKeys as $key)
                    {
                        $keyPrefix = (preg_match($srcRegex, $key, $kpmatches) == 1) ? $kpmatches[0] : $key;
                        $vidInfoArr[$keyPrefix][preg_replace('/^(' . preg_quote($keyPrefix, "/") . '\.)/', "", $key)] = $vidInfoSrc[$key];
                    }
                    //die(print_r($vidInfoArr));
                    foreach ($vidInfoArr as $keyPrefix => $vInfo)
                    {
                        $dlinks = preg_grep('/^(url_list)/', array_keys($vInfo));
                        foreach ($dlinks as $dlink)
                        {
                            parse_str((string)parse_url($vInfo[$dlink], PHP_URL_QUERY), $dlinkVars);
                            $dlinkExt = (isset($dlinkVars['mime_type'])) ? preg_replace('/^((video|audio)_)/', "", $dlinkVars['mime_type']) : '';
                            $vid = [
                                'contentLength' => $vInfo['data_size'],
                                'qualityLabel' => $vInfo['width'] . "p",
                                'watermark' => $this->HasWatermark($vInfo[$dlink]),
                                'url' => $vInfo[$dlink],
                                'ext' => $dlinkExt
                            ];
                            if (!in_array($vid, $videoInfo[$mediaType]))
                            {
                                $videoInfo[$mediaType][] = $vid;
                            }
                        }
                    }
                    $videoInfo[$mediaType] = $this->MultiArrayVidSort($videoInfo[$mediaType]);
                }
                //die(print_r($videoInfo));
                $imgs = [];
                foreach ($this->_imgSources as $source)
                {
                    if (isset($vidInfo[$source]['url_list']) && is_array($vidInfo[$source]['url_list']))
                    {
                        $imgs = array_unique(array_merge($imgs, $vidInfo[$source]['url_list']));
                    }
                }
                //die(print_r($imgs));
                if (!empty($videoInfo['videos']))
                {
                    $substrFunc = (function_exists('mb_substr')) ? 'mb_substr' : 'substr';
                    $videoInfo['title'] = (isset($json['aweme_detail']['desc']) && !empty($json['aweme_detail']['desc'])) ? $substrFunc(trim($json['aweme_detail']['desc']), 0, 100) : ((isset($json['aweme_list'][0]['desc']) && !empty($json['aweme_list'][0]['desc'])) ? $substrFunc(trim($json['aweme_list'][0]['desc']), 0, 100) : $videoInfo['title']);
                    $videoInfo['thumb_preview'] = (!empty($imgs)) ? current($imgs) : $videoInfo['thumb_preview'];
                    $videoInfo['lengthSeconds'] = (isset($json['aweme_detail']['video']['duration'])) ? (int)$json['aweme_detail']['video']['duration'] / 1000 : ((isset($json['aweme_list'][0]['video']['duration'])) ? (int)$json['aweme_list'][0]['video']['duration'] / 1000 : $videoInfo['lengthSeconds']);
                    $videoInfo['reqDetails'] = [
                        'source' => 'api',
                        'referer' => 'https://www.tiktok.com/',
                        'cookies' => $this->GenerateCookieString($response),
                        'headers' => ((bool) Config::get('app.show_extractor_headers')) ? base64_encode(gzcompress(json_encode($response->headers()), 9)) : ''
                    ];
                }
            }
        }
        return $videoInfo;
    }

    private function TryTikTokWebpage($vidUrl, array $videoInfo)
    {
        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($vidUrl);
        if ($response->successful() && !empty($response->body()) && preg_match('/<script[^>]+\bid="__UNIVERSAL_DATA_FOR_REHYDRATION__"[^>]*>(.+?)<\/script>/i', $response->body(), $matches) == 1)
        {
            $json = json_decode($matches[1], true);
            //die(print_r($json));
            if (isset($json['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct']) && !empty($json['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct']))
            {
                $vidData = $json['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct'];
                if (isset($vidData['video']['bitrateInfo']) && is_array($vidData['video']['bitrateInfo']))
                {
                    foreach ($vidData['video']['bitrateInfo'] as $vidInfo)
                    {
                        if (isset($vidInfo['PlayAddr']['UrlList'], $vidInfo['PlayAddr']['UrlKey']) && is_array($vidInfo['PlayAddr']['UrlList']) && !empty($vidInfo['PlayAddr']['UrlList']) && preg_match('/_(\d+p)_/', $vidInfo['PlayAddr']['UrlKey'], $qlmatch) == 1)
                        {
                            foreach ($vidInfo['PlayAddr']['UrlList'] as $playUrl)
                            {
                                $playUrl = json_decode('{"url":"' . $playUrl . '"}', true);
                                if (isset($playUrl['url']))
                                {
                                    parse_str((string)parse_url($playUrl['url'], PHP_URL_QUERY), $playUrlVars);
                                    $playUrlExt = (isset($playUrlVars['mime_type'])) ? preg_replace('/^((video|audio)_)/', "", $playUrlVars['mime_type']) : '';
                                    $vid = [
                                        'contentLength' => $vidInfo['PlayAddr']['DataSize'] ?? "",
                                        'qualityLabel' => $qlmatch[1],
                                        'watermark' => $this->HasWatermark($playUrl['url']),
                                        'url' => $playUrl['url'],
                                        'ext' => $playUrlExt
                                    ];
                                    if (!in_array($vid, $videoInfo['videos']))
                                    {
                                        $videoInfo['videos'][] = $vid;
                                    }
                                }
                            }
                        }
                    }
                    $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
                }
                if (!empty($videoInfo['videos']))
                {
                    $substrFunc = (function_exists('mb_substr')) ? 'mb_substr' : 'substr';
                    $videoInfo['title'] = (isset($vidData['desc']) && !empty($vidData['desc'])) ? $substrFunc(trim($vidData['desc']), 0, 100) : $videoInfo['title'];
                    $videoInfo['thumb_preview'] = (isset($vidData['video']['originCover']) && !empty($vidData['video']['originCover'])) ? $vidData['video']['originCover'] : $videoInfo['thumb_preview'];
                    $videoInfo['thumb_preview'] = current(json_decode('{"url":"' . $videoInfo['thumb_preview'] . '"}', true));
                    $videoInfo['lengthSeconds'] = (isset($vidData['video']['duration']) && !empty($vidData['video']['duration'])) ? (int)$vidData['video']['duration'] : $videoInfo['lengthSeconds'];
                    $videoInfo['reqDetails'] = [
                        'source' => 'web',
                        'referer' => 'https://www.tiktok.com/',
                        'cookies' => $this->GenerateCookieString($response),
                        'headers' => ((bool) Config::get('app.show_extractor_headers')) ? base64_encode(gzcompress(json_encode($response->headers()), 9)) : ''
                    ];
                }
            }
        }
        return $videoInfo;
    }

    private function GenRandomString($chars, $length)
    {
        $randStr = '';
        for ($i = 0; $i < $length; $i++)
        {
            $randStr = $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $randStr;
    }

    private function HasWatermark($url)
    {
        return preg_match('/[\?&]lr=unwatermarked\b/', $url) != 1;
    }
    # endregion
}
