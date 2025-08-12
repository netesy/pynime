<?php

namespace PyNime\Classes;

class SearchResultObj
{
    public string $title;
    public string $category_url;
    public string $picture_url;

    public function __construct(string $title, string $category_url, string $picture_url)
    {
        $this->title = $title;
        $this->category_url = $category_url;
        $this->picture_url = $picture_url;
    }

    public function __toString(): string
    {
        return "title: {$this->title} | category_url: {$this->category_url}";
    }
}
