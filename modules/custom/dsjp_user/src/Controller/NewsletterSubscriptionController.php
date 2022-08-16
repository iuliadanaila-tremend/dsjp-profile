<?php

namespace Drupal\dsjp_user\Controller;

use Drupal\block\BlockInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a controller for newsletter/subscription page.
 */
class NewsletterSubscriptionController extends ControllerBase {

  /**
   * Callback for newsletter subscription page.
   */
  public function subscriptionPage() {
    if ($this->currentUser()->isAnonymous()) {
      $block = $this->entityTypeManager()->getStorage('block')->load('simplenewssubscription');
      if ($block instanceof BlockInterface) {
        return $this->entityTypeManager()->getViewBuilder('block')->view($block);
      }

      return new RedirectResponse('<front>');
    }
    $url = Url::fromRoute('simplenews.newsletter_subscriptions_user', [
      'user' => $this->currentUser()->id(),
    ]);

    return new RedirectResponse($url->toString());
  }

}
