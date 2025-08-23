<?php
namespace Rinnegan\Streaming;
use GuzzleHttp\Client; use DiDom\Document;
class StreamExtractor {
    private Client $client; private string $baseURL = "https://gogoanime.cl";
    public function __construct() {
        $this->client = new Client(['headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36']]);
    }
    private function getEmbedUrl(string $episode_url): ?string {
        try {
            $response = $this->client->get($episode_url); $html = $response->getBody()->getContents();
            $document = new Document($html); $iframe = $document->first('iframe');
            if ($iframe) {
                $src = $iframe->getAttribute('src');
                if (!str_starts_with($src, 'https:')) { $src = 'https:' . $src; }
                return $src;
            } return null;
        } catch (\Exception $e) { return null; }
    }
    private function aesDecrypt(string $data, string $key, string $iv): string {
        $decrypted = openssl_decrypt(base64_decode($data), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return rtrim($decrypted, "\x00\x01\x02\x03\x04\x05\x06\x07\x08\t\n\x0b\x0c\r\x0e\x0f\x10");
    }
    private function aesEncrypt(string $data, string $key, string $iv): string {
        $padded_data = $data . str_repeat(chr(16 - strlen($data) % 16), 16 - strlen($data) % 16);
        $encrypted = openssl_encrypt($padded_data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encrypted);
    }
    public function extract(string $url): ?string {
        try {
            $embed_url = $this->getEmbedUrl($url); if (!$embed_url) return null;
            $parsed_url = parse_url($embed_url); parse_str($parsed_url['query'] ?? '', $query_params);
            $content_id = $query_params['id'] ?? null; if (!$content_id) return null;
            $next_host = "{$parsed_url['scheme']}://{$parsed_url['host']}/";
            $streaming_page = $this->client->get($embed_url, ['headers' => ['Referer' => $this->baseURL]])->getBody()->getContents();
            preg_match_all('/(?:container|videocontent)-(\d+)/', $streaming_page, $key_matches);
            if (count($key_matches[1]) < 3) return null;
            [$encryption_key, $iv, $decryption_key] = $key_matches[1];
            preg_match('/data-value="(.+?)"/', $streaming_page, $data_matches);
            $encrypted_data = $data_matches[1] ?? null; if (!$encrypted_data) return null;
            $decrypted_component = $this->aesDecrypt($encrypted_data, $encryption_key, $iv);
            $encrypted_id = $this->aesEncrypt($content_id, $encryption_key, $iv);
            $component = $decrypted_component . "&id=" . $encrypted_id . "&alias=" . $content_id;
            list(, $component) = explode('&', $component, 2);
            $ajax_response = $this->client->get($next_host . "encrypt-ajax.php?" . $component, ['headers' => ['x-requested-with' => 'XMLHttpRequest', 'Referer' => $embed_url]]);
            $encrypted_content = json_decode($ajax_response->getBody()->getContents(), true);
            $content = json_decode($this->aesDecrypt($encrypted_content['data'], $decryption_key, $iv), true);
            $sources = array_merge($content['source'] ?? [], $content['source_bk'] ?? []);
            if (!empty($sources)) { return $sources[0]['file']; }
            return null;
        } catch (\Exception $e) { return null; }
    }
}
