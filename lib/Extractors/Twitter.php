<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;

class Twitter extends Extractor
{
    // Constants
    const _PARAMS = array(
        'url_root' => array(
            'twitter.com/',
            'mobile.twitter.com/',
            'x.com/'
        )
    );
    const _GUEST_TOKEN = 'AAAAAAAAAAAAAAAAAAAAAPYXBAAAAAAACLXUNDekMxqa8h%2F40K4moUkGsoc%3DTYfbDKbT3jJPCEVnMYqilB28NHfOPqkca3qaAxGfsyKCs0wRbw';

    // Fields
    protected $_userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

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

        $playbackUrl = '';
        $vmapUrl = '';

        // Try video page
        $videoPage = Http::get('https://twitter.com/i/videos/tweet/' . $vidId);
        if ($videoPage->successful() && preg_match('/data-(?:player-)?config="([^"]+)"/', $videoPage->body(), $matches) == 1)
        {
            $jsonInfo = json_decode(htmlspecialchars_decode(trim($matches[1])), true);
            //die(print_r($jsonInfo));
            $vmapUrl = (!isset($jsonInfo['vmapUrl'])) ? ((!isset($jsonInfo['vmap_url'])) ? '' : $jsonInfo['vmap_url']) : $jsonInfo['vmapUrl'];
        }
        else
        {
            // Try Twitter API as guest
            $vidPage = Http::withoutVerifying()->withHeaders(['User-Agent' => $this->_userAgent])->withOptions(["verify" => false])->get($vidUrl);
            if ($vidPage->successful())
            {
                $cookies = $vidPage->cookies()->toArray();
                //die(print_r($cookies));
                if (!empty($cookies))
                {
                    $cookieStr = '';
                    foreach ($cookies as $cookie)
                    {
                        $cookieStr .= $cookie['Name'] . "=" . $cookie['Value'];
                        $cookieStr .= ($cookie != end($cookies)) ? "; " : "";
                    }
                    //die($cookieStr);
                    $apiResponse = Http::withoutVerifying()->withHeaders([
                        'User-Agent' => $this->_userAgent,
                        'Authorization' => 'Bearer ' . self::_GUEST_TOKEN,
                        'Referer' => $vidUrl,
                        'Cookie' => $cookieStr,
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->withOptions(["verify" => false])->post('https://api.twitter.com/1.1/guest/activate.json');
                    if ($apiResponse->successful())
                    {
                        $jsonData = $apiResponse->json();
                        //die(print_r($jsonData));
                        if (isset($jsonData['guest_token']))
                        {
                            $reqHeaders = [
                                'Authorization' => 'Bearer ' . self::_GUEST_TOKEN,
                                'x-guest-token' => $jsonData['guest_token']
                            ];
                            $apiResponse = Http::withoutVerifying()->withHeaders($reqHeaders)->withOptions(["verify" => false])->get('https://publish.twitter.com/oembed?url=' . $vidUrl);
                            if ($apiResponse->successful())
                            {
                                $jsonData = $apiResponse->json();
                                //die(print_r($jsonData));
                                $jsonInfo['status']['text'] = (isset($jsonData['html']) && !empty($jsonData['html'])) ? strip_tags(preg_replace('/.*?<p[^>]*>(.+?)<\/p>.*/s', "$1", $jsonData['html'])) : $videoInfo['title'];
                            }

                            $apiResponse = Http::withoutVerifying()->withHeaders($reqHeaders)->withOptions(["verify" => false])->get('https://api.twitter.com/graphql/xBtHv5-Xsk268T5ng_OGNg/TweetResultByRestId?variables=%7B%22tweetId%22%3A%22' . $vidId . '%22%2C%22withCommunity%22%3Afalse%2C%22includePromotedContent%22%3Afalse%2C%22withVoice%22%3Afalse%7D&features=%7B%22creator_subscriptions_tweet_preview_api_enabled%22%3Atrue%2C%22c9s_tweet_anatomy_moderator_badge_enabled%22%3Atrue%2C%22tweetypie_unmention_optimization_enabled%22%3Atrue%2C%22responsive_web_edit_tweet_api_enabled%22%3Atrue%2C%22graphql_is_translatable_rweb_tweet_is_translatable_enabled%22%3Atrue%2C%22view_counts_everywhere_api_enabled%22%3Atrue%2C%22longform_notetweets_consumption_enabled%22%3Atrue%2C%22responsive_web_twitter_article_tweet_consumption_enabled%22%3Atrue%2C%22tweet_awards_web_tipping_enabled%22%3Afalse%2C%22freedom_of_speech_not_reach_fetch_enabled%22%3Atrue%2C%22standardized_nudges_misinfo%22%3Atrue%2C%22tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled%22%3Atrue%2C%22rweb_video_timestamps_enabled%22%3Atrue%2C%22longform_notetweets_rich_text_read_enabled%22%3Atrue%2C%22longform_notetweets_inline_media_enabled%22%3Atrue%2C%22responsive_web_graphql_exclude_directive_enabled%22%3Atrue%2C%22verified_phone_label_enabled%22%3Afalse%2C%22responsive_web_graphql_skip_user_profile_image_extensions_enabled%22%3Afalse%2C%22responsive_web_graphql_timeline_navigation_enabled%22%3Atrue%2C%22responsive_web_enhance_cards_enabled%22%3Afalse%7D&fieldToggles=%7B%22withArticleRichContentState%22%3Atrue%7D');
                            if ($apiResponse->successful())
                            {
                                $jsonData = $apiResponse->json();
                                //die(print_r($jsonData));
                                if (isset($jsonData['data']['tweetResult']['result']['legacy']['entities']['media'][0]['video_info']['variants']))
                                {
                                    $vidInfo = $jsonData['data']['tweetResult']['result']['legacy']['entities']['media'][0]['video_info']['variants'];
                                    if (is_array($vidInfo) && !empty($vidInfo))
                                    {
                                        foreach ($vidInfo as $vi)
                                        {
                                            if ($vi['content_type'] == "video/mp4")
                                            {
                                                $reqHeaders = get_headers($vi['url'], true, stream_context_create(['http' => ['header' => "User-Agent: " . $this->_userAgent . "\r\n"]]));
                                                $videoInfo['videos'][] = [
                                                    'contentLength' => (isset($reqHeaders['Content-Length'])) ? max((array)$reqHeaders['Content-Length']) : '',
                                                    'qualityLabel' => preg_replace('/.*?\/(\d+)x\d+\/.*/', "$1", $vi['url']) . "p",
                                                    'url' => $vi['url'],
                                                    'ext' => 'mp4'
                                                ];
                                            }
                                        }
                                    }
                                }
                            }

                            if (empty($videoInfo['videos']))
                            {
                                $apiResponse = Http::withoutVerifying()->withHeaders($reqHeaders)->withOptions(["verify" => false])->get('https://api.twitter.com/1.1/videos/tweet/config/' . $vidId);
                                if ($apiResponse->successful())
                                {
                                    $jsonData = $apiResponse->json();
                                    //die(print_r($jsonData));
                                    $playbackUrl = (isset($jsonData['track']['playbackUrl'])) ? $jsonData['track']['playbackUrl'] : '';
                                    $vmapUrl = (isset($jsonData['track']['vmapUrl'])) ? $jsonData['track']['vmapUrl'] : '';
                                }
                            }
                        }
                    }
                }
            }
        }

        if (empty($videoInfo['videos']) && !empty($playbackUrl) && preg_match('/^((\.m3u8)(.*))$/', strrchr($playbackUrl, ".")) == 1)
        {
            $videoInfo['videos'] = $this->ParsePlaylist($playbackUrl);
        }
        elseif (empty($videoInfo['videos']) && !empty($vmapUrl))
        {
            $vmapFile = Http::get($vmapUrl);
            //die($vmapFile);
            if ($vmapFile->successful())
            {
                try
                {
                    $sxe = @new \SimpleXMLElement(trim($vmapFile->body()));
                    $mediaFile = $sxe->xpath('.//MediaFile');
                    if (is_array($mediaFile))
                    {
                        foreach ($mediaFile as $mf)
                        {
                            $filePath = parse_url($mf, PHP_URL_PATH);
                            if ($filePath !== false && !is_null($filePath))
                            {
                                $fileExt = strrchr($filePath, ".");
                                if ($fileExt !== false)
                                {
                                    if ($fileExt == ".m3u8" && empty($videoInfo['videos']))
                                    {
                                        $videoInfo['videos'] = $this->ParsePlaylist(trim((string)$mf));
                                        break;
                                    }
                                    if ($fileExt != ".m3u8")
                                    {
                                        $videoInfo['videos'][] = [
                                            'qualityLabel' => 'sd',
                                            'url' => trim((string)$mf)
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
                catch (Exception $ex) {}
            }
        }
        //die(print_r($videoInfo['videos']));
        if (empty($videoInfo['videos']) && isset($jsonInfo['video_url']))
        {
            $videoInfo['videos'] = $this->ParsePlaylist($jsonInfo['video_url']);
        }
        if (empty($videoInfo['videos']) && !empty($playbackUrl))
        {
            $videoInfo['videos'][] = [
                'contentLength' => '',
                'qualityLabel' => 'sd',
                'url' => $playbackUrl,
                'ext' => 'mp4'
            ];
        }
        $videoInfo['videos'] = $this->MultiArrayVidSort($videoInfo['videos']);
        //die(print_r($videoInfo['videos']));
        $videoInfo['title'] = (isset($jsonInfo['status']['text']) && !empty($jsonInfo['status']['text'])) ? substr(trim($jsonInfo['status']['text']), 0, 100) : $videoInfo['title'];
        $videoInfo['title'] = $this->UnicodeToHtmlEntities($videoInfo['title']);
        $videoInfo['thumb_preview'] = (!isset($jsonInfo['image_src'])) ? ((!isset($jsonData['posterImage'])) ? $videoInfo['thumb_preview'] : $jsonData['posterImage']) : $jsonInfo['image_src'];
        $videoInfo['lengthSeconds'] = (!isset($jsonInfo['duration'])) ? ((!isset($jsonData['track']['durationMs'])) ? $videoInfo['lengthSeconds'] : (int)$jsonData['track']['durationMs'] / 1000) : (int)$jsonInfo['duration'] / 1000;

        //die(print_r($videoInfo));
        return $videoInfo;
    }

    private function ParsePlaylist($playlistUrl)
    {
        $urls = array();
        $m3u8UrlInfo = parse_url($playlistUrl);
        $m3u8UrlRoot =  $m3u8UrlInfo["scheme"] . "://" . $m3u8UrlInfo["host"];
        //die($m3u8UrlRoot);
        $m3u8 = Http::get($playlistUrl);
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
                    if (preg_match('/\/((\d+)x(\d+))\//i', $val, $matches) == 1)
                    {
                        $urls[] = [
                            'contentLength' => '',
                            'qualityLabel' => (string)min((int)$matches[2], (int)$matches[3]) . "p",
                            'url' => ((preg_match('/^(https?)/i', $val) == 1) ? '' : $m3u8UrlRoot) . $val,
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
