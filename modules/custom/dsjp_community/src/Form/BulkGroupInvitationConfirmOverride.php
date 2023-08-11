<?php

namespace Drupal\dsjp_community\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\ginvite\Form\BulkGroupInvitationConfirm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for inviting members.
 */
class BulkGroupInvitationConfirmOverride extends BulkGroupInvitationConfirm {

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->routeMatch = $container->get('current_route_match');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $message = $this->routeMatch->getParameter('group')->label();
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $message = '<span class="group-invitation-confirm-message">' . $this->t('Are you sure you want to send an invitation to all e-mails listed below?') . '</span>';
    $email_list_markup = "";
    foreach ($this->tempstore['emails'] as $email) {
      $email_list_markup .= "{$email} <br />";
    }
    $recipients = '<span class="group-invitation-confirm-recipients">' . $this->t("Invitation recipients: <br /> @email_list",
      [
        '@email_list' => new FormattableMarkup($email_list_markup, []),
      ]
    ) . '</span>';
    $description = $message . '<br />' . $recipients;
    return $description;
  }

}
