<?php

namespace Drupal\simple_git\Plugin\rest\resource\response;

use Drupal\rest\ResourceResponse;
use \Drupal\Core\Cache\CacheableMetadata;

/**
 * It overrides the ResourceResponse invalidating the page caching.
 */
class ResourceResponseNonCached extends ResourceResponse {

  /**
   * Constructor for ResourceResponse objects.
   *
   * @param mixed $data
   *   Response data that should be serialized.
   * @param int $status
   *   The response status code.
   * @param array $headers
   *   An array of response headers.
   */
  public function __construct($data = NULL, $status = 200, $headers = []) {
    parent::__construct($data, $status, $headers);
    $this->disableCache();
  }

  /**
   * It disables the cache for this response.
   */
  private function disableCache() {
    $disable_cache = new CacheableMetadata();
    $disable_cache->setCacheMaxAge(0);

    $this->addCacheableDependency($disable_cache);
  }


}
