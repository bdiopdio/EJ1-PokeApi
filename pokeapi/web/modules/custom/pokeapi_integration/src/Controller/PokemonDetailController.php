<?php

namespace Drupal\pokeapi_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\pokeapi_integration\Service\PokeApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for displaying Pokémon details.
 */
class PokemonDetailController extends ControllerBase {

  /**
   * The PokeAPI client service.
   *
   * @var \Drupal\pokeapi_integration\Service\PokeApiClient
   */
  protected $pokeApiClient;

  /**
   * Constructs a PokemonDetailController object.
   *
   * @param \Drupal\pokeapi_integration\Service\PokeApiClient $poke_api_client
   *   The PokeAPI client service.
   */
  public function __construct(PokeApiClient $poke_api_client) {
    $this->pokeApiClient = $poke_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pokeapi_integration.client')
    );
  }

  /**
   * Displays details for a specific Pokémon.
   *
   * @param int $id
   *   The Pokémon ID.
   *
   * @return array
   *   A render array.
   */
  public function detail(Request $request, $id) {
    $from_offset = $request->query->getInt('from_offset', 0);
    $pokemon = $this->pokeApiClient->getPokemonDetail($id);

    if (!$pokemon) {
      throw new NotFoundHttpException();
    }

    // Process abilities.
    $abilities = [];
    foreach ($pokemon['abilities'] as $ability) {
      $abilities[] = ucfirst(str_replace('-', ' ', $ability['ability']['name']));
    }

    // Process types.
    $types = [];
    foreach ($pokemon['types'] as $type) {
      $types[] = ucfirst($type['type']['name']);
    }

    // Process stats.
    $stats = [];
    foreach ($pokemon['stats'] as $stat) {
      $stats[] = [
        'name' => ucfirst(str_replace('-', ' ', $stat['stat']['name'])),
        'value' => $stat['base_stat'],
      ];
    }

    // Calculate previous and next Pokemon IDs for navigation
    $previous_id = $pokemon['id'] > 1 ? $pokemon['id'] - 1 : null;
    $next_id = $pokemon['id'] < 1025 ? $pokemon['id'] + 1 : null;

    return [
      '#theme' => 'pokemon_detail',
      '#from_offset' => $from_offset,
      '#cache' => [
        'contexts' => ['url.query_args:from_offset'],
      ],
      '#pokemon' => [
        'id' => $pokemon['id'],
        'name' => ucfirst($pokemon['name']),
        'height' => $pokemon['height'] / 10, // Convert to meters.
        'weight' => $pokemon['weight'] / 10, // Convert to kg.
        'image' => $pokemon['sprites']['other']['official-artwork']['front_default'] ?? $pokemon['sprites']['front_default'],
        'sprites' => $pokemon['sprites'],
        'abilities' => $abilities,
        'types' => $types,
        'stats' => $stats,
      ],
      '#previous_id' => $previous_id,
      '#next_id' => $next_id,
      '#attached' => [
        'library' => [
          'pokeapi_integration/pokemon_styles',
        ],
      ],
    ];
  }

  /**
   * Returns the title for the Pokémon detail page.
   *
   * @param int $id
   *   The Pokémon ID.
   *
   * @return string
   *   The page title.
   */
  public function getTitle($id) {
    $pokemon = $this->pokeApiClient->getPokemonDetail($id);
    
    if ($pokemon) {
      return ucfirst($pokemon['name']);
    }
    
    return $this->t('Pokémon #@id', ['@id' => $id]);
  }

}
