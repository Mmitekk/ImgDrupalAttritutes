<?php

namespace Drupal\img_attr_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Image Attributes settings form in Russian and English.
 */
final class SettingsForm extends ConfigFormBase {

  /** {@inheritdoc} */
  public function getFormId(): string {
    return 'img_attr_module_settings';
  }

  /** {@inheritdoc} */
  protected function getEditableConfigNames(): array {
    return ['img_attr_module.settings'];
  }

  /** {@inheritdoc} */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $lang = 'ru'): array {
    $english = $lang === 'en';
    $config = $this->config('img_attr_module.settings');
    $form['intro'] = ['#type' => 'item', '#markup' => $english
      ? $this->t('Adds missing width and height attributes to img elements in the final HTML response. Values reserve the image aspect ratio; CSS remains responsible for responsive display.')
      : $this->t('Добавляет недостающие width и height элементам img в итоговом HTML-ответе. Атрибуты сохраняют пропорции и резервируют место; адаптивное отображение по-прежнему задаёт CSS.')];
    $form['enabled'] = ['#type' => 'checkbox', '#title' => $english ? $this->t('Enable processing') : $this->t('Включить обработку'), '#default_value' => $config->get('enabled')];
    $form['process_admin'] = ['#type' => 'checkbox', '#title' => $english ? $this->t('Process administration pages') : $this->t('Обрабатывать страницы администрирования'), '#default_value' => $config->get('process_admin')];
    $form['add_width'] = ['#type' => 'checkbox', '#title' => $english ? $this->t('Add missing width') : $this->t('Добавлять отсутствующий width'), '#default_value' => $config->get('add_width')];
    $form['add_height'] = ['#type' => 'checkbox', '#title' => $english ? $this->t('Add missing height') : $this->t('Добавлять отсутствующий height'), '#default_value' => $config->get('add_height')];
    $form['use_css_dimensions'] = ['#type' => 'checkbox', '#title' => $english ? $this->t('Use numeric dimensions from inline style') : $this->t('Использовать числовые размеры из style'), '#description' => $english ? $this->t('For example, style="width: 320px". Percentages and viewport units are ignored.') : $this->t('Например, style="width: 320px". Проценты и viewport-единицы пропускаются.'), '#default_value' => $config->get('use_css_dimensions')];
    $form['use_file_dimensions'] = ['#type' => 'checkbox', '#title' => $english ? $this->t('Read dimensions from local image files') : $this->t('Читать размеры локальных файлов изображений'), '#default_value' => $config->get('use_file_dimensions')];
    $form['use_svg_viewbox'] = ['#type' => 'checkbox', '#title' => $english ? $this->t('Use SVG viewBox when dimensions are absent') : $this->t('Использовать SVG viewBox, если размеры отсутствуют'), '#default_value' => $config->get('use_svg_viewbox')];
    $form['skip_classes'] = ['#type' => 'textfield', '#title' => $english ? $this->t('Skip images with these classes') : $this->t('Пропускать изображения с классами'), '#description' => $english ? $this->t('Space-separated CSS class names.') : $this->t('Имена CSS-классов через пробел.'), '#default_value' => $config->get('skip_classes')];
    return parent::buildForm($form, $form_state);
  }

  /** {@inheritdoc} */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('img_attr_module.settings')
      ->set('enabled', (bool) $form_state->getValue('enabled'))
      ->set('process_admin', (bool) $form_state->getValue('process_admin'))
      ->set('add_width', (bool) $form_state->getValue('add_width'))
      ->set('add_height', (bool) $form_state->getValue('add_height'))
      ->set('use_css_dimensions', (bool) $form_state->getValue('use_css_dimensions'))
      ->set('use_file_dimensions', (bool) $form_state->getValue('use_file_dimensions'))
      ->set('use_svg_viewbox', (bool) $form_state->getValue('use_svg_viewbox'))
      ->set('skip_classes', trim((string) $form_state->getValue('skip_classes')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
