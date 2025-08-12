<?php
namespace PyNime\Classes;
class AnimeDetailsObj {
    public string $title, $season, $synopsis, $released, $status, $image_url;
    public array $genres;
    public function __construct(string $title, string $season, string $synopsis, array $genres, string $released, string $status, string $image_url) {
        $this->title = $title; $this->season = $season; $this->synopsis = $synopsis; $this->genres = $genres; $this->released = $released; $this->status = $status; $this->image_url = $image_url;
    }
}
