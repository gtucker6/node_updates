<?php

namespace Drupal\node_updates\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Playground Juno routes.
 */
class NodeBatchUpdateController extends ControllerBase {


  /**
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * @var QueueWorkerManager
   */
  protected $queueManager;

  public function __construct(QueueFactory $queueFactory, QueueWorkerManagerInterface $queueWorkerManager) {
    $this->queueFactory = $queueFactory;
    $this->queueManager = $queueWorkerManager;
  }


  public static function create(ContainerInterface $container) {
    parent::create($container);

    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * Builds the response.
   */
  public function batchOperations() {
      // Create batch which collects all the specified queue items and process them one after another

      $batch = array(
        'title' => "Process all organizations with batch",
        'operations' => array(),
        'finished' => 'Drupal\node_updatesController\NodeBatchUpdateController::batchFinished',
      );



      $batch['operations'][] = array('Drupal\node_updates\Controller\NodeBatchUpdateController::batchProcess', array());

      // Adds the batch sets
      batch_set($batch);
      // Process the batch and after redirect to the frontpage
      return batch_process('<front>');

  }

  public static function batchProcess(&$context) {

    // todo: finish this with queue and number of items

    $total_nids = count(\Drupal::entityQuery('node')->condition('type', 'organization')->execute());
    $batch_size = 75;
      if (empty($context['sandbox'])) {
        // Set max for progress count later
        $context['sandbox']['max'] = $total_nids;
        $context['sandbox']['current_id'] = 0;
        $context['sandbox']['progress'] = 0;
      }

    $current_nids = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->sort('nid')
      ->condition('nid', $context['sandbox']['current_id'], '>')
      ->range(0, $batch_size)
      ->execute();
      foreach(Node::loadMultiple($current_nids) as $node){
        if($node instanceof NodeInterface) {
        }
        $context['sandbox']['current_id'] = (integer)$node->id();
        $context['sandbox']['progress']++;
      }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(t("%totalResults Organizations were processed.",['%totalResults'=> count($results)]));
    }
    else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addMessage(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
  }

  public function createNewItem() {

  }
}
