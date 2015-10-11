<?php
/**
 * Date: 4/19/15
 * Time: 12:07 AM
 */

namespace AppBundle\Service;

use AppBundle\Document\Order;
use AppBundle\Exception\LastException;
use GuzzleHttp\Client;
use Predis\Client as PredisClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LastService
 *
 * @package AppBundle\Service
 */
class LastService implements LastServiceInterface
{

    /**
     * @var
     */
    private $lastUrl;

    /**
     * @var
     */
    private $lastKey;

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var
     */
    private $spotifyUrl;

    /**
     * @var
     */
    private $spotifyCreatePlaylistUrl;

    /**
     * @var
     */
    private $spotifyPlaylistAddUrl;

    /**
     * @var
     */
    private $spotifyProfileUrl;

    /**
     * @var PredisClient
     */
    private $predis;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @param Client       $guzzle
     * @param PredisClient $predis
     * @param              $lastKey
     * @param              $lastUrl
     * @param              $spotifyUrl
     * @param              $spotifyCreatePlaylistUrl
     * @param              $spotifyPlaylistAddUrl
     * @param              $spotifyProfileUrl
     * @param OrderService $orderService
     */
    public function __construct(
        Client $guzzle,
        PredisClient $predis,
        $lastKey,
        $lastUrl,
        $spotifyUrl,
        $spotifyCreatePlaylistUrl,
        $spotifyPlaylistAddUrl,
        $spotifyProfileUrl,
        OrderService $orderService)
    {
        $this->guzzle = $guzzle;
        $this->lastKey = $lastKey;
        $this->lastUrl = $lastUrl;
        $this->spotifyUrl = $spotifyUrl;
        $this->spotifyCreatePlaylistUrl = $spotifyCreatePlaylistUrl;
        $this->spotifyPlaylistAddUrl = $spotifyPlaylistAddUrl;
        $this->spotifyProfileUrl = $spotifyProfileUrl;
        $this->predis = $predis;
        $this->orderService = $orderService;
    }

    /**
     * @param $username
     * @param $type
     *
     * @return array
     * @throws LastException
     */
    protected function grabFromLast($username, $type)
    {
        $url = str_replace(["{username}", "{method}"], [$username, $type], $this->lastUrl);
        $response = $this->guzzle->get($url);
        if ($response->getStatusCode() != Response::HTTP_OK) {
            throw new LastException("Invalid last.fm response code");
        }
        $data = $response->getBody()->getContents();
        $data = json_decode($data, true);
        $tracks = [];
        if (!isset($data[$type . 'tracks']['track'])) {
            throw new LastException("Invalid last.fm response content");
        }
        foreach ($data[$type . 'tracks']['track'] as $track) {

            $tracks[] = [
                'name'   => $track['name'],
                'artist' => $track['artist'][$type == "recent" ? "#text" : 'name'],
            ];

        }

        return $tracks;
    }

    /**
     * @param $tracks
     *
     * @return array
     */
    protected function getSpotifyUrls($tracks)
    {
        $uris = [];
        $i = 0;
        foreach ($tracks as $track) {
            $query = urlencode(sprintf("%s %s", $track['name'], $track['artist']));
            if (null === $result = $this->predis->get($query)) {
                $url = str_replace("{query}", $query, $this->spotifyUrl);
                $response = $this->guzzle->get($url);
                $data = $response->getBody()->getContents();
                $data = json_decode($data, true);
                $result = isset($data['tracks']['items'][0]['uri']) ? $data['tracks']['items'][0]['uri'] : false;
                $this->predis->set($query, $result);
            }

            if ($result) {
                $uris[] = $result;
            }


        }

        return $uris;
    }

    /**
     * @param $auth
     *
     * @return string
     */
    protected function getUserId($auth)
    {
        $response = $this->guzzle->get($this->spotifyProfileUrl,
            [
                'headers' => ['Authorization' => $auth]
            ]
        );
        $content = $response->getBody()->getContents();
        $data = json_decode($content, true);
        return $data['id'];
    }

    /**
     * @param $auth
     *
     * @return string
     */
    protected function createPlaylist($auth, $userId, $username, $type)
    {
        $url = str_replace("{user_id}", $userId, $this->spotifyCreatePlaylistUrl);
        $json = [
            "name" => ucwords($type) . "By" . ucwords($username)
        ];
        $response = $this->guzzle->post(
            $url, [
                'headers' => ['Authorization' => $auth],
                'json'    => $json
            ]
        );

        return $response->getHeader("location");
    }

    /**
     * @param $playlistId
     * @param $uris
     * @param $auth
     *
     * @return \GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Message\ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    protected function addTracksToPlaylist($playlistId, $uris, $auth, $userId)
    {
        $url = str_replace(
            ["{user_id}", "{playlist_id}", "{uris}"],
            [$userId, $playlistId, implode(",", $uris)],
            $this->spotifyPlaylistAddUrl
        );

        $response = $this->guzzle->post(
            $url,
            [
                'headers' => ['Authorization' => $auth],
            ]
        );

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function grab($lastUsername, $auth, $type)
    {
        $tracks = $this->grabFromLast($lastUsername, $type);

        $order = new Order($lastUsername);
        $this->orderService->save($order);

        $uris = $this->getSpotifyUrls($tracks);
        $auth = "Bearer " . $auth;

        $userId = $this->getUserId($auth);
        $url = $this->createPlaylist($auth, $userId, $lastUsername, $type);

        $explodedUrl = explode('/', $url);
        $playlistId = end($explodedUrl);

        $this->addTracksToPlaylist($playlistId, $uris, $auth, $userId);

        $order->finish(count($uris));
        $this->orderService->save($order);

        return $uris;
    }
}