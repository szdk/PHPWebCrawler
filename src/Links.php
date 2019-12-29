<?php
namespace szdk\PHPWebCrawler;


trait Links
{

    public static function getTrimmedURL(String $url, int $flags = self::REMOVE_ANCHOR) : string
    {
        $url = \trim($url);
        if ($flags & self::REMOVE_ANCHOR) {
            $url = \preg_replace('/\#.*$/i', '', $url);
        }
        if ($flags & self::REMOVE_QUERY) {
            $url = \preg_replace('/\?.*$/i', '', $url);
        }
        if ($flags & self::REMOVE_FILE_NAME) {
            $url = \preg_replace("~(?:(https?://[^/]+)|/[^/]*)$~i", '$1/', $url);
        }
        if ($flags & self::REMOVE_SCHEME) {
            $url = \preg_replace('/^https?\:\/\//i', '', $url);
        }
        if ($flags & self::LOWERCASE) {
            $url = \strtolower($url);
        }
        return $url;
    }

    //returns true if url1 & url2 match, false instead
    public static function compareURL(String $url1, String $url2, $caseInsensitive = false) : bool
    {
        $flags = self::REMOVE_ANCHOR | self::REMOVE_SCHEME;
        if ($caseInsensitive) {
            $flags |= self::LOWERCASE;
        }
        if (self::getTrimmedURL($url1, $flags) == self::getTrimmedURL($url2, $flags)) {
            return true;
        }
        return false;
    }

    public static function isChildrenURL(String $url, String $parent) : bool
    {
        $parent = self::getTrimmedURL($parent, self::REMOVE_FILE_NAME | self::REMOVE_ANCHOR | self::REMOVE_SCHEME);
        $url = self::getTrimmedURL($url, self::REMOVE_SCHEME);

        if (\strpos($url, $parent) ===0) {
            return true;
        }
        return false;
    }

    public static function HTMLGetBaseUrl(String &$content, String $contentUrl)
    {
        \preg_match('/\<\s*base\s*[^>]+?href\s*=\s*[\"\']([^\"\']+?)[\"\'][^>]*>.+<\/head>/is', $content, $match);
        if (!empty($match[1])) {
            return self::addPath($contentUrl, self::getTrimmedURL($match[1], self::REMOVE_FILE_NAME));
        }
        return self::getTrimmedURL($contentUrl);
    }

    public static function addPath(String $url, String $path) : String
    {
        $path = \trim($path);
        $url = self::getTrimmedUrl($url, self::REMOVE_FILE_NAME);
        if (\preg_match('/^(https?:\/\/|[a-z][a-z]?:\/)/i', $path)) {
            return $path;
        } elseif (\stripos($path, '/') ===0) {
            return self::getRootDir($url) . $path;
        } else {
            return $url . $path;
        }
    }

    public static function getRootDir(String $path)
    {
        return \preg_replace("~^(https?://[^/\#\?]+|[a-z][a-z]?:(?=/)).*$~i", '$1', $path);
    }

    public static function extractLinks(String &$content, String $contentUrl) : array
    {
        $contentUrl = self::HTMLGetBaseUrl($content, $contentUrl);
        \preg_match_all('/\<\s*a[^\>]+href\s*\=\s*\"([^\"]+)/is', $content, $matches1);
        \preg_match_all('/\<\s*a[^\>]+href\s*\=\s*\'([^\']+)/is', $content, $matches2);
        $matches = array_merge($matches1[1], $matches2[1]);

        foreach ($matches as $index => $link) {
            if (\preg_match('/[a-z]+\:/i', $link)) {
                unset($matches[$index]);
                continue;
            }
            $matches[$index] = self::addPath($contentUrl, $link);
        }
        return $matches;
    }
}