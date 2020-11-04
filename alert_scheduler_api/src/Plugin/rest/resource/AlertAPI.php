<?php

namespace Drupal\alert_scheduler_api\Plugin\rest\resource;

use Drupal\alert_scheduler_api\Entity\Alert;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides access to request alerts via REST.
 *
 * @RestResource(
 *   id = "scheduled_alert",
 *   label = @Translation("Alerts"),
 *   uri_paths = {
 *     "canonical" = "/api/alerts",
 *   }
 * )
 */
class AlertAPI extends ResourceBase {

  protected $alertStorage;

  public function __construct(ContentEntityStorageInterface $alert_storage, array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->alertStorage = $alert_storage;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var AlertStorage $alert_storage */
    $alert_storage = $container->get('entity_type.manager')->getStorage('scheduled_alert');
    /** @var LoggerChannel $logger */
    $logger = $container->get('logger.channel.rest');
    return new static($alert_storage, $configuration, $plugin_id, $plugin_definition, $container->getParameter('serializer.formats'), $logger);
  }

  public function get() {
    $alerts = $this->alertStorage->loadMultiple();
    $json_alerts = [];
    foreach ($alerts as $alert) {
      /** @var \Drupal\alert_scheduler_api\Entity\AlertInterface $alert */
      $json_alerts[] = [
        'id' => $alert->id(),
        'title' => $alert->getTitle(),
        'message' => $alert->getMessage(),
        'interval' => [
          'from' => $alert->getInterval()->start()->format('c'),
          'to' => $alert->getInterval()->end()->format('c'),
        ],
      ];
    }

    $response = new ResourceResponse($json_alerts);
    $response->setMaxAge(60);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'contexts' => [
          'user',
        ],
        'tags' => [
          'scheduled_alert_list',
        ]
      ]
    ]));

    return $response;
  }

}
