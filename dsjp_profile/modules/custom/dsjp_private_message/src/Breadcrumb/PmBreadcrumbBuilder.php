<?php

namespace Drupal\dsjp_private_message\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a custom breadcrumb builder for Private Message delete page.
 */
class PmBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The account proxy service property.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * PmBreadcrumbBuilder constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $accountProxy
   *   The account proxy service.
   */
  public function __construct(AccountProxy $accountProxy) {
    $this->currentUser = $accountProxy;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() == 'entity.private_message_thread.delete_form') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path.parent', 'url.path.is_front']);
    $links = [
      Link::createFromRoute($this->t('Home'), '<front>'),
      Link::createFromRoute($this->t('My account'), 'entity.user.canonical', [
        'user' => $this->currentUser->id(),
      ]),
    ];

    return $breadcrumb->setLinks($links);
  }

}
