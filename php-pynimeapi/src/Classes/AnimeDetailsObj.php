<?php

namespace PyNime\Classes;

class AnimeDetailsObj
{
    public string $title;
    public string $season;
    public string $synopsis;
    public array $genres;
    public string $released;
    public string $status;
    public string $image_url;

    public function __construct(
        string $title,
        string $season,
        string $synopsis,
        array $genres,
        string $released,
        string $status,
        string $image_url
    ) {
        $this->title = $title;
        $this->season = $season;
        $this->synopsis = $synopsis;
        $this->genres = $genres;
        $this->released = $released;
        $this->status = $status;
        $this->image_url = $image_url;
    }
}
