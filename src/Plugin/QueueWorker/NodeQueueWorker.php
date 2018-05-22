<?php
/**
 * Created by PhpStorm.
 * User: gloria
 * Date: 4/18/18
 * Time: 8:43 AM
 */

namespace Drupal\node_updates\Plugin\QueueWorker;


use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use \Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates a node's field items.
 *
 * @QueueWorker(
 *   id = "node_main_queue",
 *   title = @Translation("Node Update Queue"),
 * )
 */
class NodeQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

    protected $logger;



    public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
             $configuration,
             $plugin_id,
             $plugin_definition,
             $container->get('logger.factory')->get('node_updates')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processItem($data) {
        if($data instanceof NodeInterface) {
          // add new value to database for valid nids
          $data->set('queued', \Drupal::time()->getRequestTime());
          $data->save();
          $this->logger->notice($data->getType() . ' successfully saved with nid:' . $data->id());
        }
    }
}