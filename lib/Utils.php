<?php

namespace PureDevLabs;

class Utils
{
    public static function ArrayGet(array $arr, string $key): array
    {
        $keyArr = explode('.', $key);
        foreach ($keyArr as $segment)
        {
            $arr = $arr[$segment] ?? [];
            if (empty($arr)) break;
        }
        return $arr;
    }
}
