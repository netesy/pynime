<?php

namespace PyNime\Classes;

class RecentAnimeObj
{
    public string $title;
    public int $latest_episode;
    public string $latest_episode_url;
    public string $picture_url;

    public function __construct(string $title, int $latest_episode, string $latest_episode_url, string $picture_url)
    {
        $this->title = $title;
        $this->latest_episode = $latest_episode;
        $this->latest_episode_url = $latest_episode_url;
        $this->picture_url = $picture_url;
    }
}
