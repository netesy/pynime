<?php
namespace PyNime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
class Schedule {
    private string $url="https://graphql.anilist.co"; private string $gql; private Client $client;
    public function __construct() {
        $this->client=new Client();
        $this->gql=<<<'GQL'
query ($weekStart: Int, $weekEnd: Int, $page: Int) { Page(page: $page) { pageInfo { hasNextPage total } airingSchedules(airingAt_greater: $weekStart, airingAt_lesser: $weekEnd) { id episode airingAt media { title { userPreferred } status } } } }
GQL;
    }
    public function fetchSchedule(int $unix_time): array {
        $all_schedules=[]; $page=1; $week_start=$unix_time; $week_end=$unix_time+(24*7*60*60);
        do {
            try {
                $response=$this->client->post($this->url,['json'=>['query'=>$this->gql,'variables'=>['weekStart'=>$week_start,'weekEnd'=>$week_end,'page'=>$page,],],]);
                $data=json_decode($response->getBody()->getContents(),true); $page_data=$data['data']['Page']; $schedules=$page_data['airingSchedules'];
                $all_schedules=array_merge($all_schedules,$schedules); $has_next_page=$page_data['pageInfo']['hasNextPage']; $page++;
            } catch (GuzzleException $e) { return []; }
        } while ($has_next_page);
        return $all_schedules;
    }
}
