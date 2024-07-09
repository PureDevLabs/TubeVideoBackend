<?php

namespace PureDevLabs\Extractors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PureDevLabs\Core;

class Extractor extends Core
{
    // Constants
    const _WILDCARD_PATTERN = '/\\\#wildcard\\\#/';
    const _WILDCARD_REPLACE = '[^\\\\/]+';
    const _SOFTWARE_JSON_LOCAL = "YouTube/software.json";
    const _SOFTWARE_JSON_REMOTE = "https://puredevlabs.cc/Software-Updater/software.json?update=1";

    // Fields
    protected $_userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.19 (KHTML, like Gecko) Ubuntu/12.04 Chromium/18.0.1025.168 Chrome/18.0.1025.168 Safari/535.19';

    public static function ObtainSiteName($url)
    {
		$siteName = '';
		$extractors = scandir(__DIR__);
		$extractors = (is_array($extractors)) ? (array)preg_grep('/^(Extractor\.php|YoutubeData\.php|\.{1,2})$/i', $extractors, PREG_GREP_INVERT) : [];
		if (!empty($extractors))
		{
			foreach ($extractors as $extractor)
			{
				$extractor = explode(".", $extractor)[0];
				$params = (defined("PureDevLabs\\Extractors\\" . $extractor . "::_PARAMS")) ? constant("PureDevLabs\\Extractors\\" . $extractor . "::_PARAMS") : [];
				$urlRoots = (isset($params["url_root"])) ? $params["url_root"] : [];
				//print_r($urlRoots);
				if (!empty($urlRoots))
				{
					foreach ($urlRoots as $urlRoot)
					{
						if (preg_match('/^(http(s)?:\/\/' . preg_replace(self::_WILDCARD_PATTERN, self::_WILDCARD_REPLACE, preg_quote($urlRoot, "/")) . ')/', $url) == 1)
						{
							$siteName = $extractor;
							break 2;
						}
					}
				}
			}
		}
		return (!empty($siteName)) ? $siteName : [
			'error' => true,
			'code' => 400,
			'errorMsg' => 'Bad Request',
			'message' => 'Unsupported URL or video/audio site'
		];

    }

    public static function GetVideoId($vidUrl)
    {
        $self = new self();
        return $self->ExtractVideoId($vidUrl);
    }

    protected function ExtractVideoId($vidUrl)
    {
        $id = '';
        $url = trim($vidUrl);
        $urlQueryStr = parse_url($url, PHP_URL_QUERY);
        if ($urlQueryStr !== false && !empty($urlQueryStr))
        {
            parse_str($urlQueryStr, $params);
            if (isset($params['v']) && !empty($params['v']))
            {
                $id = $params['v'];
            }
            else
            {
                $url = preg_replace('/(\?' . preg_quote($urlQueryStr, '/') . ')$/', "", $url);
                $id = trim((string)strrchr(trim($url, '/'), '/'), '/');
            }
        }
        else
        {
            $id = trim((string)strrchr(trim($url, '/'), '/'), '/');
        }
        return $id;
    }

    protected function UnicodeToHtmlEntities($str)
    {
        $output = preg_replace('/(\\\u)([0-9a-zA-Z]{2,})/', '$1{$2}', $str);
        return $output;
    }

    protected function MultiArrayVidSort(array $videos, $sortBy = "qualityLabel")
    {
        $sortedArr = $videos;
        if (!empty($videos))
        {
            $vidCollection = collect($videos);
            $sortedVids = $vidCollection->sortByDesc(function ($item, $key) use ($sortBy)
            {
                return (int)preg_replace('/\D/', "", $item[$sortBy]);
            });
            $sortedArr = $sortedVids->values()->all();
        }
        return $sortedArr;
    }

    protected function GenerateCookieString($response)
    {
        $cookies = $response->cookies()->toArray();
        $cookieStr = '';
        foreach ($cookies as $cookie)
        {
            $cookieStr .= $cookie['Name'] . '=' . $cookie['Value'] . ';';
        }
        return $cookieStr;
    }

    protected function UpdateSoftware()
    {
        $response = Http::withoutVerifying()->get(self::_SOFTWARE_JSON_REMOTE);
        if ($response->successful() && !empty($response->json('lastUpdate')))
        {
            Storage::disk('local')->put(self::_SOFTWARE_JSON_LOCAL, $response->body());
        }
        return array(
            'error' => true,
            'httpCode' => 205,
            'errorMessage' => 'Updating Software',
            'errorCode' => '0x0001'
        );
    }

    protected function GetSoftwareJsonData()
    {
        if (Storage::disk('local')->exists(self::_SOFTWARE_JSON_LOCAL))
        {
            $json = Storage::disk('local')->get(self::_SOFTWARE_JSON_LOCAL);
            $data = json_decode($json, true);
            return (isset($data['lastUpdate'])) ? $data : $this->UpdateSoftware();
        }
        return $this->UpdateSoftware();
    }
}
