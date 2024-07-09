<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PureDevLabs\Extractors\Extractor;

class SoundCloud extends Extractor
{
    // Constants
    const _PARAMS = array(
        'url_root' => array(
            'soundcloud.com/',
            'm.soundcloud.com/'
        )
    );
    const _API_BASE = 'https://api-v2.soundcloud.com/';
    const _CLIENT_ID = 'Uz4aPhG7GAl1VYGOnvOPW1wQ0M6xKtA9';
    const _CLIENT_ID_PATH = 'SoundCloud/sc_client_id.txt';

    // Fields
    private $_clientId = '';
    private $_pageUrlSuffix = '';
    private $_recursionLevel = 0;

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
        $vidUrl = preg_replace('/^((https?:\/\/)m\.)/', "$2", $vidUrl);
        $clientId = (Storage::disk('local')->exists(self::_CLIENT_ID_PATH)) ? Storage::get(self::_CLIENT_ID_PATH) : self::_CLIENT_ID;
        $this->_clientId = (!empty($clientId) && $clientId != self::_CLIENT_ID) ? trim($clientId) : self::_CLIENT_ID;
        $apiResponse = Http::get(self::_API_BASE . "resolve?url=" . $vidUrl . "&client_id=" . $this->GetClientId());
        if ($apiResponse->successful())
        {
            $jsonData = $apiResponse->json();
            if (json_last_error() == JSON_ERROR_NONE)
            {
                //die(print_r($jsonData));
                $duration = (isset($jsonData['duration'])) ? (int)$jsonData['duration'] / 1000 : $videoInfo['lengthSeconds'];
                $videoInfo = array('extractor' => $videoInfo['extractor'], 'videoId' => $jsonData['id'], 'title' => $jsonData['title'], 'thumb_preview' => $jsonData['artwork_url'], 'lengthSeconds' => $duration, 'videos' => [], 'audioOnly' => []);
                $this->SetPageUrlSuffix($jsonData['user']['permalink'] . "/" . $jsonData['permalink']);

                $formats = (isset($jsonData['media']['transcodings'])) ? $jsonData['media']['transcodings'] : array();
                //die(print_r($formats));
                if (is_array($formats) && !empty($formats))
                {
                    foreach ($formats as $url)
                    {
                        if (isset($url['url']))
                        {
                            $urlJson = Http::get($url['url'] . "?client_id=" . $this->GetClientId());
                            if ($urlJson->successful())
                            {
                                $jsonInfo = $urlJson->json();
                                //print_r($jsonInfo); echo "\n\n";
                                if (isset($jsonInfo['url']))
                                {
                                    $viInfo = preg_match('/(\.(\d+)\.([^\/]+)(\/playlist\.m3u8)?)$/', (string)parse_url($jsonInfo['url'], PHP_URL_PATH), $viMatch);
                                    //die(print_r($viMatch));
                                    $reqHead = ($viInfo == 1 && empty($viMatch[4])) ? get_headers($jsonInfo['url'], true) : [];
                                    $videoInfo['audioOnly'][] = [
                                        'contentLength' => (isset($reqHead['Content-Length'])) ? $reqHead['Content-Length'] : '',
                                        'qualityLabel' => (($viInfo == 1) ? $viMatch[2] : '128') . 'kb',
                                        'url' => $jsonInfo['url'],
                                        'ext' => (($viInfo == 1) ? preg_replace('/opus/', "ogg", $viMatch[3]) : 'mp3')
                                    ];
                                }
                            }
                        }
                    }
                    //die();
                    $videoInfo['audioOnly'] = $this->MultiArrayVidSort($videoInfo['audioOnly'], 'contentLength');
                }
            }
        }
        if (!$apiResponse->successful() && $this->GetRecursionLevel() == 0)
        {
            $videoInfo = $this->UpdateClientId($vidUrl);
        }
        //die(print_r($videoInfo));
        return $videoInfo;
    }

    private function UpdateClientId($vidUrl)
    {
        $success = false;
        $siteContent = Http::get($vidUrl);
        //die($siteContent);
        if ($siteContent->successful())
        {
            preg_match_all('/<script[^>]+src="([^"]+)"/is', $siteContent->body(), $scriptMatches);
            if (!empty($scriptMatches))
            {
                //die(print_r($scriptMatches));
                $scriptMatches = array_reverse($scriptMatches[1]);
                foreach ($scriptMatches as $sm)
                {
                    $scriptContent = Http::get($sm);
                    if ($scriptContent->successful() && preg_match('/client_id\s*:\s*"([0-9a-zA-Z]{32})"/', $scriptContent->body(), $ciMatch) == 1)
                    {
                        //die(print_r($ciMatch));
                        $success = Storage::disk('local')->put(self::_CLIENT_ID_PATH, $ciMatch[1], true);
                        if (!$success && Storage::disk('local')->exists(self::_CLIENT_ID_PATH)) Storage::disk('local')->delete(self::_CLIENT_ID_PATH);
                        break;
                    }
                }
            }
        }
        $this->SetRecursionLevel($this->GetRecursionLevel() + 1);
        return ($success) ? $this->RetrieveVidInfo($vidUrl) : array();
    }
    #endregion

    #region Properties
    private function GetClientId()
    {
        return $this->_clientId;
    }

    private function SetPageUrlSuffix($value)
    {
        $this->_pageUrlSuffix = $value;
    }
    public function GetPageUrlSuffix()
    {
        return $this->_pageUrlSuffix;
    }

    private function SetRecursionLevel($value)
    {
        $this->_recursionLevel = $value;
    }
    private function GetRecursionLevel()
    {
        return $this->_recursionLevel;
    }
    #endregion
}
