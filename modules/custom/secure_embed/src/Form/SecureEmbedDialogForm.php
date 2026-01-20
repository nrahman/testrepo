<?php

namespace Drupal\secure_embed\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for inserting secure embeds via CKEditor.
 */
class SecureEmbedDialogForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'secure_embed_dialog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('secure_embed.settings');

    // Get existing embed data if editing.
    $embed_data = $form_state->get('embed_data') ?? [];

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'secure_embed/secure_embed.dialog';
    $form['#prefix'] = '<div id="secure-embed-dialog-form">';
    $form['#suffix'] = '</div>';

    // Status messages container.
    $form['status_messages'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'secure-embed-messages'],
    ];

    // URL Input.
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Embed URL'),
      '#required' => TRUE,
      '#default_value' => $embed_data['url'] ?? '',
      '#description' => $this->t('Enter the URL to embed (e.g., YouTube video URL, Google Map URL).'),
      '#attributes' => [
        'placeholder' => 'https://www.youtube.com/embed/VIDEO_ID',
      ],
    ];

    // Show allowed domains.
    $allowed_domains = $config->get('allowed_domains') ?? [];
    if (!empty($allowed_domains)) {
      $domains_display = array_slice($allowed_domains, 0, 10);
      $more = count($allowed_domains) > 10 ? $this->t('... and @count more', ['@count' => count($allowed_domains) - 10]) : '';
      $form['allowed_domains_info'] = [
        '#type' => 'details',
        '#title' => $this->t('Allowed Domains'),
        '#open' => FALSE,
        '#attributes' => ['class' => ['secure-embed-allowed-domains']],
      ];
      $form['allowed_domains_info']['list'] = [
        '#markup' => '<small>' . implode(', ', $domains_display) . ($more ? ' ' . $more : '') . '</small>',
      ];
    }

    // Title/Accessibility.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title (Accessibility)'),
      '#default_value' => $embed_data['title'] ?? '',
      '#description' => $this->t('Descriptive title for accessibility (screen readers).'),
      '#attributes' => [
        'placeholder' => 'Video: How to use our product',
      ],
    ];

    // Display options.
    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => TRUE,
    ];

    $form['display']['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive'),
      '#default_value' => $embed_data['responsive'] ?? $config->get('responsive_default') ?? TRUE,
      '#description' => $this->t('Maintain aspect ratio and fill container width.'),
    ];

    $form['display']['aspect_ratio'] = [
      '#type' => 'select',
      '#title' => $this->t('Aspect Ratio'),
      '#default_value' => $embed_data['aspect_ratio'] ?? $config->get('default_aspect_ratio') ?? '16:9',
      '#options' => [
        '16:9' => '16:9 (Widescreen Video)',
        '4:3' => '4:3 (Standard Video)',
        '1:1' => '1:1 (Square)',
        '21:9' => '21:9 (Ultra-wide)',
        '9:16' => '9:16 (Vertical/Mobile)',
      ],
      '#states' => [
        'visible' => [
          ':input[name="display[responsive]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['display']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $embed_data['width'] ?? $config->get('default_width') ?? '100%',
      '#size' => 10,
      '#description' => $this->t('e.g., 100%, 640, 640px'),
      '#states' => [
        'visible' => [
          ':input[name="display[responsive]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['display']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $embed_data['height'] ?? $config->get('default_height') ?? '400',
      '#size' => 10,
      '#description' => $this->t('e.g., 400, 400px, 50vh'),
      '#states' => [
        'visible' => [
          ':input[name="display[responsive]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['display']['custom_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS Class'),
      '#default_value' => $embed_data['custom_class'] ?? '',
      '#description' => $this->t('Optional CSS class for custom styling.'),
      '#attributes' => [
        'placeholder' => 'my-custom-embed',
      ],
    ];

    // Advanced options.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#open' => FALSE,
    ];

    $form['advanced']['lazy_loading'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy Loading'),
      '#default_value' => $embed_data['lazy_loading'] ?? $config->get('lazy_loading_default') ?? TRUE,
      '#description' => $this->t('Load embed only when visible (improves page performance).'),
    ];

    // Form actions.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert Embed'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
      ],
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::ajaxCancel',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');

    // Validate URL format.
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('url', $this->t('Please enter a valid URL.'));
      return;
    }

    // Validate against whitelist.
    if (!secure_embed_validate_url($url)) {
      $config = $this->configFactory->get('secure_embed.settings');
      $allowed_domains = $config->get('allowed_domains') ?? [];
      $form_state->setErrorByName('url', $this->t('This domain is not allowed. Allowed domains: @domains', [
        '@domains' => implode(', ', array_slice($allowed_domains, 0, 5)) . (count($allowed_domains) > 5 ? '...' : ''),
      ]));
      return;
    }

    // Validate width/height if not responsive.
    $display = $form_state->getValue('display');
    if (empty($display['responsive'])) {
      if (!preg_match('/^\d+(%|px|em|rem|vw)?$/', $display['width'])) {
        $form_state->setErrorByName('display][width', $this->t('Invalid width format.'));
      }
      if (!preg_match('/^\d+(%|px|em|rem|vh)?$/', $display['height'])) {
        $form_state->setErrorByName('display][height', $this->t('Invalid height format.'));
      }
    }

    // Validate custom class (alphanumeric, hyphens, underscores only).
    $custom_class = $display['custom_class'] ?? '';
    if (!empty($custom_class) && !preg_match('/^[a-zA-Z0-9\-_\s]+$/', $custom_class)) {
      $form_state->setErrorByName('display][custom_class', $this->t('Custom class can only contain letters, numbers, hyphens, and underscores.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submission is handled via AJAX.
  }

  /**
   * AJAX callback for form submission.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Check for errors.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new HtmlCommand('#secure-embed-messages', [
        '#theme' => 'status_messages',
        '#message_list' => $this->messenger()->all(),
        '#status_headings' => [
          'error' => $this->t('Error'),
        ],
      ]));
      $this->messenger()->deleteAll();
      return $response;
    }

    // Build embed data.
    $display = $form_state->getValue('display');
    $advanced = $form_state->getValue('advanced');

    $embed_data = [
      'url' => $form_state->getValue('url'),
      'title' => $form_state->getValue('title'),
      'responsive' => !empty($display['responsive']),
      'aspect_ratio' => $display['aspect_ratio'] ?? '16:9',
      'width' => $display['width'] ?? '100%',
      'height' => $display['height'] ?? '400',
      'custom_class' => $display['custom_class'] ?? '',
      'lazy_loading' => !empty($advanced['lazy_loading']),
    ];

    // Sanitize the data.
    $embed_data = secure_embed_sanitize_params($embed_data);

    // Encode for storage.
    $encoded_data = secure_embed_encode_data($embed_data);

    // Create the embed token for CKEditor.
    $embed_token = '<div class="secure-embed-token" data-secure-embed="' . $encoded_data . '">';
    $embed_token .= '[Secure Embed: ' . htmlspecialchars($embed_data['title'] ?: parse_url($embed_data['url'], PHP_URL_HOST)) . ']';
    $embed_token .= '</div>';

    // Return values to CKEditor.
    $values = [
      'embed_token' => $embed_token,
      'embed_data' => $embed_data,
    ];

    $response->addCommand(new EditorDialogSave($values));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * AJAX callback for cancel button.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
