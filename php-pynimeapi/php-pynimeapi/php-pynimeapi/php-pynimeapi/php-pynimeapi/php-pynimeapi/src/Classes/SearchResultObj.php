<?php
namespace PyNime\Classes;
class SearchResultObj {
    public string $title, $category_url, $picture_url;
    public function __construct(string $title, string $category_url, string $picture_url) {
        $this->title = $title; $this->category_url = $category_url; $this->picture_url = $picture_url;
    }
}
