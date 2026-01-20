<?php

namespace Drupal\secure_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to process secure embed tokens.
 *
 * @Filter(
 *   id = "secure_embed_filter",
 *   title = @Translation("Secure Embed Filter"),
 *   description = @Translation("Converts secure embed tokens into sanitized iframe elements."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 100
 * )
 */
class SecureEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a SecureEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Find all secure embed tokens.
    if (strpos($text, 'secure-embed-token') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $tokens = $xpath->query('//div[contains(@class, "secure-embed-token")][@data-secure-embed]');

    if ($tokens->length === 0) {
      return $result;
    }

    $config = $this->configFactory->get('secure_embed.settings');

    foreach ($tokens as $token) {
      $encoded_data = $token->getAttribute('data-secure-embed');
      if (empty($encoded_data)) {
        continue;
      }

      // Decode the embed data.
      $embed_data = secure_embed_decode_data($encoded_data);
      if (empty($embed_data) || empty($embed_data['url'])) {
        continue;
      }

      // Validate the URL again for security.
      if (!secure_embed_validate_url($embed_data['url'])) {
        // Replace with error message.
        $error = $dom->createElement('div');
        $error->setAttribute('class', 'secure-embed-error');
        $error->textContent = $this->t('This embed URL is not allowed.');
        $token->parentNode->replaceChild($error, $token);
        continue;
      }

      // Build the iframe element.
      $iframe_html = $this->buildIframe($embed_data, $config);

      // Create a new DOM fragment for the iframe.
      $fragment = $dom->createDocumentFragment();
      $fragment->appendXML($iframe_html);

      // Replace the token with the iframe.
      $token->parentNode->replaceChild($fragment, $token);
    }

    $result->setProcessedText(Html::serialize($dom));
    $result->addAttachments(['library' => ['secure_embed/secure_embed.frontend']]);

    return $result;
  }

  /**
   * Build the iframe HTML.
   *
   * @param array $embed_data
   *   The embed data.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   *
   * @return string
   *   The iframe HTML.
   */
  protected function buildIframe(array $embed_data, $config) {
    $url = Html::escape($embed_data['url']);
    $title = Html::escape($embed_data['title'] ?? '');
    $responsive = !empty($embed_data['responsive']);
    $aspect_ratio = Html::escape($embed_data['aspect_ratio'] ?? '16:9');
    $width = Html::escape($embed_data['width'] ?? '100%');
    $height = Html::escape($embed_data['height'] ?? '400');
    $custom_class = Html::escape($embed_data['custom_class'] ?? '');
    $lazy_loading = !empty($embed_data['lazy_loading']);

    // Build wrapper classes.
    $wrapper_classes = ['secure-embed'];
    if ($responsive) {
      $wrapper_classes[] = 'secure-embed--responsive';
      $wrapper_classes[] = 'secure-embed--ratio-' . str_replace(':', '-', $aspect_ratio);
    }
    if (!empty($custom_class)) {
      $wrapper_classes[] = $custom_class;
    }

    // Build iframe attributes.
    $iframe_attrs = [
      'src' => $url,
      'frameborder' => '0',
    ];

    if (!empty($title)) {
      $iframe_attrs['title'] = $title;
    }

    // Add loading attribute.
    if ($lazy_loading) {
      $iframe_attrs['loading'] = 'lazy';
    }

    // Add sandbox attributes if enabled.
    if ($config->get('enable_sandbox')) {
      $sandbox_attrs = $config->get('sandbox_attributes') ?? [];
      if (!empty($sandbox_attrs)) {
        $iframe_attrs['sandbox'] = implode(' ', $sandbox_attrs);
      }
    }

    // Add allow attributes.
    $allow_attrs = $config->get('allow_attributes') ?? [];
    if (!empty($allow_attrs)) {
      $iframe_attrs['allow'] = implode('; ', $allow_attrs);
    }

    // Add allowfullscreen if fullscreen is in allow attributes.
    if (in_array('fullscreen', $allow_attrs)) {
      $iframe_attrs['allowfullscreen'] = 'allowfullscreen';
    }

    // Set dimensions for non-responsive.
    if (!$responsive) {
      $iframe_attrs['width'] = $width;
      $iframe_attrs['height'] = $height;
    }

    // Build iframe attributes string.
    $attrs_string = '';
    foreach ($iframe_attrs as $key => $value) {
      $attrs_string .= ' ' . $key . '="' . $value . '"';
    }

    // Build the HTML.
    $wrapper_style = '';
    if (!$responsive) {
      $wrapper_style = ' style="width: ' . $width . ';"';
    }

    $html = '<div class="' . implode(' ', $wrapper_classes) . '"' . $wrapper_style . '>';
    $html .= '<iframe' . $attrs_string . '></iframe>';
    $html .= '</div>';

    return $html;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Use the Secure Embed button in the editor toolbar to embed external content like videos and maps. Only approved domains are allowed for security.');
    }
    return $this->t('External embeds are processed securely with domain whitelisting.');
  }

}
