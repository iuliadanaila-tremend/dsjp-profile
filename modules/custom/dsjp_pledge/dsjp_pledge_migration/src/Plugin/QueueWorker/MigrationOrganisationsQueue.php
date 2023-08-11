<?php

namespace Drupal\dsjp_pledge_migration\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for dsjp_pledge_migration_organisations_social_queue.
 *
 * @QueueWorker(
 *   id = "dsjp_pledge_migration_organisations_social_queue",
 *   title = @Translation("Connect organisations social links."),
 *   cron = {"time" = 5}
 * )
 */
class MigrationOrganisationsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->database = $container->get('database');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data)) {
      foreach ($data as $organization_data) {
        $gids = $this->entityTypeManager->getStorage('group')->getQuery()
          ->condition('label', $organization_data['name'])
          ->condition('field_dsj_contact_email_pledges', $organization_data['pledge_mail'])
          ->condition('field_dsj_contact_person_pledges', $organization_data['pledge_person'])
          ->condition('type', 'dsj_organization')
          ->accessCheck(TRUE)
          ->execute();
        foreach ($gids as $gid) {
          // Verify if the group organisation contact mail is the same as on the
          // old website.
          $query = $this->database->select('dsj_pledge_users', 'pu')
            ->condition('pu.mail', $organization_data['mail'])
            ->condition('pu.oid', $gid)
            ->countQuery()
            ->execute()->fetchField();
          if (!empty($query)) {
            $group = $this->entityTypeManager->getStorage('group')->load($gid);
            if ($group instanceof GroupInterface) {
              $socials = json_decode($organization_data['social'], TRUE);
              if (!empty($socials['facebook'])) {
                $group->set('field_dsj_facebook_link', $socials['facebook']);
              }
              if (!empty($socials['twitter'])) {
                $group->set('field_dsj_twitter_link', $socials['twitter']);
              }
              if (!empty($socials['linkedin'])) {
                $group->set('field_dsj_linkedin_link', $socials['linkedin']);
              }
              $group->save();
            }
          }
        }
      }
    }
  }

}
