<?php
/**
 * Date: 4/19/15
 * Time: 12:07 AM
 */

namespace AppBundle\Service;

use AppBundle\Document\Order;
use AppBundle\Exception\LastException;
use GuzzleHttp\ClientInterface;
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
    private $lastUrl;

    /**
     * @var string
     */
    private $lastKey;

    /**
     * @var ClientInterface
     */
    private $guzzle;

    /**
     * @var string
     */
    private $spotifyUrl;

    /**
     * @var string
     */
    private $spotifyCreatePlaylistUrl;

    /**
     * @var string
     */
    private $spotifyPlaylistAddUrl;

    /**
     * @var string
     */
    private $spotifyPlaylistReplaceUrl;

    /**
     * @var string
     */
    private $spotifyProfileUrl;

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
     * @param string $lastUrl
     * @param string $spotifyUrl
     * @param string $spotifyCreatePlaylistUrl
     * @param string $spotifyPlaylistAddUrl
     * @param string $spotifyPlaylistReplaceUrl
     * @param string $spotifyProfileUrl
     * @param OrderServiceInterface $orderService
     */
    public function __construct(
        ClientInterface $guzzle,
        PredisClientInterface $predis,
        $lastKey,
        $lastUrl,
        $spotifyUrl,
        $spotifyCreatePlaylistUrl,
        $spotifyPlaylistAddUrl,
        $spotifyPlaylistReplaceUrl,
        $spotifyProfileUrl,
        OrderServiceInterface $orderService
    ) {
        $this->guzzle = $guzzle;
        $this->lastKey = $lastKey;
        $this->lastUrl = $lastUrl;
        $this->spotifyUrl = $spotifyUrl;
        $this->spotifyCreatePlaylistUrl = $spotifyCreatePlaylistUrl;
        $this->spotifyPlaylistAddUrl = $spotifyPlaylistAddUrl;
        $this->spotifyPlaylistReplaceUrl = $spotifyPlaylistReplaceUrl;
        $this->spotifyProfileUrl = $spotifyProfileUrl;
        $this->predis = $predis;
        $this->orderService = $orderService;
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
        $uris = $this->getSpotifyUrls($tracks);

        $userId = $this->getUserId();
        $recent = json_encode($tracks[0]);

        $query = 'recent_' . $username . $userId;
        $cacheRecent = $this->predis->get($query);
        if ($cacheRecent === $recent) {
            return false;
        }
        $this->predis->set($query, $recent);

        $query = 'follow_' . $username . $userId;
        if (($playlistId = $this->predis->get($query)) === null) {
            $playlistId = $this->createPlaylist($userId, $username);
            $this->predis->set($query, $playlistId);
        }

        $this->replaceTracksInPlaylist($playlistId, $uris, $userId);

        return true;
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
        $url = str_replace(["{username}", "{method}"], [$username, $type], $this->lastUrl);
        $response = $this->guzzle->get($url);
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
     *
     * @return string
     */
    protected function getUserId()
    {
        $response = $this->guzzle->get(
            $this->spotifyProfileUrl,
            [
                'headers' => ['Authorization' => $this->getAuth()]
            ]
        );
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
        $url = str_replace("{user_id}", $userId, $this->spotifyCreatePlaylistUrl);
        $name = $type ? (ucwords($type) . "By" . ucwords($username)) : ("following". ucwords($username));
        $json = [
            "name" => $name
        ];
        $response = $this->guzzle->post(
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
            $this->spotifyPlaylistReplaceUrl
        );

        $response = $this->guzzle->put(
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
            $this->spotifyPlaylistAddUrl
        );

        $response = $this->guzzle->post(
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
