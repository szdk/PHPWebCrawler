<?php
namespace szdk\PHPWebCrawler;


class Crawler
{
    use Queue;
    use Links;

    public $url; //url of webpage
    //public $rootDir = null; //top level dir (eg: https://example.com, root/)
    public $depth = 100; //scrap max 100 pages (put 0 for no limit)
    public $onlyChildren = true; //process only children urls?
    public $exceptions = []; //dont search through these urls, or there children directories
    
    private const REMOVE_ANCHOR = 1;
    private const REMOVE_QUERY = 2;
    private const REMOVE_SCHEME = 4;
    private const REMOVE_FILE_NAME = 8;
    private const LOWERCASE = 16;

    private $localFile = false;
    private $processedCount = 0;
    private $processed = [];
    private $queued = [];
    private $dirs = [
        'temp' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR,
    ];
    private $files = [
        'processed' =>  __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'processed.json',
        'queued' =>  __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'queued.json',
    ];

    public function __construct(String $url, $clearURLs = false)
    {
        $this->url = \trim($url);
        if (\stripos($this->url, 'http://') !== 0 && \stripos($this->url, 'https://') !== 0) {
            $this->localFile = true;
            $this->url = \str_replace('\\', '/', \realpath($this->url));
        }
        foreach ($this->dirs as $dir) {
            if (!\is_dir($dir)) {
                @\mkdir($dir);
            }
        }
        if ($clearURLs) {
            $this->clearURLs();
        }
    }

    /*
     *the callable function passed must accept a webpage contents in first parameter
     */
    public function run(Callable $fnc)
    {
        $this->queued($this->url);
        $this->url = $this->getTrimmedURL($this->url, self::REMOVE_ANCHOR | self::REMOVE_FILE_NAME);
        

        while (!empty($this->queued) && ($this->depth === 0 || $this->processedCount < $this->depth)) {
            $url = \array_key_first($this->queued);

            if ($this->isURLProcessed($url)) {
                $this->processed($url);
                continue;
            }
            
            $this->processed($url);


            if ($this->onlyChildren && !$this->isChildrenURL($url, $this->url)) {
                continue;
            }
            
            foreach ($this->exceptions as $link) {
                if ($this->isChildrenURL($url, $link));
                continue;
            }


            try {
                $content = \file_get_contents(!$this->localFile ? $url : $this->getTrimmedURL($url, self::REMOVE_ANCHOR | self::REMOVE_QUERY));
            } catch (\Exception $e) {
                //Logger::log($e);
            }

            $fnc(['content' => $content, 'url' => $url]); //run user generated scraping function

            $newLinks = $this->extractLinks($content, $url);
            foreach ($newLinks as $link) {
                if (!$this->isURLProcessed($link) && (!$this->onlyChildren || $this->isChildrenURL($link, $this->url))) {
                    $this->queued(!$this->localFile ? $link : $this->getTrimmedURL($link, self::REMOVE_ANCHOR | self::REMOVE_QUERY));
                }
            }
            $this->processedCount++;
        }
        return true;
    }

    public function clearURLs()
    {
        foreach ($this->files as $file) {
            if (\is_readable($file)) {
                @\unlink($file);
            }
        }
        $this->processed = $this->queued = [];
    }
}

