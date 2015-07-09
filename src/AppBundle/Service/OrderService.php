<?php
/**
 * Date: 7/3/15
 * Time: 12:39 PM
 */

namespace AppBundle\Service;

use AppBundle\Document\Order;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class OrderService
 *
 * @package AppBundle\Service
 */
class OrderService
{

    /**
     * @var DocumentManager
     */
    protected $objectManager;

    /**
     * @param DocumentManager $objectManager
     */
    public function __construct(DocumentManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param Order $order
     */
    public function save(Order $order)
    {
        $this->objectManager->persist($order);
        $this->objectManager->flush();
    }
}