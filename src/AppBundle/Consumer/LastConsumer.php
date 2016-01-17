<?php
/**
 * Date: 5/22/15
 * Time: 1:05 PM
 */
namespace AppBundle\Consumer;

use AppBundle\Service\LastService;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class LastConsumer
 *
 * @package AppBundle\Consumer
 */
class LastConsumer implements ConsumerInterface
{

    /**
     * @var LastService
     */
    private $lastService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param LastService $lastService
     * @param Logger      $logger
     */
    public function __construct(LastService $lastService, Logger $logger)
    {
        $this->lastService = $lastService;
        $this->logger = $logger;
    }

    /**
     * @param AMQPMessage $msg
     *
     * @return int
     */
    public function execute(AMQPMessage $msg)
    {
        try {
            $data = unserialize($msg->body);
            $tracks = $this->lastService->grab($data['username'], $data['token'], $data['type']);
            echo sprintf("%s: Imported %d tracks...\n", date("Y.m.d H:i:s"), count($tracks));
        } catch (\Exception $e) {
            $this->logger->error(json_encode(['message' => $e->getMessage(), 'data' => $data]));
            return ConsumerInterface::MSG_REJECT;
        }

    }
}