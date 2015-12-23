<?php
/**
 * Date: 5/25/15
 * Time: 7:32 PM
 */
namespace AppBundle\Tests;

use AppBundle\Service\LastService;
use GuzzleHttp\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Predis\ClientInterface as PredisClientInterface;
use AppBundle\Service\OrderServiceInterface;

class LastServiceTest extends WebTestCase
{

    public function testMain()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $username = "icesahara";
        $token = "";

        $guzzle = $this->getMockBuilder(ClientInterface::class)->getMock();
        $guzzle->expects("get");
        $predis = $this->getMockBuilder(PredisClientInterface::class)->getMock();
        $orderService = $this->getMockBuilder(OrderServiceInterface::class)->getMock();

        $service = new LastService(
            $guzzle,
            $predis,
            "",
            "",
            "",
            "",
            "",
            "",
            $orderService
        );

        $service->grab($username, $token, "recent");
    }
}