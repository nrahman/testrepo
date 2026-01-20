<?php

namespace Drupal\secure_embed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\secure_embed\Service\SecureEmbedManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Secure Embed settings.
 */
class SecureEmbedSettingsForm extends ConfigFormBase {

  /**
   * The secure embed manager.
   *
   * @var \Drupal\secure_embed\Service\SecureEmbedManager
   */
  protected $embedManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->embedManager = $container->get('secure_embed.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'secure_embed_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['secure_embed.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('secure_embed.settings');

    $form['domain_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Allowed Domains'),
      '#open' => TRUE,
      '#description' => $this->t('Only URLs from these domains will be allowed for embedding. Use wildcard (*) for subdomains, e.g., "*.youtube.com".'),
    ];

    $allowed_domains = $config->get('allowed_domains') ?? '';
    $form['domain_settings']['allowed_domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed Domains'),
      '#default_value' => $allowed_domains,
      '#description' => $this->t('Enter one domain per line. Examples:<br>youtube.com<br>*.youtube.com<br>vimeo.com'),
      '#rows' => 15,
    ];

    $form['default_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Default Settings'),
      '#open' => TRUE,
    ];

    $form['default_settings']['default_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Width'),
      '#default_value' => $config->get('default_width') ?? '100%',
      '#description' => $this->t('Default width for embeds (e.g., 100%, 640, 640px).'),
      '#size' => 20,
    ];

    $form['default_settings']['default_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Height'),
      '#default_value' => $config->get('default_height') ?? '400',
      '#description' => $this->t('Default height for embeds (e.g., 400, 400px, 50vh).'),
      '#size' => 20,
    ];

    $form['default_settings']['default_aspect_ratio'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Aspect Ratio'),
      '#default_value' => $config->get('default_aspect_ratio') ?? '16:9',
      '#options' => [
        '16:9' => '16:9 (Widescreen)',
        '4:3' => '4:3 (Standard)',
        '1:1' => '1:1 (Square)',
        '21:9' => '21:9 (Ultra-wide)',
        '9:16' => '9:16 (Vertical/Mobile)',
        'custom' => 'Custom (Use width/height)',
      ],
      '#description' => $this->t('Default aspect ratio for responsive embeds.'),
    ];

    $form['default_settings']['responsive_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive by default'),
      '#default_value' => $config->get('responsive_default') ?? TRUE,
      '#description' => $this->t('Make embeds responsive by default (maintains aspect ratio).'),
    ];

    $form['default_settings']['lazy_loading_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy loading by default'),
      '#default_value' => $config->get('lazy_loading_default') ?? TRUE,
      '#description' => $this->t('Enable lazy loading for better performance.'),
    ];

    $form['security_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Security Settings'),
      '#open' => FALSE,
    ];

    $form['security_settings']['enable_sandbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable iframe sandbox'),
      '#default_value' => $config->get('enable_sandbox') ?? TRUE,
      '#description' => $this->t('Apply sandbox restrictions to iframes for additional security. Disable only if embeds are not functioning correctly.'),
    ];

    $sandbox_options = [
      'allow-scripts' => $this->t('Allow scripts'),
      'allow-same-origin' => $this->t('Allow same origin'),
      'allow-popups' => $this->t('Allow popups'),
      'allow-forms' => $this->t('Allow forms'),
      'allow-presentation' => $this->t('Allow presentation'),
      'allow-modals' => $this->t('Allow modals'),
      'allow-pointer-lock' => $this->t('Allow pointer lock'),
      'allow-top-navigation' => $this->t('Allow top navigation'),
    ];

    $form['security_settings']['sandbox_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sandbox Permissions'),
      '#options' => $sandbox_options,
      '#default_value' => $config->get('sandbox_attributes') ?? ['allow-scripts', 'allow-same-origin', 'allow-popups', 'allow-presentation'],
      '#description' => $this->t('Select which sandbox permissions to grant. More permissions = less security.'),
      '#states' => [
        'visible' => [
          ':input[name="enable_sandbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $allow_options = [
      'accelerometer' => $this->t('Accelerometer'),
      'autoplay' => $this->t('Autoplay'),
      'camera' => $this->t('Camera'),
      'clipboard-read' => $this->t('Clipboard read'),
      'clipboard-write' => $this->t('Clipboard write'),
      'encrypted-media' => $this->t('Encrypted media'),
      'fullscreen' => $this->t('Fullscreen'),
      'geolocation' => $this->t('Geolocation'),
      'gyroscope' => $this->t('Gyroscope'),
      'microphone' => $this->t('Microphone'),
      'midi' => $this->t('MIDI'),
      'payment' => $this->t('Payment'),
      'picture-in-picture' => $this->t('Picture-in-picture'),
      'web-share' => $this->t('Web share'),
    ];

    $form['security_settings']['allow_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allow Permissions Policy'),
      '#options' => $allow_options,
      '#default_value' => $config->get('allow_attributes') ?? ['accelerometer', 'autoplay', 'clipboard-write', 'encrypted-media', 'gyroscope', 'picture-in-picture', 'web-share'],
      '#description' => $this->t('Select which permissions to allow in the iframe "allow" attribute.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate domains.
    $domains_text = $form_state->getValue('allowed_domains');
    $domains = array_filter(array_map('trim', explode("\n", $domains_text)));

    foreach ($domains as $domain) {
      // Skip wildcards.
      if (strpos($domain, '*.') === 0) {
        $domain = substr($domain, 2);
      }

      // Basic domain validation.
      if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]*[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $domain) && !preg_match('/^[a-zA-Z0-9]+\.[a-zA-Z]{2,}$/', $domain)) {
        $form_state->setErrorByName('allowed_domains', $this->t('Invalid domain format: @domain', ['@domain' => $domain]));
      }
    }

    // Validate width/height.
    $width = $form_state->getValue('default_width');
    if (!preg_match('/^\d+(%|px|em|rem|vw)?$/', $width)) {
      $form_state->setErrorByName('default_width', $this->t('Invalid width format.'));
    }

    $height = $form_state->getValue('default_height');
    if (!preg_match('/^\d+(%|px|em|rem|vh)?$/', $height)) {
      $form_state->setErrorByName('default_height', $this->t('Invalid height format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Normalize domains text (one per line, trimmed).
    $domains_text = $form_state->getValue('allowed_domains');
    $domains = array_filter(array_map('trim', explode("\n", $domains_text)));
    $normalized_domains = implode("\n", $domains);

    // Process checkboxes to get only selected values.
    $sandbox_attributes = array_values(array_filter($form_state->getValue('sandbox_attributes')));
    $allow_attributes = array_values(array_filter($form_state->getValue('allow_attributes')));

    $this->config('secure_embed.settings')
      ->set('allowed_domains', $normalized_domains)
      ->set('default_width', $form_state->getValue('default_width'))
      ->set('default_height', $form_state->getValue('default_height'))
      ->set('default_aspect_ratio', $form_state->getValue('default_aspect_ratio'))
      ->set('responsive_default', (bool) $form_state->getValue('responsive_default'))
      ->set('lazy_loading_default', (bool) $form_state->getValue('lazy_loading_default'))
      ->set('enable_sandbox', (bool) $form_state->getValue('enable_sandbox'))
      ->set('sandbox_attributes', $sandbox_attributes)
      ->set('allow_attributes', $allow_attributes)
      ->save();

    // Clear the domain pattern cache.
    $this->embedManager->clearCache();

    parent::submitForm($form, $form_state);
  }

}
