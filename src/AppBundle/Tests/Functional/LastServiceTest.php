<?php
/**
 * Date: 1/20/16
 * Time: 4:01 PM
 */
namespace AppBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
/**
 * Class LastServiceTest
 */
class LastServiceTest extends WebTestCase
{
    public function testMain()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $lastService = $container->get("last.service");

        $token = $container->getParameter("token_test");

        $order = $lastService->grab('icesahara', 'recent', $token);

        $this->assertGreaterThan(1, $order->getTracksCountAdded());
    }
}
