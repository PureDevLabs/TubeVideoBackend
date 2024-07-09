<?php

namespace App\Http\Controllers;

use App\Models\Key;
use PureDevLabs\Core;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PureDevLabs\Extractors\Extractor;
use PureDevLabs\Extractors\YoutubeData;
use PureDevLabs\DMCA;


class ApiController extends Controller
{
    /**
     * @bodyParam url string required valid Video URL. Example: https://www.youtube.com/watch?v=zvrMzRVtj1s
     */
    public function extract(Request $request)
    {
        $apiKey = $request->header('apiKey');
        $validate = $this->checkApiKey($apiKey);
        if (isset($apiKey) && isset($request->url) && $validate === 'valid')
        {
            $site = Extractor::ObtainSiteName($request->url);
            $vid = Extractor::GetVideoId($request->url);
            if (isset($site['error']))
            {
                return $site;
            }
            elseif (DMCA::CheckBlockedUrl($site, $request->url, $vid))
            {
                return $this->InvalidBlockedUrl();
            }
            else
            {
                if (config('app.search_cache'))
                {
                    $cachedVideo = Cache::get('video:' . $site . ':' . $vid);
                    if ($cachedVideo)
                    {
                        return json_decode($cachedVideo, FALSE);
                    }
                    else
                    {
                        $data = $this->ValidRequest($site, $request->url);
                        $newData = mb_convert_encoding($data, "UTF-8", "auto");
                        (!isset($data['error'])) ? Cache::put('video:' . $site . ':' . $vid, json_encode($newData), 14400) : '';

                        return $data;
                    }
                }
                else
                {
                    $data = $this->ValidRequest($site, $request->url);
                    $newData = mb_convert_encoding($data, "UTF-8", "auto");
                    return $newData;
                }
            }
        }
        else
        {
            return (isset($request->url)) ? $validate : $this->InvalidParams();
        }
    }

    /**
     * @bodyParam term string required Your Search term. Example: sia
     * @bodyParam nextPageToken string The nextPageToken of first request. Example: EqQDEgNzaWEanANTQlNDQVF0RFZ6WXRNRkJvUTJ0SGM0SUJDMjlrUVZKV1duZGxiRXBWZ2dFTGRtTnFWVmRpWW5ZMVNFMkNBUXRaWVVWSE1tRlhTbTVhT0lJQkN6ZGtUVk5HUVZZeldGZHZnZ0VMUjB0VFVubE1aR3B6VUVHQ0FRc3lkbXBRUW5KQ1ZTMVVUWUlCQzB0WFdrZEJSWGhxTFdWemdnRVlWVU5PT1VoUWJqSm1jUzFPVERoTk5WOXJjRFJTVjFwUmdnRUxTbGQ2YW5sdk1qQktRalNDQVF0b00yZ3dNelZGZVhvMVFZSUJDMmcyVDJ3elpYQnlTMmwzZ2dFTFdISmFha3hxVFVJdE9WbUNBUXRyWnpGQ2JHcE1kVGxaV1lJQkN6ZFlabmQxT0U1SU1XbEpnZ0VMYlZkUlFVTkZjV1kwVVZtQ0FRdHdOVkZtZVVZNWNHdElWWUlCQzJkelpYUTNPVXROYlhRd2dnRUxUMjgwUnpsWU5tbDZSRkdDQVF0dVdXZ3RiamRGVDNSTlFiSUJCZ29FQ0JZUUFnJTNEJTNEGIHg6BgiC3NlYXJjaC1mZWVk
     */
    public function search(Request $request)
    {
        $apiKey = $request->header('apiKey');
        $validate = $this->checkApiKey($apiKey);
        if (isset($apiKey) && isset($request->term) && $validate === 'valid')
        {
            $nextPageToken = (!empty($request->nextPageToken)) ? $request->nextPageToken : '';
            return app(YoutubeData::class)->GetResults(__FUNCTION__, trim($request->term), $nextPageToken);
        }
        else
        {
            return (isset($request->term)) ? $this->InvalidRequest() : $this->InvalidParams();
        }
    }

    public function related(Request $request)
    {
        $apiKey = $request->header('apiKey');
        $validate = $this->checkApiKey($apiKey);
        if (isset($apiKey) && isset($request->videoid) && $validate === 'valid')
        {
            $nextPageToken = (!empty($request->nextPageToken)) ? $request->nextPageToken : '';
            return app(YoutubeData::class)->GetResults(__FUNCTION__, trim($request->videoid), $nextPageToken);
        }
        else
        {
            return (isset($request->videoid)) ? $this->InvalidRequest() : $this->InvalidParams();
        }
    }

    private function checkApiKey($apiKey)
    {
        $result = Key::with('management')->where('apikey', $apiKey)->get();

        if ($result->isEmpty())
        {
            return $this->InvalidApiKey();
        }
        else
        {
            $validApiReq = false;

            $referer = (isset($_SERVER['HTTP_REFERER'])) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';

            if (isset($result[0]->management))
            {
                foreach ($result[0]->management as $allowed)
                {
                    if (isset($allowed->allowed_ip) && preg_match('/(' . preg_quote($allowed->allowed_ip, '/') . ')$/', $referer) == 1 || isset($allowed->allowed_ip) && $allowed->allowed_ip === app(Core::class)->RefererIP())
                    {
                        $validApiReq = true;
                        break;
                    }
                }
            }

            if ($validApiReq)
            {
                return 'valid';
            }
            else
            {
                return $this->InvalidRequest();
            }
        }
    }

    private function InvalidParams()
    {
        return [
            'error' => true,
            'code' => 400,
            'errorMsg' => 'Bad request',
            'message' => 'Invalid or missing parameters'
        ];
    }

    private function InvalidRequest()
    {
        return array(
            'error' => true,
            'code' => 401,
            'errorMsg' => 'Unauthorized',
            'message' => 'You are not Authorized. Please check your allowed IPs. (IP: ' . app(Core::class)->RefererIP() . ')'
        );
    }

    private function InvalidApiKey()
    {
        return array(
            'error' => true,
            'code' => 403,
            'errorMsg' => 'Invalid API Key',
            'message' => 'This API key is invalid. Please check your Configuration.'
        );
    }

    private function InvalidBlockedUrl()
    {
        return [
            'error' => true,
            'code' => 403,
            'errorMsg' => 'Forbidden',
            'message' => 'Download blocked at copyright holder\'s request.'
        ];
    }

    private function ValidRequest($site, $url)
    {
        $site = "PureDevLabs\\Extractors\\" . Str::ucfirst($site);
        $extractor = new $site();
        $data = $extractor->GetDownloadLinks($url);
        return (empty($data)) ? [
            'error' => true,
            'code' => 404,
            'errorMsg' => 'No Streams found.',
            'message' => 'Error: No Streams found.'
        ] : $data;
    }
}
