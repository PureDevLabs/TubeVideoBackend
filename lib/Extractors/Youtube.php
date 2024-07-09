<?php

namespace PureDevLabs\Extractors;

use PureDevLabs\Utils;
use PureDevLabs\Parser;
use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;

class Youtube extends Extractor
{
    // Constants
    const _PARAMS = array(
        'url_root' => array(
            'www.youtube.com/watch?v=',
            'm.youtube.com/watch?v=',
            'youtu.be/',
            'music.youtube.com/watch?v=',
            'www.youtube.com/shorts/',
            'youtube.com/shorts/'
        )
    );
    const _CAPTCHA_PATTERN = '/^((<form)(.+?)(das_captcha)(.+?)(<\/form>))$/msi';
    const _SAPISID_PATTERN = '/SAPISID\s*=\s*(.+?)\;/s';

    protected $Parser;

    public function __construct()
    {
        $this->Parser = new Parser();
    }
    
    public function GetDownloadLinks($url)
    {
        $id = $this->ExtractVideoId($url);
        $data = $this->ParseDownloadLinks($id);
        return $data;
    }

    # Private Methods

    private function ParseDownloadLinks($vid)
    {
        $data = $this->GetYouTubeVideoData($vid);
        if (isset($data['error']))
        {
            return $data;
        }
        else
        {
            $json = json_decode($data, true);
            if (json_last_error() == JSON_ERROR_NONE && isset($json['playabilityStatus']['status']))
            {
                if ($json['playabilityStatus']['status'] == 'OK')
                {
                    $formats = Utils::ArrayGet($json, 'streamingData.formats');
                    $adaptiveFormats = Utils::ArrayGet($json, 'streamingData.adaptiveFormats');
                    $videoDetails = Utils::ArrayGet($json, 'videoDetails');
                    $formatsCombined = array_merge($formats, $adaptiveFormats);

                    $itags = ['format' => $this->Parser->FormatedStreamsByItag(), 'audio' => $this->Parser->AudioStreamsByItag(), 'video' => $this->Parser->VideoStreamsByItag()];
                    foreach ($formatsCombined as $item)
                    {
                        if (isset($item['itag'], $item['url']))
                        {
                            foreach ($itags as $catName => $itagGroup)
                            {
                                if (in_array($item['itag'], $itagGroup))
                                {
                                    ${$catName . 'Streams'}[] = $this->SortFormattedOutput($item);
                                    break;
                                }
                            }
                        }
                    }
                    return [
                        'extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__),
                        'videoId' => $videoDetails['videoId'] ?? $vid,
                        'title' => $videoDetails['title'] ?? "Unknown",
                        'lengthSeconds' => $videoDetails['lengthSeconds'] ?? '0',
                        'videos' => (isset($formatStreams)) ? array_reverse($formatStreams) : [],
                        'audioOnly' => (isset($audioStreams)) ? array_reverse($audioStreams) : [],
                        'videoOnly' => $videoStreams ?? []
                    ];
                }
                else
                {
                    return ($json['playabilityStatus']['status'] == 'LOGIN_REQUIRED') ? $this->UpdateSoftware() : [
                        'error' => true,
                        'httpCode' => 200,
                        'errorMessage' => $json['playabilityStatus']['reason'] ?? "Unknown reason",
                        'errorCode' => $json['playabilityStatus']['status']
                    ];
                }
            }
        }
    }

    private function SortFormattedOutput(array $item)
    {
        $contentLength = $item['contentLength'] ?? '0';
        if ($contentLength == 0)
        {
            $headers = (array)get_headers($item['url'], true);
            $contentLength = $headers['Content-Length'] ?? $contentLength;
            $contentLength = (is_array($contentLength)) ? (int)end($contentLength) : (int)$contentLength;
        }
        return array(
            'url' => $item['url'],
            'itag' => $item['itag'],
            'qualityLabel' => $item['qualityLabel'] ?? '',
            'bitrate' => isset($item['bitrate']) ? round($item['bitrate'] / 1000, 0) . ' kbps' : '',
            'format' => $this->Parser->ParseItagInfo($item['itag']),
            'ext' => $this->Parser->ParseItagFileExt($item['itag']),
            'contentLength' => $contentLength
        );
    }

    private function GetYouTubeVideoData($vid)
    {
        $postDataReq = $this->GetSoftwareJsonData();
        if (!isset($postDataReq['error']))
        {
            $postData = array(
                'context' => array(
                    'client' => array(
                        'clientName' => (isset($postDataReq['reqParams']['androidParams']['clientName'])) ? $postDataReq['reqParams']['androidParams']['clientName'] : 'ANDROID',
                        'clientVersion' => (isset($postDataReq['reqParams']['androidParams']['clientVersion'])) ? $postDataReq['reqParams']['androidParams']['clientVersion'] : '16.20'
                    )
                ),
                'videoId' => $vid,
                'contentCheckOk' => true,
                'racyCheckOk' => true
            );
            try
            {
                $androidUserAgent = 'Mozilla/5.0 (Linux; Android 12; SAMSUNG SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/16.0 Chrome/92.0.4515.166 Mobile Safari/537.36';
                $response = Http::withOptions(['force_ip_resolve' => 'v' . env('APP_USE_IP_VERSION', 4)])->timeout(4)->withUserAgent($androidUserAgent)->withHeaders($this->GeneratePostRequestHeaders())->post('https://www.youtube.com/youtubei/v1/player', $postData);

                if ($response->status() == 200)
                {
                    return $response->body();
                }
                else
                {
                    $json = json_decode($response, true);

                    if (json_last_error() == JSON_ERROR_NONE)
                    {
                        if (strpos($json['error']['message'], 'API key not valid. Please pass a valid API key.') !== false)
                        {
                            return $this->UpdateSoftware();
                        }
                        else
                        {
                            return array(
                                'error' => true,
                                'httpCode' => $response->status(),
                                'errorMessage' => $json['error']['message'],
                                'errorCode' => $json['error']['code']
                            );
                        }
                    }
                }
            }
            catch (\Throwable $th)
            {
                // dd($th->getMessage());
                return array(
                    'error' => true,
                    'httpCode' => 503,
                    "errorMsg" => "Connection Error",
                    "message" => "Can't connect to YouTube."
                );
            }
        }
        return $postDataReq;
    }

    public function GeneratePostRequestHeaders()
    {
        $data = $this->GetSoftwareJsonData();
        if (!isset($data['error']))
        {
            $postHeaders = array();
            if (preg_match(self::_SAPISID_PATTERN, $data['reqParams']['cookie'], $matches) == 1)
            {
                $origin = 'https://www.youtube.com';
                $timestamp = time();
                $hash = sha1($timestamp . ' ' . $matches[1] . ' ' . $origin);
                $sapihash = 'SAPISIDHASH ' . $timestamp . '_' . $hash;
                $postHeaders = array(
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $data['reqParams']['apiKey'],
                    'Cookie' => $data['reqParams']['cookie'],
                    'Authorization' => $sapihash,
                    'x-origin' => $origin
                );
            }
            return $postHeaders;
        }
        return $data;
    }
}
