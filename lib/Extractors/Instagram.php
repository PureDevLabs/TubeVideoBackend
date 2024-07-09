<?php

namespace PureDevLabs\Extractors;

use PureDevLabs\HttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PureDevLabs\Extractors\Extractor;

class Instagram extends Extractor
{
    // Constants
    const _COOKIE_PATTERN = '/(\S+)\t(\S+)[\n\r]+/is';
    const _PARAMS = array(
        'url_root' => array(
            'instagram.com/p/',
            'www.instagram.com/p/',
            'www.instagram.com/tv/',
            'www.instagram.com/reel/',
            'www.instagram.com/#wildcard#/p/',
            'www.instagram.com/#wildcard#/tv/',
            'www.instagram.com/#wildcard#/reel/'
        )
    );

    // Fields
    protected $_userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 244.0.0.12.112 (iPhone13,2; iOS 15_5; en_US; en-US; scale=3.00; 1170x2532; 383361019)';

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
        $vidId = $this->ExtractVideoId($vidUrl);
        $videoInfo = array('extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $vidId, 'title' => 'Unknown', 'thumb_preview' => 'https://img.youtube.com/vi/oops/1.jpg', 'lengthSeconds' => 0, 'videos' => [], 'audioOnly' => []);
        $jsonData = $this->GenerateJsonData($vidId, $vidUrl);
        //die(print_r($jsonData));
        $media = (!isset($jsonData['shortcode_media'])) ? ((!isset($jsonData['data']['shortcode_media'])) ? [] : $jsonData['data']['shortcode_media']) : $jsonData['shortcode_media'];
        if (!empty($media))
        {
            //die(print_r($media));
            $videoInfo['videoId'] = $media['shortcode'];
            $caption = (!isset($media['edge_media_to_caption']['edges'][0]['node']['text'])) ? ((!isset($media['caption'])) ? '' : $media['caption']) : $media['edge_media_to_caption']['edges'][0]['node']['text'];
            //die($caption);
            $videoInfo['title'] = $this->FormatMediaTitle($caption, $videoInfo);
            $videoInfo['thumb_preview'] = (!isset($media['display_src'])) ? ((!isset($media['display_url'])) ? $videoInfo['thumb_preview'] : $media['display_url']) : $media['display_src'];
            $videoAvailable = isset($media['video_url'], $media['is_video']) && (bool)$media['is_video'];
            if ($videoAvailable)
            {
                $reqHead = get_headers($media['video_url'], true);
                $videoInfo['videos'][] = [
                    'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                    'qualityLabel' => (isset($media['dimensions']['width'])) ? $media['dimensions']['width'] . "p" : 'sd',
                    'url' => $media['video_url'],
                    'ext' => 'mp4'
                ];
            }
            if (empty($videoInfo['videos']) && isset($media['edge_sidecar_to_children']['edges']) && is_array($media['edge_sidecar_to_children']['edges']))
            {
                //die(print_r($media['edge_sidecar_to_children']['edges']));
                foreach ($media['edge_sidecar_to_children']['edges'] as $edge)
                {
                    $videoAvailable = isset($edge['node']['video_url'], $edge['node']['is_video']) && (bool)$edge['node']['is_video'];
                    if ($videoAvailable)
                    {
                        $reqHead = get_headers($edge['node']['video_url'], true);
                        $videoInfo['videos'][] = [
                            'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                            'qualityLabel' => (isset($media['dimensions']['width'])) ? $media['dimensions']['width'] . "p" : 'sd',
                            'url' => $edge['node']['video_url'],
                            'ext' => 'mp4'
                        ];
                        break;
                    }
                }
            }
        }
        if (empty($videoInfo['videos']))
        {
            $media = (isset($jsonData['items'][0])) ? $jsonData['items'][0] : [];
            if (!empty($media))
            {
                $videoInfo['videoId'] = $media['code'];
                $caption = (isset($media['caption']['text'])) ? $media['caption']['text'] : '';
                //die($caption);
                $videoInfo['title'] = $this->FormatMediaTitle($caption, $videoInfo);
                $videoInfo['thumb_preview'] = (!isset($media['image_versions2']['candidates'][0]['url'])) ? ((!isset($media['image_versions2']['additional_candidates']['first_frame']['url'])) ? $videoInfo['thumb_preview'] : $media['image_versions2']['additional_candidates']['first_frame']['url']) : $media['image_versions2']['candidates'][0]['url'];
                $videoInfo['lengthSeconds'] = (isset($media['video_duration'])) ? $media['video_duration'] : $videoInfo['lengthSeconds'];
                $videoAvailable = isset($media['video_versions'][0]['url']);
                if ($videoAvailable)
                {
                    foreach ($media['video_versions'] as $version)
                    {
                        $reqHead = get_headers($version['url'], true);
                        $videoInfo['videos'][] = [
                            'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                            'qualityLabel' => (isset($version['width'])) ? $version['width'] . "p" : 'sd',
                            'url' => $version['url'],
                            'ext' => 'mp4'
                        ];
                    }
                }
                if (empty($videoInfo['videos']) && isset($media['carousel_media']) && is_array($media['carousel_media']))
                {
                    //die(print_r($media['carousel_media']));
                    foreach ($media['carousel_media'] as $cm)
                    {
                        $videoInfo['thumb_preview'] = (!isset($cm['image_versions2']['candidates'][0]['url'])) ? ((!isset($cm['image_versions2']['additional_candidates']['first_frame']['url'])) ? $videoInfo['thumb_preview'] : $cm['image_versions2']['additional_candidates']['first_frame']['url']) : $cm['image_versions2']['candidates'][0]['url'];
                        $videoInfo['lengthSeconds'] = (isset($cm['video_duration'])) ? $cm['video_duration'] : $videoInfo['lengthSeconds'];
                        $videoAvailable = isset($cm['video_versions'][0]['url']);
                        if ($videoAvailable)
                        {
                            foreach ($cm['video_versions'] as $version)
                            {
                                $reqHead = get_headers($version['url'], true);
                                $videoInfo['videos'][] = [
                                    'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                                    'qualityLabel' => (isset($version['width'])) ? $version['width'] . "p" : 'sd',
                                    'url' => $version['url'],
                                    'ext' => 'mp4'
                                ];
                            }
                            break;
                        }
                    }
                }
            }
        }
        $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
        //die(print_r($videoInfo));
        return ($videoAvailable) ? $videoInfo : [
            'error' => true,
            'httpCode' => 400,
            'errorMsg' => 'Bad Request',
            'message' => 'Unsupported Instagram media format'
        ];
    }

    private function GenerateJsonData($vidId, $vidUrl)
    {
        if (!Storage::exists('Instagram/Cookie.txt')) Storage::put('Instagram/Cookie.txt', '');

        $cookieFile = Storage::path("Instagram/Cookie.txt");

        $jsonData = [];
        $reqHeaders = [
            'X-IG-App-ID' => '936619743392459',
            'X-ASBD-ID' => '198387',
            'X-IG-WWW-Claim' => '0',
            'Origin' => 'https://www.instagram.com',
            'Accept' => '*/*',
            'User-Agent' => $this->_userAgent,
        ];

        $vidPage = app(HttpClient::class)->Get('https://i.instagram.com/api/v1/media/' . $this->ConvertIdToNum($vidId) . '/info/', $reqHeaders, $cookieFile);
        $json = json_decode($vidPage, true);
        $jsonData = (json_last_error() === JSON_ERROR_NONE && !empty($json)) ? $json : $jsonData;

        if (empty($jsonData))
        {
            $vidPage = Http::withHeaders(['User-Agent' => $this->_userAgent])->withOptions(['force_ip_resolve' => 'v4'])->get(trim(preg_replace('/(\?.*)$/', "", $vidUrl), "/") . '/embed/');
            //die($vidPage->body());
            $jsonData = ($vidPage->successful() && preg_match('/window.__additionalDataLoaded\(\'[^\']+\',\s*(\{.+?\})\);/', $vidPage->body(), $matched) == 1) ? json_decode($matched[1], true) : $jsonData;
        }
        if (empty($jsonData))
        {
            $vidPage = Http::withHeaders($reqHeaders + ['X-Requested-With' => 'XMLHttpRequest', 'Referer' => $vidUrl])->withOptions(['force_ip_resolve' => 'v4'])->get('https://www.instagram.com/graphql/query/', [
                'query_hash' => '9f8827793ef34641b2fb195d4d41151c',
                'variables' => '{"shortcode":"' . $vidId . '","child_comment_count":3,"fetch_comment_count":40,"parent_comment_count":24,"has_threaded_comments":true}'
            ]);

            //die($vidPage->body());
            $jsonData = ($vidPage->successful() && !empty($vidPage->json())) ? $vidPage->json() : $jsonData;
        }
        return $jsonData;
    }

    private function ConvertIdToNum($vidId)
    {
        $num = 0;
        $encodingChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $charTableArr = str_split($encodingChars);
        $charTableArr = $this->RandShuffleArr($charTableArr);
        //print_r($charTableArr);
        $base = count($charTableArr);
        $idArr = str_split($vidId);
        foreach ($idArr as $char)
        {
            $num = $num * $base + $charTableArr[$char];
            //echo $num . "<br>";
        }
        //die();
        return $num;
    }

    private function RandShuffleArr(array $arr)
    {
        $shuffledArr = [];
        $keys = array_keys($arr);
        if (shuffle($keys))
        {
            foreach ($keys as $key) $shuffledArr[$arr[$key]] = $key;
        }
        return $shuffledArr;
    }

    private function FormatMediaTitle($caption, array $videoInfo)
    {
        $vTitleArr = explode("\n", wordwrap(preg_replace('/\n|\r|\t/', "", $this->UnicodeToHtmlEntities($caption)), 55, "\n", true));
        //die(print_r($vTitleArr));
        $vTitle = trim($vTitleArr[0]) . ((count($vTitleArr) > 1) ? "..." : "");
        return (!empty($vTitle)) ? $vTitle : $videoInfo['title'];
    }
    #endregion
}
