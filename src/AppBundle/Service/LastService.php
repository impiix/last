<?php
/**
 * Date: 4/19/15
 * Time: 12:07 AM
 */

namespace AppBundle\Service;

use GuzzleHttp\Client;
use Predis\Client as PredisClient;

class LastService {

    private $lastUrl;

    private $lastKey;

    private $guzzle;

    private $spotifyUrl;

    private $spotifyCreatePlaylistUrl;

    private $spotifyPlaylistAddUrl;

    private $predis;

    protected function grabFromLast($username) {
        $url = str_replace("{username}", $username, $this->lastUrl);
        $response = $this->guzzle->get($url);
        $data = $response->getBody()->getContents();
        $data = json_decode($data, true);
        $tracks = [];
        foreach($data['toptracks']['track'] as $track) {
            $tracks[] = [
                'name' => $track['name'],
                'artist' => $track['artist']['name'],
            ];
        }
        return $tracks;
    }

    protected function getSpotifyUrls($tracks) {
        $uris = [];
        $i = 0;
        foreach($tracks as $track) {
            $query = urlencode(sprintf("%s %s", $track['name'], $track['artist']));
            if(null === $result = $this->predis->get($query)) {
                $url = str_replace("{query}", $query, $this->spotifyUrl);
                $response = $this->guzzle->get($url);
                $data = $response->getBody()->getContents();
                $data = json_decode($data, true);
                $result = isset($data['tracks']['items'][0]['uri']) ? $data['tracks']['items'][0]['uri'] : false;
                $this->predis->set($query, $result);
            }

            if($result) {
                $uris[] = $result;
            }
            //fixme:remove below
            //break;

        }
        return $uris;
    }

    protected function createPlaylist($auth) {
        $url = str_replace("{user_id}", "1123045332", $this->spotifyCreatePlaylistUrl);
        $json = [
            "name" => "bestByCwiru"
        ];
        $response = $this->guzzle->post($url, [
            'headers'   => ['Authorization' => $auth],
            'json'      => $json
        ]);
        return $response->getHeader("location");
    }

    protected function addTracksToPlaylist($playlistId, $uris, $auth) {
        $url = str_replace(
            ["{user_id}", "{playlist_id}", "{uris}"],
            ["1123045332", $playlistId, implode(",", $uris)],
            $this->spotifyPlaylistAddUrl);

        $response = $this->guzzle->post($url,
            [
                'headers'   => ['Authorization' => $auth],
            ]);
        return $response;
    }



    public function grab($lastUsername, $auth) {
        $tracks = $this->grabFromLast($lastUsername);

        $uris = $this->getSpotifyUrls($tracks);
        $auth = "Bearer " . $auth;
        $url = $this->createPlaylist($auth);
        //$id = substr($url, strrpos($url, '/') + 1); - strpos doesn't return last char - wtf?
        $explodedUrl = explode('/', $url);
        $playlistId = end($explodedUrl);

        $this->addTracksToPlaylist($playlistId, $uris, $auth);
        return $uris;
    }

    public function __construct(Client $guzzle, PredisClient $predis,  $lastKey, $lastUrl, $spotifyUrl, $spotifyCreatePlaylistUrl, $spotifyPlaylistAddUrl) {
        $this->guzzle = $guzzle;
        $this->lastKey = $lastKey;
        $this->lastUrl = $lastUrl;
        $this->spotifyUrl = $spotifyUrl;
        $this->spotifyCreatePlaylistUrl = $spotifyCreatePlaylistUrl;
        $this->spotifyPlaylistAddUrl = $spotifyPlaylistAddUrl;
        $this->predis = $predis;
    }
}