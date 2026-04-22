<?php

namespace Drupal\pokeapi_integration\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Service to interact with the PokeAPI.
 */
class PokeApiClient {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The base URL for the PokeAPI.
   *
   * @var string
   */
  protected $baseUrl = 'https://pokeapi.co/api/v2';

  /**
   * Constructs a PokeApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Fetches a list of Pokémon.
   *
   * @param int $limit
   *   The number of Pokémon to fetch. Defaults to 20.
   * @param int $offset
   *   The offset for pagination. Defaults to 0.
   *
   * @return array|null
   *   An array containing the list of Pokémon, or NULL on failure.
   */
  public function getPokemonList($limit = 20, $offset = 0) {
    $cache_key = "pokeapi_integration:list:{$limit}:{$offset}";

    //return ['error' => 'no_connection'];

    try {
      // Check if data is cached.
      if ($cached = $this->cache->get($cache_key)) {
        return $cached->data;
      }

      $response = $this->httpClient->request('GET', "{$this->baseUrl}/pokemon", [
        'query' => [
          'limit' => $limit,
          'offset' => $offset,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      // Cache for 1 hour.
      $this->cache->set($cache_key, $data, time() + 3600);
      
      return $data;
    }
    catch (ConnectException $e) {
      // Error de conexión (DNS, timeout, sin internet)
      $this->loggerFactory->get('pokeapi_integration')->error('Sin conexión a internet.');
      return ['error' => 'no_connection']; 
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('pokeapi_integration')
        ->error('Failed to fetch Pokémon list: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Fetches details for a specific Pokémon.
   *
   * @param int $id
   *   The Pokémon ID.
   *
   * @return array|null
   *   An array containing the Pokémon details, or NULL on failure.
   */
  public function getPokemonDetail($id) {
    $cache_key = "pokeapi_integration:detail:{$id}";
    
    // Check if data is cached.
    if ($cached = $this->cache->get($cache_key)) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->request('GET', "{$this->baseUrl}/pokemon/{$id}");
      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      // Cache for 24 hours (Pokémon data rarely changes).
      $this->cache->set($cache_key, $data, time() + 86400);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('pokeapi_integration')
        ->error('Failed to fetch Pokémon detail for ID @id: @message', [
          '@id' => $id,
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }
  }

}
