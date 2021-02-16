<?php

namespace Drupal\alert_scheduler_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\rest\Annotation\RestResource;
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

  /**
   * The alert entity storage handler.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $alertStorage;

  /**
   * The alert storage handler.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   An entity storage handler for "scheduled_alert" entities.
   */
  protected function alertStorage() {
    return $this->alertStorage;
  }

  /**
   * Create a new AlertAPI resource instance.
   *
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $alert_storage
   *   An entity storage handler.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ContentEntityStorageInterface $alert_storage, array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->alertStorage = $alert_storage;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /* @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager')
        ->getStorage('scheduled_alert'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.channel.rest'));
  }

  /**
   * GET request handler.
   *
   * @return \Drupal\rest\ResourceResponse
   *   A resource response.
   */
  public function get() {
    $json_alerts = [];
    foreach ($this->loadAlerts() as $alert) {
      $json_alerts[] = [
        'id' => $alert->id(),
        'title' => $alert->getTitle(),
        'message' => $alert->getMessage(),
        'interval' => [
          'from' => $alert
            ->getInterval()
            ->start()
            ->format('c'),
          'to' => $alert
            ->getInterval()
            ->end()
            ->format('c'),
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
        ],
      ],
    ]));

    return $response;
  }

  /**
   * Load the alert entities with the given IDs.
   *
   * @param array|null $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\alert_scheduler_api\Entity\AlertInterface[]
   *   An array of "scheduled_alert" entities.
   */
  protected function loadAlerts(array $ids = NULL) {
    /** @var \Drupal\alert_scheduler_api\Entity\AlertInterface[] $alerts */
    $alerts = $this->alertStorage()
      ->loadMultiple($ids);
    return $alerts;
  }

}
