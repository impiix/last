<?php
/**
 * Date: 5/25/15
 * Time: 7:32 PM
 */
namespace AppBundle\Tests;

use AppBundle\Service\LastService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LastServiceTest extends WebTestCase
{

    public function testMain()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $username = "icesahara";
        $token = "";

        $service = $this->getMockBuilder('AppBundle\Service\LastService')
            ->disableOriginalConstructor()
            ->getMock();

        $service = new LastService(
            ''
        );

        $service->grab($username, $token, "recent");
    }
}