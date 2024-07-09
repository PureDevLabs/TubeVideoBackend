<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use PureDevLabs\Extractors\Extractor;

class Twitch extends Extractor
{
    // Constants
    const _TWITCH_USHER_URL = 'https://usher.ttvnw.net/vod/';
    const _TWITCH_GRAPHQL_URL = 'https://gql.twitch.tv/gql';
    const _CLIENT_ID = 'kimne78kx3ncx6brgo4mv6wki5h1ko';
    const _SHA256_VIDEO_PLAYER = '07e99e4d56c5a7c67117a154777b0baf85a5ffefa393b213f4bc712ccaf85dd6';
    const _SHA256_VIDEO_META_DATA = '226edb3e692509f727fd56821f5653c05740242c82b0388883e0c0e75dcbf687';
    const _SHA256_PLAYBACK_ACCESS_TOKEN = '0828119ded1c13477966434e15800ff57ddacf13ba1911c129dc2200705b0712';
    const _TWITCH_CHANNEL_PATTERN = '/^((https?:)?\/\/[^\.]+\.cloudfront\.net\/[^_\/]+_([^\/]+?)_\d+_)/';
    const _TWITCH_HLS_QUALITY_PATTERN = '/\#EXT-X-MEDIA:(.*?)NAME="([^"]+)"(.*?)((https?:)?\/\/(.+?)\.m3u8)/si';
    const _PARAMS = [
        'url_root' => [
            'www.twitch.tv/videos/'
        ]
    ];

    // Fields
    private $_reqHeaders = [
        'Client-Id' => self::_CLIENT_ID
    ];

    public function GetDownloadLinks(string $url): array
    {
        return $this->GetVideoData($url);
    }

    private function GetVideoData(string $url): array
    {
        $vid = $this->ExtractVideoId($url);
        $vidInfo = ['extractor' => str_replace(__NAMESPACE__ . '\\', '', __CLASS__), 'videoId' => $vid, 'title' => 'Unknown', 'thumb_preview' => 'https://img.youtube.com/vi/oops/1.jpg', 'lengthSeconds' => 0, 'videos' => [], 'audioOnly' => []];
        $hlsData = $this->GetHlsPlaylist($vid);
        $videoURL = $hlsData['videos'][0]['url'] ?? '';

        if (preg_match(self::_TWITCH_CHANNEL_PATTERN, $videoURL, $match) == 1)
        {
            //die(print_r($match));
            $channelName = $match[3];
            $payload = [
                'operationName' => 'VideoMetadata',
                'variables' => [
                    'channelLogin' => $channelName,
                    'videoID' => $vid
                ],
                'extensions' => [
                    'persistedQuery' => [
                        'version' => 1,
                        'sha256Hash' => self::_SHA256_VIDEO_META_DATA
                    ]
                ]
            ];
            $response = Http::withHeaders($this->_reqHeaders)->post(self::_TWITCH_GRAPHQL_URL, $payload);
            if ($response->successful())
            {
                $json = $response->json();
                $vidInfo = [
                    'extractor' => $vidInfo['extractor'],
                    'videoId' => $vid,
                    'title' => $json['data']['video']['title'] ?? '',
                    'thumb_preview' => str_replace('{width}x{height}', '1280x720', $json['data']['video']['previewThumbnailURL'] ?? ''),
                    'lengthSeconds' => $json['data']['video']['lengthSeconds'] ?? 0,
                    'channelName' => $channelName,
                    'profileImageURL' => $json['data']['user']['profileImageURL'] ?? '',
                    'thumbnails' => [
                        [
                            'quality' => '1280x720',
                            'url' => str_replace('{width}x{height}', '1280x720', $json['data']['video']['previewThumbnailURL'] ?? '')
                        ],
                        [
                            'quality' => '480x320',
                            'url' => str_replace('{width}x{height}', '480x320', $json['data']['video']['previewThumbnailURL'] ?? '')
                        ],
                        [
                            'quality' => 'custom',
                            'url' => $json['data']['video']['previewThumbnailURL'] ?? ''
                        ],
                    ],
                    'publishedAt' => isset($json['data']['video']['publishedAt']) ? (int)strtotime($json['data']['video']['publishedAt']) : 0,
                    'game' => [
                        'name' => $json['data']['video']['game']['name']  ?? '',
                        'gameThumb' => [
                            [
                                'quality' => '300x400',
                                'url' => str_replace('{width}x{height}', '300x400', $json['data']['video']['game']['boxArtURL'] ?? '')
                            ],
                            [
                                'quality' => '188x250',
                                'url' => str_replace('{width}x{height}', '188x250', $json['data']['video']['game']['boxArtURL'] ?? '')
                            ],
                            [
                                'quality' => 'custom',
                                'url' => $json['data']['video']['game']['boxArtURL'] ?? ''
                            ],
                        ]
                    ]
                ];
            }
        }
        return array_merge($vidInfo, $hlsData);
    }

    private function GetHlsPlaylist(string $vid): array
    {
        $accessToken = $this->GetPlaybackAccessToken($vid);
        $masterHlsPayload = [
            'client_id' => self::_CLIENT_ID,
            'token' => $accessToken['data']['videoPlaybackAccessToken']['value'] ?? '',
            'sig' => $accessToken['data']['videoPlaybackAccessToken']['signature'] ?? '',
            'allow_source' => true,
            'allow_audio_only' => true
        ];
        $masterHlsUrl = self::_TWITCH_USHER_URL . $vid . '.m3u8?' . http_build_query($masterHlsPayload);
        $response = Http::get($masterHlsUrl);
        return ($response->successful() && !empty($response->body())) ? $this->ParsePlaylist($response->body()) : [];
    }

    private function GetPlaybackAccessToken(string $vid): array
    {
        $payload = [
            'operationName' => 'PlaybackAccessToken',
            'variables' => [
                'isLive' => false,
                'isVod' => true,
                'login' => '',
                'playerType' => 'embed',
                'vodID' => $vid
            ],
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => self::_SHA256_PLAYBACK_ACCESS_TOKEN
                ]
            ]
        ];
        $response = Http::withHeaders($this->_reqHeaders)->post(self::_TWITCH_GRAPHQL_URL, $payload);
        $tokenData = [];
        if ($response->successful())
        {
            $json = $response->json();
            $tokenData = (json_last_error() == JSON_ERROR_NONE) ? $json : $tokenData;
        }
        return $tokenData;
    }

    private function ParsePlaylist(string $hlsFile): array
    {
        $hlstreams = [];
        $success = preg_match_all(self::_TWITCH_HLS_QUALITY_PATTERN, $hlsFile, $matches, PREG_SET_ORDER, 0);
        if ((int)$success > 0)
        {
            foreach ($matches as $video)
            {
                $isAudio = isset($video[2]) && $video[2] === 'Audio Only';
                $mediaType = ($isAudio) ? 'audioOnly' : 'videos';
                $hlstreams[$mediaType][] = [
                    'contentLength' => '',
                    'qualityLabel' => ($isAudio) ? $video[2] : ($video[2] ?? ''),
                    'url' => $video[4] ?? '',
                    'ext' => ($isAudio) ? 'adts' : 'mp4'
                ];
            }
        }
        return $hlstreams;
    }
}
