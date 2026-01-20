<?php

namespace Drupal\secure_embed\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/**
 * Service for managing secure embed operations.
 */
class SecureEmbedManager {

  /**
   * Cache ID for compiled domain patterns.
   */
  const CACHE_CID = 'secure_embed:domain_patterns';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Cached config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|null
   */
  protected $config;

  /**
   * Cached domain patterns.
   *
   * @var array|null
   */
  protected $domainPatterns;

  /**
   * Constructs a SecureEmbedManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets the module configuration (cached per request).
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  public function getConfig() {
    if ($this->config === NULL) {
      $this->config = $this->configFactory->get('secure_embed.settings');
    }
    return $this->config;
  }

  /**
   * Validates a URL against the domain whitelist.
   *
   * @param string $url
   *   The URL to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateUrl($url) {
    // Must be a valid URL.
    if (!UrlHelper::isValid($url, TRUE)) {
      return FALSE;
    }

    $parsed = parse_url($url);
    if (empty($parsed['host'])) {
      return FALSE;
    }

    // Enforce HTTPS only.
    if (empty($parsed['scheme']) || strtolower($parsed['scheme']) !== 'https') {
      return FALSE;
    }

    $host = strtolower($parsed['host']);
    $patterns = $this->getDomainPatterns();

    // Check against compiled patterns.
    foreach ($patterns as $pattern) {
      if ($pattern['type'] === 'exact' && $host === $pattern['domain']) {
        return $this->invokeValidateAlter($url, TRUE);
      }
      if ($pattern['type'] === 'wildcard') {
        // Must match exactly: *.example.com matches sub.example.com, not example.com
        if (preg_match($pattern['regex'], $host)) {
          return $this->invokeValidateAlter($url, TRUE);
        }
      }
      if ($pattern['type'] === 'www' && ($host === $pattern['domain'] || $host === 'www.' . $pattern['domain'])) {
        return $this->invokeValidateAlter($url, TRUE);
      }
    }

    return $this->invokeValidateAlter($url, FALSE);
  }

  /**
   * Invokes hook_secure_embed_validate_alter.
   *
   * @param string $url
   *   The URL being validated.
   * @param bool $is_valid
   *   The current validation result.
   *
   * @return bool
   *   The final validation result.
   */
  protected function invokeValidateAlter($url, $is_valid) {
    $this->moduleHandler->alter('secure_embed_validate', $is_valid, $url);
    return $is_valid;
  }

  /**
   * Gets compiled domain patterns (cached).
   *
   * @return array
   *   Array of compiled domain patterns.
   */
  protected function getDomainPatterns() {
    if ($this->domainPatterns !== NULL) {
      return $this->domainPatterns;
    }

    // Try cache first.
    $cached = $this->cache->get(self::CACHE_CID);
    if ($cached) {
      $this->domainPatterns = $cached->data;
      return $this->domainPatterns;
    }

    // Compile patterns from config.
    $this->domainPatterns = $this->compileDomainPatterns();

    // Cache with config dependency.
    $this->cache->set(
      self::CACHE_CID,
      $this->domainPatterns,
      CacheBackendInterface::CACHE_PERMANENT,
      ['config:secure_embed.settings']
    );

    return $this->domainPatterns;
  }

  /**
   * Compiles domain patterns from configuration.
   *
   * @return array
   *   Compiled patterns.
   */
  protected function compileDomainPatterns() {
    $config = $this->getConfig();
    $domains_raw = $config->get('allowed_domains') ?? '';
    $domains = array_filter(array_map('trim', explode("\n", $domains_raw)));

    $patterns = [];
    foreach ($domains as $domain) {
      $domain = strtolower($domain);

      if (strpos($domain, '*.') === 0) {
        // Wildcard pattern: *.example.com
        $base = substr($domain, 2);
        $patterns[] = [
          'type' => 'wildcard',
          'domain' => $base,
          'regex' => '/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?\.' . preg_quote($base, '/') . '$/',
        ];
        // Also allow the base domain itself.
        $patterns[] = [
          'type' => 'exact',
          'domain' => $base,
        ];
      }
      else {
        // Exact match, but also allow www prefix.
        $patterns[] = [
          'type' => 'www',
          'domain' => ltrim($domain, 'www.'),
        ];
      }
    }

    return $patterns;
  }

  /**
   * Clears the domain pattern cache.
   */
  public function clearCache() {
    $this->cache->delete(self::CACHE_CID);
    $this->domainPatterns = NULL;
    $this->config = NULL;
  }

  /**
   * Encodes embed data with HMAC signature.
   *
   * @param array $data
   *   The embed data.
   *
   * @return string
   *   The signed, encoded token.
   */
  public function encodeData(array $data) {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES);
    $signature = $this->generateSignature($json);

    return base64_encode(json_encode([
      'data' => $data,
      'sig' => $signature,
    ], JSON_UNESCAPED_SLASHES));
  }

  /**
   * Decodes embed data and verifies HMAC signature.
   *
   * @param string $encoded
   *   The encoded token.
   *
   * @return array|null
   *   The decoded data, or NULL if invalid/tampered.
   */
  public function decodeData($encoded) {
    $decoded = base64_decode($encoded, TRUE);
    if ($decoded === FALSE) {
      return NULL;
    }

    $payload = json_decode($decoded, TRUE);
    if (!is_array($payload) || !isset($payload['data']) || !isset($payload['sig'])) {
      // Legacy token without signature - reject for security.
      return NULL;
    }

    // Verify signature.
    $expected_sig = $this->generateSignature(json_encode($payload['data'], JSON_UNESCAPED_SLASHES));
    if (!hash_equals($expected_sig, $payload['sig'])) {
      return NULL;
    }

    return $payload['data'];
  }

  /**
   * Generates HMAC signature for data.
   *
   * @param string $data
   *   The data to sign.
   *
   * @return string
   *   The signature.
   */
  protected function generateSignature($data) {
    $key = Settings::getHashSalt() . ':secure_embed';
    return hash_hmac('sha256', $data, $key);
  }

  /**
   * Sanitizes embed parameters.
   *
   * @param array $params
   *   Raw parameters.
   *
   * @return array
   *   Sanitized parameters.
   */
  public function sanitizeParams(array $params) {
    $sanitized = [];

    // URL - validate and store as-is (will be escaped on output).
    if (!empty($params['url']) && $this->validateUrl($params['url'])) {
      $sanitized['url'] = $params['url'];
    }

    // Title - strip tags only, escape on output.
    if (!empty($params['title'])) {
      $sanitized['title'] = strip_tags($params['title']);
    }

    // Boolean flags.
    $sanitized['responsive'] = !empty($params['responsive']);
    $sanitized['lazy_loading'] = !empty($params['lazy_loading']);

    // Aspect ratio - whitelist allowed values.
    $allowed_ratios = ['16:9', '4:3', '1:1', '21:9', '9:16'];
    $ratio = $params['aspect_ratio'] ?? '16:9';
    $sanitized['aspect_ratio'] = in_array($ratio, $allowed_ratios) ? $ratio : '16:9';

    // Dimensions - numeric with unit.
    if (!empty($params['width'])) {
      $sanitized['width'] = $this->sanitizeDimension($params['width']);
    }
    if (!empty($params['height'])) {
      $sanitized['height'] = $this->sanitizeDimension($params['height']);
    }

    // Custom class - alphanumeric, hyphens, underscores, spaces only.
    if (!empty($params['custom_class'])) {
      $sanitized['custom_class'] = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $params['custom_class']);
    }

    // Allow other modules to alter.
    $this->moduleHandler->alter('secure_embed_params', $sanitized, $params);

    return $sanitized;
  }

  /**
   * Sanitizes a dimension value.
   *
   * @param string $value
   *   The dimension value.
   *
   * @return string
   *   Sanitized value.
   */
  protected function sanitizeDimension($value) {
    $value = trim($value);

    // Allow percentage.
    if (preg_match('/^(\d+)%$/', $value, $matches)) {
      return min((int) $matches[1], 100) . '%';
    }

    // Allow px, em, rem, vw, vh.
    if (preg_match('/^(\d+)(px|em|rem|vw|vh)?$/', $value, $matches)) {
      $num = (int) $matches[1];
      $unit = $matches[2] ?? 'px';
      return $num . $unit;
    }

    // Default.
    return '100%';
  }

  /**
   * Builds a render array for the secure embed.
   *
   * @param array $embed_data
   *   The sanitized embed data.
   *
   * @return array
   *   A Drupal render array.
   */
  public function buildRenderArray(array $embed_data) {
    $config = $this->getConfig();

    // Validate URL one more time.
    if (empty($embed_data['url']) || !$this->validateUrl($embed_data['url'])) {
      return [
        '#theme' => 'secure_embed_error',
        '#message' => t('This embed URL is not allowed.'),
      ];
    }

    $responsive = !empty($embed_data['responsive']);
    $aspect_ratio = $embed_data['aspect_ratio'] ?? '16:9';

    // Build wrapper classes.
    $wrapper_classes = ['secure-embed'];
    if ($responsive) {
      $wrapper_classes[] = 'secure-embed--responsive';
      $wrapper_classes[] = 'secure-embed--ratio-' . str_replace(':', '-', $aspect_ratio);
    }
    if (!empty($embed_data['custom_class'])) {
      $wrapper_classes[] = $embed_data['custom_class'];
    }

    // Build iframe attributes.
    $iframe_attributes = [
      'src' => $embed_data['url'],
      'frameborder' => '0',
    ];

    if (!empty($embed_data['title'])) {
      $iframe_attributes['title'] = $embed_data['title'];
    }

    if (!empty($embed_data['lazy_loading'])) {
      $iframe_attributes['loading'] = 'lazy';
    }

    // Sandbox attributes.
    if ($config->get('enable_sandbox')) {
      $sandbox_attrs = $config->get('sandbox_attributes') ?? [];
      if (!empty($sandbox_attrs)) {
        $iframe_attributes['sandbox'] = implode(' ', $sandbox_attrs);
      }
    }

    // Allow attributes (Permissions Policy).
    $allow_attrs = $config->get('allow_attributes') ?? [];
    if (!empty($allow_attrs)) {
      $iframe_attributes['allow'] = implode('; ', $allow_attrs);
    }

    if (in_array('fullscreen', $allow_attrs)) {
      $iframe_attributes['allowfullscreen'] = TRUE;
    }

    // Dimensions for non-responsive.
    if (!$responsive) {
      $iframe_attributes['width'] = $embed_data['width'] ?? '100%';
      $iframe_attributes['height'] = $embed_data['height'] ?? '400px';
    }

    // Build wrapper attributes.
    $wrapper_attributes = [
      'class' => $wrapper_classes,
    ];
    if (!$responsive && !empty($embed_data['width'])) {
      $wrapper_attributes['style'] = 'width: ' . $embed_data['width'] . ';';
    }

    $build = [
      '#theme' => 'secure_embed',
      '#wrapper_attributes' => $wrapper_attributes,
      '#iframe_attributes' => $iframe_attributes,
      '#responsive' => $responsive,
      '#attached' => [
        'library' => ['secure_embed/secure_embed.frontend'],
      ],
      '#cache' => [
        'tags' => ['config:secure_embed.settings'],
      ],
    ];

    // Allow other modules to alter the render array.
    $this->moduleHandler->alter('secure_embed_render', $build, $embed_data);

    return $build;
  }

  /**
   * Gets allowed domains as a formatted list.
   *
   * @return array
   *   Array of allowed domain strings.
   */
  public function getAllowedDomainsList() {
    $config = $this->getConfig();
    $domains_raw = $config->get('allowed_domains') ?? '';
    return array_filter(array_map('trim', explode("\n", $domains_raw)));
  }

}
