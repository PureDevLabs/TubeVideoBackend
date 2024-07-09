<?php

namespace PureDevLabs;

use PureDevLabs\Extractors\Extractor;
use Illuminate\Support\Facades\Cache;

class DMCA
{
    public static function CheckBlockedUrl(string $site, string $url, string $id): bool
    {
        $urls = json_decode(Cache::get('blacklist:' . strtolower($site) . ':urls', '{}'), true);
        $isBlocked = json_last_error() == JSON_ERROR_NONE && isset($urls[$url]);
        if (!$isBlocked && !empty($id))
        {
            $ids = json_decode(Cache::get('blacklist:' . strtolower($site) . ':ids', '{}'), true);
            $isBlocked = json_last_error() == JSON_ERROR_NONE && isset($ids[$id]);
        }
        return $isBlocked;
    }

    public static function ConvertUrlsToJson(string $urls, bool $useIds=false): string
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
