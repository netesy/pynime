<?php

namespace PyNime;

use PyNime\Classes\SearchResultObj;
use PyNime\Classes\AnimeDetailsObj;
use PyNime\Classes\RecentAnimeObj;
use PyNime\Schedule;
use PyNime\Streaming\StreamExtractor;
use PyNime\Streaming\PlaylistParser;
use GuzzleHttp\Client;
use DiDom\Document;

class PyNime
{
    private string $baseURL;
    private Client $client;

    public function __construct(string $base_url = "https://gogoanime.cl")
    {
        $this->baseURL = $base_url;
        $this->client = new Client();
    }

    public function version(): string
    {
        return "0.1.55-php";
    }

    public function search_anime(string $anime_title): ?array
    {
        $anime_result = [];
        try {
            $response = $this->client->get("https://ajax.gogo-load.com/site/loadAjaxSearch", [
                'query' => ['keyword' => $anime_title]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $document = new Document($data['content']);
            $items = $document->find('a.ss-title');

            foreach ($items as $item) {
                $title = $item->text();
                $category_url = $this->baseURL . $item->getAttribute('href');

                $parent_div = $item->parent();
                $picture_div = $parent_div->previousSibling();
                $style = $picture_div->getAttribute('style');
                preg_match('/url\(\'(.*?)\'\)/', $style, $matches);
                $picture_url = $matches[1] ?? '';

                $anime_result[] = new SearchResultObj($title, $category_url, $picture_url);
            }

            return $anime_result;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function get_anime_details(string $anime_category_url): ?AnimeDetailsObj
    {
        try {
            $response = $this->client->get($anime_category_url);
            $html = $response->getBody()->getContents();
            $document = new Document($html);

            $info_body = $document->first('.anime_info_body_bg');
            $title = $info_body->first('h1')->text();
            $image_url = $info_body->first('img')->getAttribute('src');

            $other_info = $info_body->find('p.type');
            $season = str_replace('Type: ', '', trim($other_info[0]->text()));
            $synopsis = trim($other_info[1]->text());

            $genres = [];
            foreach ($other_info[2]->find('a') as $genre_link) {
                $genres[] = $genre_link->getAttribute('title');
            }

            $released = str_replace('Released: ', '', trim($other_info[3]->text()));
            $status = str_replace('Status: ', '', trim($other_info[4]->text()));

            return new AnimeDetailsObj(
                $title,
                $season,
                $synopsis,
                $genres,
                $released,
                $status,
                $image_url
            );

        } catch (\Exception $e) {
            return null;
        }
    }

    public function get_episode_urls(string $anime_category_url): ?array
    {
        try {
            $response = $this->client->get($anime_category_url);
            $html = $response->getBody()->getContents();

            preg_match('/<input.+?value="(\d+)" id="movie_id"/', $html, $matches);
            $anime_id = $matches[1] ?? null;

            if (!$anime_id) {
                return null;
            }

            $response = $this->client->get("https://ajax.gogo-load.com/ajax/load-list-episode", [
                'query' => [
                    'ep_start' => 0,
                    'ep_end' => 9999,
                    'id' => $anime_id,
                ]
            ]);

            $html = $response->getBody()->getContents();
            $document = new Document($html);

            $eps_list = [];
            $links = $document->find('a');
            foreach ($links as $link) {
                $eps_list[] = $this->baseURL . trim($link->getAttribute('href'));
            }

            return array_reverse($eps_list);

        } catch (\Exception $e) {
            return null;
        }
    }

    public function get_stream_urls(string $anime_episode_url): ?array
    {
        $extractor = new StreamExtractor();
        $playlist_url = $extractor->extract($anime_episode_url);
        if (!$playlist_url) {
            return null;
        }
        $parser = new PlaylistParser();
        return $parser->parse($playlist_url);
    }

    public function grab_stream(string $anime_title, int $episode, int $resolution = 1080): ?string
    {
        $search_result = $this->search_anime($anime_title);
        if (empty($search_result)) {
            return null;
        }

        $episode_urls = $this->get_episode_urls($search_result[0]->category_url);
        if (empty($episode_urls) || !isset($episode_urls[$episode - 1])) {
            echo "[!] Unfortunately episode {$episode} not released yet.\n";
            echo "[!] Latest episode is episode " . count($episode_urls) . ".\n";
            return null;
        }

        $stream_urls = $this->get_stream_urls($episode_urls[$episode - 1]);
        if (empty($stream_urls)) {
            return null;
        }

        if (isset($stream_urls[$resolution])) {
            return $stream_urls[$resolution];
        } else {
            echo "[!] Available resolutions are " . implode(', ', array_keys($stream_urls)) . ". {$resolution}p not available.\n";
            return null;
        }
    }

    public function get_schedule(): void
    {
        $schedule = new Schedule();
        $unix_time = time();
        $schedule_data = $schedule->fetchSchedule($unix_time);

        $template = [];
        foreach ($schedule_data as $airing) {
            $datetime = date('M. d, l', $airing['airingAt']);
            $time = date('H:i:s', $airing['airingAt']);
            $template[$datetime][$time][] = [
                'name' => $airing['media']['title']['userPreferred'],
                'episode' => $airing['episode'],
            ];
        }

        foreach ($template as $date_format => $child_component) {
            echo "[>] On {$date_format}\n";
            ksort($child_component);
            foreach ($child_component as $time_format => $anime_component) {
                echo "\t{$time_format} - ";
                $animes = [];
                foreach ($anime_component as $anime) {
                    $animes[] = "{$anime['name']} [{$anime['episode']}]";
                }
                echo implode("\n\t\t - ", $animes) . "\n";
            }
        }
    }

    public function get_recent_release(int $page = 1): ?array
    {
        try {
            $response = $this->client->get("https://ajax.gogo-load.com/ajax/page-recent-release.html", [
                'query' => ['page' => $page]
            ]);
            $html = $response->getBody()->getContents();

            $regex_filter = "/<li>\s*\n.*\n.*<a\shref=[\"'](?P<href>.*?-episode-(?P<episode>\d+))[\"']\s*title=[\"'](?P<title>.*?)[\"']>\n.*<img\ssrc=[\"'](?P<img>.*?)[\"']/";

            preg_match_all($regex_filter, $html, $matches, PREG_SET_ORDER);

            $recent_release_list = [];
            foreach ($matches as $match) {
                $recent_release_list[] = new RecentAnimeObj(
                    $match['title'],
                    (int)$match['episode'],
                    $this->baseURL . $match['href'],
                    $match['img']
                );
            }

            return $recent_release_list;

        } catch (\Exception $e) {
            return null;
        }
    }
}
