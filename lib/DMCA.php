<?php

namespace PureDevLabs;

use PureDevLabs\Extractors\Extractor;
use Illuminate\Support\Facades\Cache;
use App\Models\BlacklistUrl;

class DMCA
{
    public static function CheckBlockedUrl(string $site, string $url, string $id): bool
    {
        $data = Cache::store('permaCache')->rememberForever('blockedurls', function () {
            return BlacklistUrl::with('extractor')->get();
        });

        $isBlocked = false;

        if (!$data->isEmpty())
        {

            foreach ($data as $blocked)
            {
                $vid = Extractor::GetVideoId($blocked->url);
                
                if ($url === $blocked->url || $id === $vid && strtolower($site) === $blocked->extractor->name)
                {
                    $isBlocked .= true;
                    break;
                } 
            }
        }

        return $isBlocked;
    }

    public static function ConvertUrlsToJson(string $urls, bool $useIds = false): string
    {
        $output = ['_placeholder_' => true];
        $urlLines = preg_split('/\\\n/', $urls, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($urlLines as $url)
        {
            $property = ($useIds) ? Extractor::GetVideoId($url) : $url;
            if (!empty($property) && !isset($output[$property]))
            {
                $output[$property] = true;
            }
        }
        $json = json_encode($output);
        return (json_last_error() == JSON_ERROR_NONE) ? $json : "{}";
    }
}
