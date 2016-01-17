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

    /**
     * @param int $limit
     *
     * @return array
     */
    public function getRecent($limit = 10)
    {
        $query = $this->objectManager
            ->createQueryBuilder("AppBundle:Order")
            ->find()
            ->sort("createdAt", -1)
            ->limit($limit)
            ->getQuery();

        $results = $query->toArray();

        return $results;
    }
}