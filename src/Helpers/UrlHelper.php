<?php

namespace App\Helpers;

use Psr\Http\Message\ServerRequestInterface as Request;

class UrlHelper
{
    public static function path(Request $request, string $path): string
    {
        $basePath = $request->getAttribute('base_path') ?? '';
        return $basePath . $path;
    }
}
