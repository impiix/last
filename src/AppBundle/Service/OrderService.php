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
class OrderService implements OrderServiceInterface
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
     * {@inheritdoc}
     */
    public function save(Order $order)
    {
        $this->objectManager->persist($order);
        $this->objectManager->flush();
    }

    public function test()
    {
        $query = $this->objectManager
            ->createQueryBuilder("AppBundle:Order")
            ->find()
            ->sort("createdAt", -1)
            ->limit(10)
            ->getQuery();

        $results = $query->toArray();

        return $results;
    }
}