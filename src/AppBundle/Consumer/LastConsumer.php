<?php
/** 
 * Date: 5/22/15
 * Time: 1:05 PM
 */
namespace AppBundle\Consumer;

use AppBundle\Service\LastService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class LastConsumer implements ConsumerInterface
{
    private $lastService;

    public function __construct(LastService $lastService) {
        $this->lastService = $lastService;
    }

    public function execute(AMQPMessage $msg) {
        try {
            $data = unserialize($msg->body);
            $tracks = $this->lastService->grab($data['username'], $data['token']);
            echo sprintf("%s: Imported %d tracks...\n", date("Y.m.d H:i:s"), count($tracks));
        } catch(\Exception $e) {
            return false;
        }
    }

}