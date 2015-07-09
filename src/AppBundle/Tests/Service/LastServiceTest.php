<?php
/**
 * Date: 5/25/15
 * Time: 7:32 PM
 */
namespace AppBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LastServiceTest extends WebTestCase
{

    public function testMain()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $username = "icesahara";
        $token = "";
        $container->get("last.service")->grab($username, $token);
    }
}