<?php

namespace oteixido\yii2\rest\helpers;

class UrlHelper
{
    public static function join($parts)
    {
        if (count($parts) == 0) {
            return '';
        }
        $first = rtrim($parts[0], '/');
        $rest = array_map(function($v) { return trim($v, '/'); }, array_slice($parts, 1));
        return join('/', array_merge([$first], $rest));
    }
}
