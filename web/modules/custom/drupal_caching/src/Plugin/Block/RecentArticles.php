<?php

namespace Drupal\drupal_caching\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RecentArticles' block.
 *
 * @Block(
 *   id = "recent_articles",
 *   admin_label = @Translation("Recent Articles")
 * )
 */
class RecentArticles extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupalist object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current_user.
   */
  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // Get the recent 3 articles.
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE)
      ->range(0, 3);

    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);

    // Titles of the node.
    $titles = [];
    foreach ($nodes as $node) {
      $titles[] = $node->getTitle();
    }

    // Get cache tags for the nodes.
    $cache_tags = [];
    foreach ($nodes as $node) {
      $cache_tags = Cache::mergeTags($cache_tags, $node->getCacheTags());
    }
    // User Email Address.
    $email = $this->currentUser->getEmail();

    return [
      '#theme' => 'item_list',
      '#items' => array_merge(['Current User Email: ' . $email], $titles),
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => ['user'],
      ],
    ];
  }
}
