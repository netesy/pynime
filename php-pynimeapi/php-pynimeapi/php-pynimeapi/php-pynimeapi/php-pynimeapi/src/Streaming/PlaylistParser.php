<?php

namespace PyNime\Streaming;

use GuzzleHttp\Client;

class PlaylistParser
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function parse(string $playlist_url): array
    {
        try {
            $response = $this->client->get($playlist_url);
            $playlist_content = $response->getBody()->getContents();

            if (strpos($playlist_content, '#EXT-X-STREAM-INF') !== false) {
                // Variant playlist
                preg_match_all('/#EXT-X-STREAM-INF:.*?RESOLUTION=(\d+x\d+).*?\n(.*?)\s/', $playlist_content, $matches);

                $streams = [];
                $url_base = dirname($playlist_url);

                foreach ($matches[1] as $index => $resolution) {
                    $res_parts = explode('x', $resolution);
                    $streams[$res_parts[1]] = $url_base . '/' . $matches[2][$index];
                }
                return $streams;
            } else {
                // Not a variant playlist, return the URL directly
                return ['default' => $playlist_url];
            }
        } catch (\Exception $e) {
            return [];
        }
    }
}
