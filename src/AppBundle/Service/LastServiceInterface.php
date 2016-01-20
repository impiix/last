<?php

namespace AppBundle\Service;

use AppBundle\Document\Order;

/**
 * Interface LastServiceInterface
 * @package AppBundle\Service
 */
interface LastServiceInterface
{
    /**
     * @param string $lastUsername
     * @param string $type
     * @param string $token
     *
     * @return Order
     */
    public function grab($lastUsername, $type, $token);

    /**
     * @param string $username
     * @param string $token
     *
     * @return bool
     */
    public function follow($username, $token);
}
