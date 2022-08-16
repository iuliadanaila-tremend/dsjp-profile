<?php

namespace Drupal\dsjp_translation\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\node\NodeInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides an event subscriber for migration entity save.
 */
class MigratePostSave implements EventSubscriberInterface {

  /**
   * The entity type manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MigratePostSave constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::POST_ROW_SAVE] = ['onPostRowSave'];
    return $events;
  }

  /**
   * Triggers the post row save on migrate event.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post save event.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $translator = $this->entityTypeManager->getStorage('tmgmt_translator')->load('etranslation');
    if ($translator instanceof TranslatorInterface) {
      $availability = $translator->checkAvailable();
      if ($availability->getSuccess()) {
        $source = $event->getRow()->getSource();
        if (isset($source['constants']['language']) && $source['constants']['language'] != 'en') {
          $ids = $event->getDestinationIdValues();
          $node = $this->entityTypeManager->getStorage('node')->load(reset($ids));
          if ($node instanceof NodeInterface) {
            // If there is an existing node with the imported status and with a
            // job item available, delete the job item and add a new one to the
            // main job entity.
            $moderationStatus = $node->get('moderation_state')->getString();
            if ($moderationStatus == 'imported') {
              $existingJob = $this->checkExistingJob($node->id(), $source['constants']['language']);
            }
            // If a job item was deleted, we use the existing job to add the new
            // item to, otherwise we create a new job.
            $job = !empty($existingJob) ? $existingJob : tmgmt_job_create($source['constants']['language'], 'en', $source['constants']['group_owner'], [
              'translator' => 'etranslation',
            ]);
            if ($job instanceof JobInterface) {
              $job->addItem('content', 'node', $node->id());
              $job->save();
              $job->requestTranslation();
            }
          }
        }
      }
    }
  }

  /**
   * Check if an existing job exists for the imported node and delete the item.
   *
   * @param int $nid
   *   The id of the imported node.
   * @param string $migrationLanguage
   *   The migration langcode.
   *
   * @return string|JobInterface
   *   Returns the Job entity if it exists, empty string otherwise.
   */
  protected function checkExistingJob($nid, $migrationLanguage) {
    $existingJob = '';
    // Load the job item by the node id.
    $existingJobItems = $this->entityTypeManager->getStorage('tmgmt_job_item')->loadByProperties([
      'item_type' => 'node',
      'item_id' => $nid,
    ]);
    if (!empty($existingJobItems)) {
      // Delete the job item if the migration language source is the
      // same as the job's one.
      foreach ($existingJobItems as $jobItem) {
        $job = $jobItem->getJob();
        $sourceLanguage = $job->getSourceLangcode();
        if ($sourceLanguage == $migrationLanguage) {
          $jobItem->delete();
          $existingJob = $job;
        }
      }
    }

    return $existingJob;
  }

}
