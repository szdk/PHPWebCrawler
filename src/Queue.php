<?php
namespace szdk\PHPWebCrawler;

trait Queue
{
    private function queued(String $url = null) : array
    {
        if (empty($this->queued) && \is_readable($this->files['queued'])) {
            $this->queued = \json_decode(\file_get_contents($this->files['queued']), true);
            empty($this->queued) && $this->queued = [];
        }

        if (!empty($url)) {
            $this->queued[$url] = true;
            \file_put_contents($this->files['queued'], \json_encode($this->queued));
        }

        return $this->queued;
    }

    private function processed(String $url = null) : array
    {
        if (empty($this->processed) && \is_readable($this->files['processed'])) {
            $this->processed = \json_decode(\file_get_contents($this->files['processed']), true);
            empty($this->processed) && $this->processed = [];
        }

        if (!empty($url)) {
            $this->processed[$this->getTrimmedURL($url, self::REMOVE_ANCHOR | self::REMOVE_SCHEME)] = true;
            \file_put_contents($this->files['processed'], \json_encode($this->processed));

            $this->queued = $this->queued();
            if (isset($this->queued[$url])) {
                unset($this->queued[$url]);
                \file_put_contents($this->files['queued'], \json_encode($this->queued));
                //return true;
            }
        }

        return $this->processed;
    }

    private function isURLProcessed(String $url) : bool
    {
        if (isset($this->processed()[$this->getTrimmedURL($url, self::REMOVE_ANCHOR | self::REMOVE_SCHEME)])) {
            return true;
        }
        return false;
    }

}