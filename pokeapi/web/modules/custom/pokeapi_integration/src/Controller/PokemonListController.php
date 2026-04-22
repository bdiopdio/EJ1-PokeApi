<?php

namespace Drupal\pokeapi_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\pokeapi_integration\Service\PokeApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for displaying the Pokémon list.
 */
class PokemonListController extends ControllerBase {

  /**
   * The PokeAPI client service.
   *
   * @var \Drupal\pokeapi_integration\Service\PokeApiClient
   */
  protected $pokeApiClient;

  /**
   * Constructs a PokemonListController object.
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
    return new self(
      $container->get('pokeapi_integration.client')
    );
  }

  /**
   * Displays the list of Pokémon.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array.
   */
  public function list(Request $request) {
    $limit = 20;
    $offset = (int) $request->query->get('offset', 0);

    $data = $this->pokeApiClient->getPokemonList($limit, $offset);

    // Caso 1: Error de conexión específico
    if (isset($data['error']) && $data['error'] === 'no_connection') {
      $this->messenger()->addError($this->t('No se pudo establecer conexión con el servidor de Pokémon. Revisa tu conexión a internet.'));
      return [
        '#theme' => 'pokemon_list',
        '#pokemons' => [], // Enviamos lista vacía para que el sitio siga operativo
        '#no_connection' => TRUE, // Variable nueva para Twig
      ];
    }

    // Caso 2: El servicio devolvió NULL (otros errores)
    if (!$data) {
      $this->messenger()->addWarning($this->t('No hay datos disponibles en este momento.'));
      return ['#theme' => 'pokemon_list', '#pokemons' => []];
    }

    // Extract Pokémon ID from URL for each Pokémon.
    $pokemons = [];
    foreach ($data['results'] as $pokemon) {
      $url_parts = explode('/', rtrim($pokemon['url'], '/'));
      $id = end($url_parts);
      $pokemons[] = [
        'id' => $id,
        'name' => ucfirst($pokemon['name']),
        'url' => $pokemon['url'],
      ];
    }

    // Calculate pagination.
    $current_page = floor($offset / $limit) + 1;
    $total_count = $data['count'] ?? 0;
    $total_pages = ceil($total_count / $limit);
    $next_offset = $offset + $limit;
    $prev_offset = max(0, $offset - $limit);

    // Logic to determine if there are next and previous pages based on the API response.
    $has_previous = $offset > 0;
    $has_next = ($offset + $limit) < $total_count;

    return [
      '#theme' => 'pokemon_list',
      '#no_connection' => FALSE,
      '#cache' => [
        'contexts' => ['url.query_args:offset'],
      ],
      '#pokemons' => $pokemons,
      '#current_offset' => $offset,
      '#next_offset' => $next_offset,
      '#prev_offset' => $prev_offset,
      '#has_previous' => $has_previous,
      '#has_next' => $has_next,
      '#current_page' => $current_page,
      '#total_pages' => $total_pages,
      '#attached' => [
        'library' => [
          'pokeapi_integration/pokemon_styles',
        ],
      ],
    ];
  }

}
