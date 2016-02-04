<?php
/**
 * Date: 2/4/16
 * Time: 10:31 AM
 */
namespace AppBundle\ValueObject;

/**
 * Class UrlContainer
 * @package AppBundle\ValueObject
 */
class UrlContainer
{
    /**
     * @var string
     */
    private $lastUrl;

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
     * UrlContainer constructor.
     * @param string $lastUrl
     * @param string $spotifyUrl
     * @param string $spotifyCreatePlaylistUrl
     * @param string $spotifyPlaylistAddUrl
     * @param string $spotifyPlaylistReplaceUrl
     * @param string $spotifyProfileUrl
     */
    public function __construct(
        $lastUrl,
        $spotifyUrl,
        $spotifyCreatePlaylistUrl,
        $spotifyPlaylistAddUrl,
        $spotifyPlaylistReplaceUrl,
        $spotifyProfileUrl
    ) {
        $this->lastUrl = $lastUrl;
        $this->spotifyUrl = $spotifyUrl;
        $this->spotifyCreatePlaylistUrl = $spotifyCreatePlaylistUrl;
        $this->spotifyPlaylistAddUrl = $spotifyPlaylistAddUrl;
        $this->spotifyPlaylistReplaceUrl = $spotifyPlaylistReplaceUrl;
        $this->spotifyProfileUrl = $spotifyProfileUrl;
    }

    /**
     * @return string
     */
    public function getLastUrl()
    {
        return $this->lastUrl;
    }

    /**
     * @return string
     */
    public function getSpotifyUrl()
    {
        return $this->spotifyUrl;
    }

    /**
     * @return string
     */
    public function getSpotifyCreatePlaylistUrl()
    {
        return $this->spotifyCreatePlaylistUrl;
    }

    /**
     * @return string
     */
    public function getSpotifyPlaylistAddUrl()
    {
        return $this->spotifyPlaylistAddUrl;
    }

    /**
     * @return string
     */
    public function getSpotifyPlaylistReplaceUrl()
    {
        return $this->spotifyPlaylistReplaceUrl;
    }

    /**
     * @return string
     */
    public function getSpotifyProfileUrl()
    {
        return $this->spotifyProfileUrl;
    }
}
