<?php
/**
 * Date: 5/25/15
 * Time: 7:32 PM
 */
namespace AppBundle\Tests;

use AppBundle\Service\LastService;
use AppBundle\ValueObject\UrlContainer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Predis\ClientInterface as PredisClientInterface;
use AppBundle\Service\OrderServiceInterface;

class LastServiceTest extends WebTestCase
{
    public function testMainFlow()
    {
        $username = "icesahara";
        $token = "";

        $guzzle = \Mockery::mock(ClientInterface::class);

        $predis = \Mockery::mock(PredisClientInterface::class);

        $orderService = \Mockery::mock(OrderServiceInterface::class);

        $urlContainer = \Mockery::mock(UrlContainer::class);

        $response = new Response(
            200,
            [],
            '{"recenttracks":{"track":[{"name":"Is this Love", "artist": {"#text":"Bob"}}]}}'
        );

        $responseForSpotify = new Response(
            200,
            [],
            '{"tracks":{"items":[{"uri":"uri"}]}}'
        );

        $urlContainer->shouldReceive("getLastUrl");
        $guzzle->shouldReceive("request")->once()->andReturn($response);
        $orderService->shouldReceive("save");
        $predis->shouldReceive("get");
        $urlContainer->shouldReceive("getSpotifyUrl");
        $guzzle->shouldReceive("request")->andReturn($responseForSpotify);
        $predis->shouldReceive("set");
        $urlContainer->shouldReceive("getSpotifyProfileUrl");
        $urlContainer->shouldReceive("getSpotifyCreatePlaylistUrl");
        $urlContainer->shouldReceive("getSpotifyPlaylistAddUrl");

        $service = new LastService(
            $guzzle,
            $predis,
            "",
            $orderService,
            $urlContainer
        );

        $order = $service->grab($username, $token, "recent");

        $this->assertGreaterThan(0, $order->getTracksCountAdded());
    }
}