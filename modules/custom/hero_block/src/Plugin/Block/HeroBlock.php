<?php

namespace Drupal\hero_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

/**
 * Provides a Hero Block with 3 layout options.
 *
 * @Block(
 *   id = "hero_block",
 *   admin_label = @Translation("Hero Block"),
 *   category = @Translation("Custom"),
 * )
 */
class HeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new HeroBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'hero_layout' => 'full_width_overlay',
      'hero_heading' => '',
      'hero_subheading' => '',
      'hero_image' => [],
      'hero_button_1_title' => '',
      'hero_button_1_url' => '',
      'hero_button_2_title' => '',
      'hero_button_2_url' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Layout selection.
    $form['hero_layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#description' => $this->t('Select the hero layout style.'),
      '#options' => [
        'full_width_overlay' => $this->t('Full Width Overlay'),
        'text_left_image_right' => $this->t('Text Left / Image Right'),
        'image_left_text_right' => $this->t('Image Left / Text Right'),
      ],
      '#default_value' => $config['hero_layout'],
      '#required' => TRUE,
    ];

    // Heading.
    $form['hero_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
      '#description' => $this->t('The main heading text for the hero.'),
      '#default_value' => $config['hero_heading'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    // Subheading.
    $form['hero_subheading'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Subheading'),
      '#description' => $this->t('The subheading or description text.'),
      '#default_value' => $config['hero_subheading'],
      '#rows' => 3,
    ];

    // Hero Image.
    $form['hero_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Hero Image'),
      '#description' => $this->t('Upload the hero background/feature image. Recommended size: 1920x1080 for full-width, 800x600 for split layouts.'),
      '#upload_location' => 'public://hero_block/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif webp'],
        'file_validate_size' => [10 * 1024 * 1024], // 10MB max
      ],
      '#default_value' => $config['hero_image'],
      '#required' => TRUE,
    ];

    // Image Alt Text.
    $form['hero_image_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image Alt Text'),
      '#description' => $this->t('Alternative text for accessibility.'),
      '#default_value' => $config['hero_image_alt'] ?? '',
      '#maxlength' => 255,
    ];

    // Button 1 fieldset.
    $form['button_1'] = [
      '#type' => 'details',
      '#title' => $this->t('Primary Button'),
      '#open' => TRUE,
    ];

    $form['button_1']['hero_button_1_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#description' => $this->t('The text displayed on the primary button.'),
      '#default_value' => $config['hero_button_1_title'],
      '#maxlength' => 100,
    ];

    $form['button_1']['hero_button_1_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button URL'),
      '#description' => $this->t('The URL for the primary button. Can be internal (/node/1) or external (https://example.com).'),
      '#default_value' => $config['hero_button_1_url'],
      '#maxlength' => 255,
    ];

    // Button 2 fieldset.
    $form['button_2'] = [
      '#type' => 'details',
      '#title' => $this->t('Secondary Button'),
      '#open' => TRUE,
    ];

    $form['button_2']['hero_button_2_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#description' => $this->t('The text displayed on the secondary button.'),
      '#default_value' => $config['hero_button_2_title'],
      '#maxlength' => 100,
    ];

    $form['button_2']['hero_button_2_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button URL'),
      '#description' => $this->t('The URL for the secondary button. Can be internal (/node/1) or external (https://example.com).'),
      '#default_value' => $config['hero_button_2_url'],
      '#maxlength' => 255,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);

    // Validate button URLs if provided.
    $button_1_url = $form_state->getValue(['button_1', 'hero_button_1_url']);
    $button_1_title = $form_state->getValue(['button_1', 'hero_button_1_title']);

    if (!empty($button_1_url) && empty($button_1_title)) {
      $form_state->setErrorByName('button_1][hero_button_1_title', $this->t('Please provide a title for the primary button.'));
    }

    if (!empty($button_1_title) && empty($button_1_url)) {
      $form_state->setErrorByName('button_1][hero_button_1_url', $this->t('Please provide a URL for the primary button.'));
    }

    $button_2_url = $form_state->getValue(['button_2', 'hero_button_2_url']);
    $button_2_title = $form_state->getValue(['button_2', 'hero_button_2_title']);

    if (!empty($button_2_url) && empty($button_2_title)) {
      $form_state->setErrorByName('button_2][hero_button_2_title', $this->t('Please provide a title for the secondary button.'));
    }

    if (!empty($button_2_title) && empty($button_2_url)) {
      $form_state->setErrorByName('button_2][hero_button_2_url', $this->t('Please provide a URL for the secondary button.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['hero_layout'] = $form_state->getValue('hero_layout');
    $this->configuration['hero_heading'] = $form_state->getValue('hero_heading');
    $this->configuration['hero_subheading'] = $form_state->getValue('hero_subheading');
    $this->configuration['hero_image'] = $form_state->getValue('hero_image');
    $this->configuration['hero_image_alt'] = $form_state->getValue('hero_image_alt');
    $this->configuration['hero_button_1_title'] = $form_state->getValue(['button_1', 'hero_button_1_title']);
    $this->configuration['hero_button_1_url'] = $form_state->getValue(['button_1', 'hero_button_1_url']);
    $this->configuration['hero_button_2_title'] = $form_state->getValue(['button_2', 'hero_button_2_title']);
    $this->configuration['hero_button_2_url'] = $form_state->getValue(['button_2', 'hero_button_2_url']);

    // Make the uploaded file permanent.
    $image = $form_state->getValue('hero_image');
    if (!empty($image[0])) {
      $file = File::load($image[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
        // Add file usage to prevent deletion.
        \Drupal::service('file.usage')->add($file, 'hero_block', 'block', $this->getPluginId());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Get the image URL.
    $image_url = '';
    if (!empty($config['hero_image'][0])) {
      $file = File::load($config['hero_image'][0]);
      if ($file) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    // Prepare button data.
    $button_1 = NULL;
    if (!empty($config['hero_button_1_title']) && !empty($config['hero_button_1_url'])) {
      $button_1 = [
        'title' => $config['hero_button_1_title'],
        'url' => $config['hero_button_1_url'],
      ];
    }

    $button_2 = NULL;
    if (!empty($config['hero_button_2_title']) && !empty($config['hero_button_2_url'])) {
      $button_2 = [
        'title' => $config['hero_button_2_title'],
        'url' => $config['hero_button_2_url'],
      ];
    }

    // Determine layout class.
    $layout_classes = [
      'full_width_overlay' => 'hero--full-width-overlay',
      'text_left_image_right' => 'hero--text-left-image-right',
      'image_left_text_right' => 'hero--image-left-text-right',
    ];
    $layout_class = $layout_classes[$config['hero_layout']] ?? 'hero--full-width-overlay';

    return [
      '#theme' => 'hero_block',
      '#hero_layout' => $config['hero_layout'],
      '#hero_layout_class' => $layout_class,
      '#hero_heading' => $config['hero_heading'],
      '#hero_subheading' => $config['hero_subheading'],
      '#hero_image_url' => $image_url,
      '#hero_image_alt' => $config['hero_image_alt'] ?? '',
      '#hero_button_1' => $button_1,
      '#hero_button_2' => $button_2,
      '#attached' => [
        'library' => [
          'hero_block/hero_block',
        ],
      ],
    ];
  }

}
