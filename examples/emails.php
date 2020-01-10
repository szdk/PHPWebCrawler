<?php
/**
 * example code to extract emails from website
 */
ob_implicit_flush();

require __DIR__ . "/../vendor/autoload.php";

$url = "https://example.com"; //or path to any local html file
$scraper = function ($content) {
    static $counter = 0;
    $counter++;
    echo $counter . '. ' . $content['url'] . " ";
    $content = $content['content'];

    //find emails
    preg_match_all('/[a-z0-9\-\_\.]+\@[a-z0-9\_\-\.]{4,}\.[a-z]{2,4}/i', $content, $matches);

    foreach ($matches[0] as $email) {
        file_put_contents('emails.txt', $email . "\n", FILE_APPEND);
        echo  " => " . $email . "\n" ;
    }

    echo "\n";
    flush();
};

$crawler = new szdk\PHPWebCrawler\Crawler($url, true);
$crawler->depth = 0;
$crawler->run($scraper);