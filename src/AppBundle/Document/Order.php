<?php
/**
 * Date: 7/2/15
 * Time: 7:07 PM
 */
namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Order
{

    /**
     *
     */
    const STATUS_INITIALIZED = "initialized";
    /**
     *
     */
    const STATUS_FINISHED = "finished";

    /**
     * @MongoDb\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $username;
    /**
     * @MongoDB\Int
     */
    protected $tracksCountRequested;
    /**
     * @MongoDB\Int
     */
    protected $tracksCountAdded;

    /**
     * @MongoDB\String
     */
    protected $status;

    /**
     * Get id
     *
     * @return $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $username
     */
    public function __construct($username)
    {
        $this->username = $username;
        $this->status = self::STATUS_INITIALIZED;
    }

    /**
     * @param $tracksCountAdded
     */
    public function finish($tracksCountAdded)
    {
        $this->tracksCountAdded = $tracksCountAdded;
        $this->status = self::STATUS_FINISHED;
    }

    /**
     * Set tracksCountAdded
     *
     * @param $tracksCountAdded
     *
     * @return self
     */
    public function setTarget($tracksCountAdded)
    {
        $this->tracksCountAdded = $tracksCountAdded;

        return $this;
    }

    /**
     * Get tracksCountAdded
     *
     * @return $tracksCountAdded
     */
    public function getTracksCountAdded()
    {
        return $this->tracksCountAdded;
    }
}