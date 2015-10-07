<?php

namespace AppBundle\Service;

use AppBundle\Document\Order;

/**
 * Interface OrderServiceInterface
 * @package AppBundle\Service
 */
interface OrderServiceInterface
{
    /**
     * @param Order $order
     */
    public function save(Order $order);
}
