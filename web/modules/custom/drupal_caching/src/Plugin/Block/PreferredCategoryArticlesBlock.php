<?php

namespace Drupal\drupal_caching\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PreferredCategoryArticlesBlock' block.
 *
 * @Block(
 *   id = "preferred_category_articles_block",
 *   admin_label = @Translation("Preferred Category Articles Block")
 * )
 */
class PreferredCategoryArticlesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PreferredCategoryArticlesBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the preferred category term ID from the current user.
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $preferred_category_tid = $user->get('field_preferred_category')->target_id;

    // If no preferred category is set, return an empty render array.
    if (empty($preferred_category_tid)) {
      return [
        '#markup' => $this->t('No preferred category set.'),
      ];
    }

    // Get Nodes which have Preferred Category.
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'article')
      ->condition('field_category.target_id', $preferred_category_tid)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE)
      ->range(0, 5);

    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);

    // Node Titles.
    $titles = [];
    foreach ($nodes as $node) {
      $titles[] = $node->getTitle();
    }

    return [
        '#theme' => 'item_list',
        '#items' => $titles,
        '#cache' => [
          'contexts' => ['user', 'preferred_category'],
        ],
      ];      
  }
}
