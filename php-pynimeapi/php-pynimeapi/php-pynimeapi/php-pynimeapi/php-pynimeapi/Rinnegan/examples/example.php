<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Rinnegan\Rinnegan;
function print_line() { echo str_repeat('-', 50) . "\n"; }
function print_header($title) { print_line(); echo "--- " . strtoupper($title) . " ---\n"; print_line(); }
print_header("Initializing API");
$api = new Rinnegan("https://gogoanime.cl");
echo "Rinnegan API Initialized. Version: " . $api->version() . "\n";
print_header("Searching for Anime: 'yofukashi no uta'");
$search_title = "yofukashi no uta";
$search_result = $api->search_anime($search_title);
if (empty($search_result)) {
    echo "No anime found for '{$search_title}'.\n";
} else {
    echo "Found " . count($search_result) . " result(s):\n";
    foreach ($search_result as $index => $anime) { echo "[$index] {$anime->title}\n"; }
    print_header("Getting Details for the First Result");
    $first_anime = $search_result[0];
    $details = $api->get_anime_details($first_anime->category_url);
    if ($details) {
        echo "Title: {$details->title}\n";
        echo "Synopsis: " . substr($details->synopsis, 0, 70) . "...\n";
    } else { echo "Could not fetch details.\n"; }
    print_header("Getting Episode URLs");
    $episode_urls = $api->get_episode_urls($first_anime->category_url);
    if ($episode_urls) { echo "Found " . count($episode_urls) . " episodes.\n"; }
    else { echo "Could not fetch episode URLs.\n"; }
    print_header("Grabbing Stream for Episode 1");
    $stream_url = $api->grab_stream($search_title, 1);
    if ($stream_url) { echo "Grabbed a stream URL: {$stream_url}\n"; }
    else { echo "Could not grab stream URL.\n"; }
    print_header("Getting Recent Releases");
    $recent_releases = $api->get_recent_release(1);
    if ($recent_releases) {
        echo "Found " . count($recent_releases) . " recent releases (showing first 5):\n";
        for ($i = 0; $i < min(5, count($recent_releases)); $i++) {
            echo " - {$recent_releases[$i]->title}\n";
        }
    } else { echo "Could not fetch recent releases.\n"; }
}
print_header("Getting Weekly Schedule");
$api->get_schedule();
print_line();
