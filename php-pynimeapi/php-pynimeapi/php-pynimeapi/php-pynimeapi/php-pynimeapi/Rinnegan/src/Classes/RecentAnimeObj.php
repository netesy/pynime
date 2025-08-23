<?php
namespace Rinnegan\Classes;
class RecentAnimeObj {
    public string $title, $latest_episode_url, $picture_url;
    public int $latest_episode;
    public function __construct(string $title, int $latest_episode, string $latest_episode_url, string $picture_url) {
        $this->title = $title; $this->latest_episode = $latest_episode; $this->latest_episode_url = $latest_episode_url; $this->picture_url = $picture_url;
    }
}
