<?php

namespace App\Controllers;

use App\Models\BookmarkModel;
use CodeIgniter\HTTP\ResponseInterface;

class Feed extends BaseController
{
    public function rss(): ResponseInterface
    {
        $bookmarkModel = new BookmarkModel();

        $bookmarks = $bookmarkModel
            ->where('private', 0)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll(20);

        $siteUrl  = site_url('/');
        $siteName = esc(config('App')->siteName);
        $feedUrl  = site_url('/feed/rss');
        $buildDate = date(DATE_RSS);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . $siteName . '</title>' . "\n";
        $xml .= '    <link>' . esc($siteUrl) . '</link>' . "\n";
        $xml .= '    <description>Latest bookmarks from ' . $siteName . '</description>' . "\n";
        $xml .= '    <language>en-gb</language>' . "\n";
        $xml .= '    <lastBuildDate>' . $buildDate . '</lastBuildDate>' . "\n";
        $xml .= '    <atom:link href="' . esc($feedUrl) . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($bookmarks as $bookmark) {
            $itemTitle   = esc($bookmark['title']);
            $itemLink    = esc($bookmark['url']);
            $itemGuid    = esc(site_url('/bookmark/' . $bookmark['uuid']));
            $itemPubDate = date(DATE_RSS, strtotime($bookmark['created_at']));
            $itemDesc    = ! empty($bookmark['notes_html'])
                ? '<![CDATA[' . $bookmark['notes_html'] . ']]>'
                : '<![CDATA[' . esc($bookmark['url']) . ']]>';

            $xml .= '    <item>' . "\n";
            $xml .= '      <title>' . $itemTitle . '</title>' . "\n";
            $xml .= '      <link>' . $itemLink . '</link>' . "\n";
            $xml .= '      <guid isPermaLink="true">' . $itemGuid . '</guid>' . "\n";
            $xml .= '      <pubDate>' . $itemPubDate . '</pubDate>' . "\n";
            $xml .= '      <description>' . $itemDesc . '</description>' . "\n";
            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>' . "\n";

        return $this->response
            ->setHeader('Content-Type', 'application/rss+xml; charset=utf-8')
            ->setBody($xml);
    }
}
