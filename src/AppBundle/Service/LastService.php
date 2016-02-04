<?php
/**
 * Date: 4/19/15
 * Time: 12:07 AM
 */

namespace AppBundle\Service;

use AppBundle\Document\Order;
use AppBundle\Exception\LastException;
use AppBundle\ValueObject\UrlContainer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Predis\ClientInterface as PredisClientInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LastService
 *
 * @package AppBundle\Service
 */
class LastService implements LastServiceInterface
{
    /**
     * @var string
     */
    private $lastKey;

    /**
     * @var ClientInterface
     */
    private $guzzle;

    /**
     * @var UrlContainer
     */
    private $urlContainer;

    /**
     * @var PredisClientInterface
     */
    private $predis;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var string
     */
    private $token;

    /**
     * @param ClientInterface $guzzle
     * @param PredisClientInterface $predis
     * @param string $lastKey
     * @param OrderServiceInterface $orderService
     * @param UrlContainer $urlContainer
     */
    public function __construct(
        ClientInterface $guzzle,
        PredisClientInterface $predis,
        $lastKey,
        OrderServiceInterface $orderService,
        UrlContainer $urlContainer
    ) {
        $this->guzzle = $guzzle;
        $this->lastKey = $lastKey;
        $this->predis = $predis;
        $this->orderService = $orderService;
        $this->urlContainer = $urlContainer;
    }

    /**
     * @param string $token
     */
    protected function initialize($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     *
     * @throws LastException
     */
    protected function getAuth()
    {
        if (!$this->token) {
            throw new LastException('Service not initialized with token');
        }

        return "Bearer " . $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function follow($username, $token)
    {
        $this->initialize($token);
        $tracks = $this->grabFromLast($username, "recent", 1);

        $userId = $this->getUserId();
        $recent = json_encode($tracks[0]);

        $query = 'recent_' . $username . $userId;
        $cacheRecent = $this->predis->get($query);
        if ($cacheRecent === $recent) {
            return false;
        }
        $this->predis->set($query, $recent);

        $uris = $this->getSpotifyUrls($tracks);

        if (!$uris) {
            throw new LastException("Could not find track");
        }

        $query = 'follow_' . $username . $userId;
        if (($playlistId = $this->predis->get($query)) === null) {
            $playlistId = $this->createPlaylist($userId, $username);
            $this->predis->set($query, $playlistId);
        }

        try {
            $this->replaceTracksInPlaylist($playlistId, $uris, $userId);
        } catch (ClientException $e) {
            $playlistId = $this->createPlaylist($userId, $username);
            $this->predis->set($query, $playlistId);
            $this->replaceTracksInPlaylist($playlistId, $uris, $userId);
        }

        return $tracks[0];
    }

    /**
     * @param $username
     * @param string $type
     * @param int $limit
     *
     * @return array
     * @throws LastException
     */
    public function grabFromLast($username, $type = '', $limit = 100)
    {
        $type = $type ?: 'recent';
        $url = str_replace(["{username}", "{method}"], [$username, $type], $this->urlContainer->getLastUrl());
        $response = $this->guzzle->request('get', $url);
        if ($response->getStatusCode() != Response::HTTP_OK) {
            throw new LastException("Invalid last.fm response code");
        }
        $data = $response->getBody()->getContents();
        $data = json_decode($data, true);
        $tracks = [];
        if (isset($data['error'])) {
            $message = "last.fm replied with error";
            if (isset($data['message'])) {
                $message .= ": " .$data['message'];
            }
            throw new LastException($message);
        }
        if (!isset($data[$type . 'tracks']['track'])) {
            throw new LastException("Invalid last.fm response content");
        }
        $count = 0;
        foreach ($data[$type . 'tracks']['track'] as $track) {
            $tracks[] = [
                'name'   => $track['name'],
                'artist' => $track['artist'][$type == "recent" ? "#text" : 'name'],
            ];
            $count++;
            if ($count >= $limit) {
                break;
            }
        }

        if (!$tracks) {
            throw new LastException("No tracks for this params.");
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
                $url = str_replace("{query}", $query, $this->urlContainer->getSpotifyUrl());
                $response = $this->guzzle->request('get', $url);
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
     * @return string
     */
    protected function getUserId()
    {
        try {
            $response = $this->guzzle->request(
                'get',
                $this->urlContainer->getSpotifyProfileUrl(),
                [
                    'headers' => [
                        'Authorization' => $this->getAuth()
                    ]
                ]
            );
        } catch (ClientException $e) {
            throw new LastException($e->getMessage(), $e->getCode(), $e);
        }
        $content = $response->getBody()->getContents();
        $data = json_decode($content, true);
        return $data['id'];
    }

    /**
     * @param $userId
     * @param $username
     * @param string|null $type
     *
     * @return string
     */
    protected function createPlaylist($userId, $username, $type = null)
    {
        $url = str_replace("{user_id}", $userId, $this->urlContainer->getSpotifyCreatePlaylistUrl());
        $name = $type ? (ucwords($type) . "By" . ucwords($username)) : ("following". ucwords($username));
        $json = [
            "name" => $name
        ];
        $response = $this->guzzle->request(
            'post',
            $url,
            [
                'headers' => ['Authorization' => $this->getAuth()],
                'json'    => $json
            ]
        );

        /**
         * @var \Psr\Http\Message\ResponseInterface $response
         */

        $url = $response->getHeaderLine("location");

        $explodedUrl = explode('/', $url);
        $playlistId = end($explodedUrl);

        return $playlistId;
    }

    /**
     * @param $playlistId
     * @param $uris
     * @param $userId
     *
     * @return int
     */
    protected function replaceTracksInPlaylist($playlistId, $uris, $userId)
    {
        $url = str_replace(
            ["{user_id}", "{playlist_id}"],
            [$userId, $playlistId],
            $this->urlContainer->getSpotifyPlaylistReplaceUrl()
        );

        $response = $this->guzzle->request(
            'put',
            $url,
            [
                'headers' => ['Authorization' => $this->getAuth()],
                'json'    => ['uris' => $uris]
            ]
        );

        return $response->getStatusCode();
    }

    /**
     * @param $playlistId
     * @param $uris
     * @param $userId
     *
     * @return int
     */
    protected function addTracksToPlaylist($playlistId, $uris, $userId)
    {
        $url = str_replace(
            ["{user_id}", "{playlist_id}", "{uris}"],
            [$userId, $playlistId, implode(",", $uris)],
            $this->urlContainer->getSpotifyPlaylistAddUrl()
        );

        $response = $this->guzzle->request(
            'post',
            $url,
            [
                'headers' => ['Authorization' => $this->getAuth()],
            ]
        );

        return $response->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function grab($lastUsername, $type, $auth)
    {
        $this->initialize($auth);
        $tracks = $this->grabFromLast($lastUsername, $type);

        $order = new Order($lastUsername);
        $this->orderService->save($order);

        $uris = $this->getSpotifyUrls($tracks);

        $userId = $this->getUserId();
        $playlistId = $this->createPlaylist($userId, $lastUsername, $type);

        $this->addTracksToPlaylist($playlistId, $uris, $userId);

        $order->finish(count($uris));
        $this->orderService->save($order);

        return $order;
    }
}
