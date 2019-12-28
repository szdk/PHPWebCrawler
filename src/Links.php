<?php
namespace szdk\PHPWebCrawler;


trait Links
{

    private function getTrimmedURL(String $url, int $flags = self::REMOVE_ANCHOR) : string
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
    private function compareURL(String $url1, String $url2, $caseInsensitive = false) : bool
    {
        $flags = self::REMOVE_ANCHOR | self::REMOVE_SCHEME;
        if ($caseInsensitive) {
            $flags |= self::LOWERCASE;
        }
        if ($this->getTrimmedURL($url1, $flags) == $this->getTrimmedURL($url2, $flags)) {
            return true;
        }
        return false;
    }

    private function isChildrenURL(String $url, String $parent = null) : bool
    {
        if (empty($parent)) {
            $parent = $this->url;
        }
        $parent = $this->getTrimmedURL($parent, self::REMOVE_FILE_NAME | self::REMOVE_ANCHOR | self::REMOVE_SCHEME);
        $url = $this->getTrimmedURL($url, self::REMOVE_SCHEME);

        if (\strpos($parent, $url) ===0) {
            return true;
        }
        return false;
    }

    private function HTMLGetBaseUrl(String &$content, String $contentUrl)
    {
        ///$contentUrl = $this->getTrimmedURL($contentUrl);
        \preg_match('/\<\s*base\s*[^>]+?href\s*=\s*[\"\']([^\"\']+?)[\"\'][^>]*>.+<\/head>/is', $content, $match);
        if (!empty($match[1])) {
            return $this->addPath($contentUrl, $this->getTrimmedURL($match[1], self::REMOVE_FILE_NAME));
        }
        return $this->getTrimmedURL($contentUrl);
    }

    private function addPath(String $url, String $path) : String
    {
        $path = \trim($path);
        $url = $this->getTrimmedUrl($url, self::REMOVE_FILE_NAME);
        if (\preg_match('/^(https?:\/\/|[a-z][a-z]?:\/)/i', $path)) {
            return $path;
        } elseif (\stripos($path, '/') ===0) {
            return $this->getRootDir($url) . $path;
        } else {
            return $url . $path;
        }
    }

    private function getRootDir(String $path)
    {
        return \preg_replace("~^(https?://[^/\#\?]+|[a-z][a-z]?:(?=/)).*$~i", '$1', $path);
    }

    private function extractLinks(String &$content, String $contentUrl) : array
    {
        $contentUrl = $this->HTMLGetBaseUrl($content, $contentUrl);
        \preg_match_all('/\<\s*a[^\>]+href\s*\=\s*\"([^\"]+)/is', $content, $matches1);
        \preg_match_all('/\<\s*a[^\>]+href\s*\=\s*\'([^\']+)/is', $content, $matches2);
        $matches = array_merge($matches1[1], $matches2[1]);

        foreach ($matches as $index => $link) {
            if (\preg_match('/[a-z]+\:/i', $link)) {
                unset($matches[$index]);
                continue;
            }
            $matches[$index] = $this->addPath($contentUrl, $link);
        }
        return $matches;
    }
}