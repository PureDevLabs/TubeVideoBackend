<?php

namespace PureDevLabs\Extractors;

use PureDevLabs\Utils;
use PureDevLabs\Parser;
use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\OauthToken;
use AuthSettings;

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
    const _MAX_UNPLAYABLE_TRIES = 10;
    const _BASE_JS = "YouTube/base.js";

    protected $Parser;
    protected $_authMethod;
    protected $_unplayableTries = 0;

    public function __construct()
    {
        $this->Parser = new Parser();
        $this->_authMethod = app(AuthSettings::class)->method;
    }

    public function GetDownloadLinks($url)
    {
        $id = $this->ExtractVideoId($url);
        $data = $this->ParseDownloadLinks($id);
        return $data;
    }

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
                    $formatsCombined = $this->DecodeNSig(array_merge($formats, $adaptiveFormats));

                    $itags = ['format' => $this->Parser->FormatedStreamsByItag(), 'audio' => $this->Parser->AudioStreamsByItag(), 'video' => $this->Parser->VideoStreamsByItag()];
                    foreach ($formatsCombined as $item)
                    {
                        $url = $this->ReturnDownloadFormatUrl($item);
                        $item['url'] = (!empty($url)) ? $url : null;
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
                    return [
                        'error' => true,
                        'httpCode' => 200,
                        'errorMessage' => $json['playabilityStatus']['reason'] ?? "Unknown reason",
                        'errorCode' => $json['playabilityStatus']['status'] ?? "Unknown status"
                    ];
                }
            }
        }
    }

    private function ReturnDownloadFormatUrl(array $format)
    {
        $url = (isset($format['url'])) ? $format['url'] : '';
        if (empty($url) && isset($format['signatureCipher']))
        {
            parse_str($format['signatureCipher'], $sigVars);
            if (isset($sigVars['url']))
            {
                $url = $sigVars['url'];
                unset($sigVars['url']);
                $url .= "&" . urldecode(http_build_query($sigVars));
            }
        }
        return $url;
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

    private function DecodeNSig(array $formats)
    {
        $decodedFormats = [];
        if ($this->_authMethod == "session")
        {
            $nsigs = [];
            foreach ($formats as $format)
            {
                $url = $this->ReturnDownloadFormatUrl($format);
                if (!empty($url))
                {
                    $url = urldecode($url);
                    $urlParts = parse_url($url);
                    parse_str($urlParts['query'], $urlVars);
                    if (isset($urlVars['n']) && !isset($nsigs[$urlVars['n']]))
                    {
                        $nsigs[$urlVars['n']] = $format;
                    }
                }
            }
            if (!empty($nsigs) && Storage::disk('local')->exists(self::_BASE_JS))
            {
                $basejsCode = Storage::disk('local')->get(self::_BASE_JS);
                $nsigCode = (!empty($basejsCode)) ? $this->GenerateNSigCode($basejsCode) : '';
                $nsigStr = implode(",", array_keys($nsigs));
                if (!empty($nsigCode))
                {
                    exec("bun run " . resource_path('js/nsig.js') . " " . escapeshellarg($nsigCode) . " " . escapeshellarg($nsigStr), $bunResponse);
                    $decodedNsigs = json_decode(implode("", $bunResponse), true);
                    if (json_last_error() == JSON_ERROR_NONE && array_keys($decodedNsigs) == array_keys($nsigs))
                    {
                        foreach ($formats as $format)
                        {
                            $url = $this->ReturnDownloadFormatUrl($format);
                            if (!empty($url))
                            {
                                $url = urldecode($url);
                                $urlParts = parse_url($url);
                                parse_str($urlParts['query'], $urlVars);
                                if (isset($urlVars['n'], $decodedNsigs[$urlVars['n']]))
                                {
                                    $urlVars['n'] = $decodedNsigs[$urlVars['n']];
                                    $format['url'] = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . "?" . http_build_query($urlVars);
                                    $decodedFormats[] = $format;
                                }
                            }
                        }
                    }
                }
            }
        }
        return (!empty($decodedFormats)) ? $decodedFormats : $formats;
    }

    private function GenerateNSigCode($basejsCode)
    {
        $playerJS = $basejsCode;
        $nsigCode = '';
        if (preg_match('/(?x)(?:\.get\("n"\)\)&&\(b=|(?:b=String\.fromCharCode\(110\)|(?P<str_idx>[a-zA-Z0-9_$.]+)&&\(b="nn"\[\+(?P=str_idx)\])(?:,[a-zA-Z0-9_$]+\(a\))?,c=a\.(?:get\(b\)|[a-zA-Z0-9_$]+\[b\]\|\|null)\)&&\(c=|\b(?P<var>[a-zA-Z0-9_$]+)=)(?P<nfunc>[a-zA-Z0-9_$]+)(?:\[(?P<idx>\d+)\])?\([a-zA-Z]\)(?(var),[a-zA-Z0-9_$]+\.set\("n"\,(?P=var)\),(?P=nfunc)\.length)/', $playerJS, $pmatch) == 1)
        {
            $fname = $pmatch['nfunc'];
            $findex = $pmatch['idx'];
            if (preg_match('/var ' . preg_quote($fname, "/") . '=\[([^\]]+)\];/', $playerJS, $pmatch2) == 1)
            {
                $funcs = explode(",", $pmatch2[1]);
                if (isset($funcs[$findex]))
                {
                    $fname = $funcs[$findex];
                    $fNamePattern = preg_quote($fname, "/");
                    if (preg_match('/((function\s+' . $fNamePattern . ')|([\{;,]\s*' . $fNamePattern . '\s*=\s*function)|(var\s+' . $fNamePattern . '\s*=\s*function))\s*\(([^\)]*)\)\s*\{(.+?)\};\n/s', $playerJS, $nsigFunc) == 1)
                    {
                        //die("<pre>" . print_r($nsigFunc, true) . "</pre>");
                        $nsigCode = $fname . ' = function(' . $nsigFunc[5] . '){' . $nsigFunc[6] . '}; ' . $fname . '(n);';
                    }
                }
            }
        }
        return $nsigCode;
    }

    private function GetYouTubeVideoData($vid)
    {
        $postDataReq = $this->GetSoftwareJsonData();
        if (!isset($postDataReq['error']))
        {
            $userAgent = 'Mozilla/5.0 (Linux; Android 12; SAMSUNG SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/16.0 Chrome/92.0.4515.166 Mobile Safari/537.36';
            $postData = [
                'context' => [
                    'client' => [
                        'clientName' => $postDataReq['reqParams']['androidParams']['clientName'] ?? 'ANDROID',
                        'clientVersion' => $postDataReq['reqParams']['androidParams']['clientVersion'] ?? '16.20'
                    ]
                ],
                'videoId' => $vid,
                'contentCheckOk' => true,
                'racyCheckOk' => true
            ];
            if ($this->_authMethod == "session")
            {
                $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36,gzip(gfe)';
                $postData['context']['client'] = [
                    'clientName' => $postDataReq['reqParams']['webParams']['clientName'] ?? 'WEB',
                    'clientVersion' => $postDataReq['reqParams']['webParams']['clientVersion'] ?? '2.20240726.00.00',
                    "visitorData" => Cache::get('trustedSession:visitorData', ''),
                    "userAgent" => $userAgent
                ];
                $postData['context']['user'] = [
                    'lockedSafetyMode' => false
                ];
                $postData['serviceIntegrityDimensions'] = [
                    "poToken" => Cache::get('trustedSession:poToken', '')
                ];
            }
            try
            {
                $response = Http::withOptions(['force_ip_resolve' => 'v' . env('APP_USE_IP_VERSION', 4)])->timeout(4)->withUserAgent($userAgent)->withHeaders($this->GeneratePostRequestHeaders())->post('https://www.youtube.com/youtubei/v1/player', $postData);

                $json = json_decode($response, true);
                if (json_last_error() == JSON_ERROR_NONE)
                {
                    if ($response->status() == 200)
                    {
                        $status = $json['playabilityStatus'] ?? null;
                        if (!is_null($status) && isset($status['status'], $status['reason']) && $status['status'] == 'UNPLAYABLE' && preg_match('/limit/', $status['reason']) == 1)
                        {
                            if ($this->_unplayableTries < self::_MAX_UNPLAYABLE_TRIES)
                            {
                                $this->_unplayableTries++;
                                return $this->GetYouTubeVideoData($vid);
                            }
                            else
                            {
                                return array(
                                    'error' => true,
                                    'httpCode' => $response->status(),
                                    'errorMessage' => $status['reason'],
                                    'errorCode' => $status['status']
                                );
                            }
                        }
                        return $response->body();
                    }
                    else
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
                                'errorMessage' => (preg_match('/authentication/i', $json['error']['message']) == 1) ? "Authentication failure" : $json['error']['message'],
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

    public function GeneratePostRequestHeaders($reqType=null)
    {
        $data = $this->GetSoftwareJsonData();
        if (!isset($data['error']))
        {
            $postHeaders = array();
            $origin = 'https://www.youtube.com';
            $timestamp = time();
            if (preg_match(self::_SAPISID_PATTERN, $data['reqParams']['cookie'], $matches) == 1)
            {
                $hash = sha1($timestamp . ' ' . $matches[1] . ' ' . $origin);
                $sapihash = 'SAPISIDHASH ' . $timestamp . '_' . $hash;
            }
            $auth = $sapihash ?? ((is_null($reqType) && $this->_authMethod == "oauth") ? $this->GenerateOAuthToken() : '');
            $postHeaders = [
                'Content-Type' => 'application/json',
                'x-origin' => $origin
            ];
            if ($this->_authMethod != "session")
            {
                $postHeaders += [
                    'X-Goog-Api-Key' => $data['reqParams']['apiKey'],
                    'Cookie' => $data['reqParams']['cookie'],
                    'Authorization' => $auth
                ];
            }
            return $postHeaders;
        }
        return $data;
    }

    private function GenerateOAuthToken()
    {
        $token = "";
        $tokens = Cache::rememberForever('oauth:tokens', function() {
            $tokenss = OauthToken::where('enabled', 1)->get();
            return json_encode($tokenss->toArray());
        });
        if (!empty($tokens))
        {
            $tokens = json_decode($tokens, true);
            if (isset($tokens[0]['access_token']))
            {
                $randomToken = mt_rand(0, count($tokens) - 1);
                $token = $tokens[$randomToken]['access_token'];
            }
        }
        return $token;
    }

    public function TestPlayerApiRequest($vid, $token="")
    {
        $output = [];
        $data = $this->GetSoftwareJsonData();
        if (!isset($data['error']))
        {
            $origin = 'https://www.youtube.com';
            $timestamp = time();
            if (preg_match(self::_SAPISID_PATTERN, $data['reqParams']['cookie'], $matches) == 1)
            {
                $hash = sha1($timestamp . ' ' . $matches[1] . ' ' . $origin);
                $sapihash = 'SAPISIDHASH ' . $timestamp . '_' . $hash;
            }
            $postHeaders = array(
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $data['reqParams']['apiKey'],
                'Cookie' => $data['reqParams']['cookie'],
                'Authorization' => $sapihash ?? $token,
                'x-origin' => $origin
            );
            $postData = array(
                'context' => array(
                    'client' => array(
                        'clientName' => (isset($data['reqParams']['androidParams']['clientName'])) ? $data['reqParams']['androidParams']['clientName'] : 'ANDROID',
                        'clientVersion' => (isset($data['reqParams']['androidParams']['clientVersion'])) ? $data['reqParams']['androidParams']['clientVersion'] : '16.20'
                    )
                ),
                'videoId' => $vid,
                'contentCheckOk' => true,
                'racyCheckOk' => true
            );

            $androidUserAgent = 'Mozilla/5.0 (Linux; Android 12; SAMSUNG SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/16.0 Chrome/92.0.4515.166 Mobile Safari/537.36';
            $response = Http::withOptions(['force_ip_resolve' => 'v' . env('APP_USE_IP_VERSION', 4)])->timeout(4)->withUserAgent($androidUserAgent)->withHeaders($postHeaders)->post('https://www.youtube.com/youtubei/v1/player', $postData);

            $output['responseCode'] = $response->status();
            $json = json_decode($response, true);
            if (json_last_error() == JSON_ERROR_NONE)
            {
                $output['status'] = $json['playabilityStatus']['status'] ?? 'No status available';
                $output['statusReason'] = $json['playabilityStatus']['reason'] ?? 'No status reason available';
                $output['errorCode'] = $json['error']['code'] ?? 'No error code';
                $output['errorMessage'] = $json['error']['message'] ?? 'No error message';
            }
        }
        return $output;
    }
}
