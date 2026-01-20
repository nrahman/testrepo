<?php

namespace Drupal\secure_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\secure_embed\Service\SecureEmbedManager;
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
   * The secure embed manager service.
   *
   * @var \Drupal\secure_embed\Service\SecureEmbedManager
   */
  protected $embedManager;

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
   * @param \Drupal\secure_embed\Service\SecureEmbedManager $embed_manager
   *   The secure embed manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SecureEmbedManager $embed_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->embedManager = $embed_manager;
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
      $container->get('secure_embed.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Early return if no tokens present (fast path).
    if (strpos($text, 'secure-embed-token') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $tokens = $xpath->query('//div[contains(@class, "secure-embed-token")][@data-secure-embed]');

    if ($tokens->length === 0) {
      return $result;
    }

    $has_embeds = FALSE;

    foreach ($tokens as $token) {
      $encoded_data = $token->getAttribute('data-secure-embed');
      if (empty($encoded_data)) {
        continue;
      }

      // Decode and verify signature.
      $embed_data = $this->embedManager->decodeData($encoded_data);
      if (empty($embed_data)) {
        // Invalid or tampered token - show error.
        $this->replaceWithError($dom, $token, $this->t('Invalid or tampered embed token.'));
        continue;
      }

      if (empty($embed_data['url'])) {
        $this->replaceWithError($dom, $token, $this->t('Embed URL is missing.'));
        continue;
      }

      // Build render array using the service.
      $build = $this->embedManager->buildRenderArray($embed_data);

      // Render to HTML.
      $rendered_html = (string) $this->renderer->renderPlain($build);

      // Create a new DOM fragment for the rendered content.
      $fragment = $dom->createDocumentFragment();
      // Suppress warnings for HTML5 elements.
      @$fragment->appendXML($rendered_html);

      // Replace the token with the rendered embed.
      if ($fragment->hasChildNodes()) {
        $token->parentNode->replaceChild($fragment, $token);
        $has_embeds = TRUE;
      }
    }

    $result->setProcessedText(Html::serialize($dom));

    // Add library and cache metadata.
    if ($has_embeds) {
      $result->addAttachments(['library' => ['secure_embed/secure_embed.frontend']]);
      $result->addCacheTags(['config:secure_embed.settings']);
    }

    return $result;
  }

  /**
   * Replaces a token with an error message.
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $token
   *   The token element to replace.
   * @param string $message
   *   The error message.
   */
  protected function replaceWithError(\DOMDocument $dom, \DOMElement $token, $message) {
    $error = $dom->createElement('div');
    $error->setAttribute('class', 'secure-embed-error');
    $error->textContent = $message;
    $token->parentNode->replaceChild($error, $token);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Use the Secure Embed button in the editor toolbar to embed external content like videos and maps. Only approved domains with HTTPS are allowed for security.');
    }
    return $this->t('External embeds are processed securely with domain whitelisting and HMAC verification.');
  }

}
