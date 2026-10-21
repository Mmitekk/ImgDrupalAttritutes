<?php

namespace Drupal\img_attr_module\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Adds intrinsic dimensions to eligible img elements in HTML responses. */
final class ImageAttributeResponseSubscriber implements EventSubscriberInterface {

  public function __construct(private ConfigFactoryInterface $configFactory, private FileSystemInterface $fileSystem) {}

  /** {@inheritdoc} */
  public static function getSubscribedEvents(): array {
    // Run late so page renderers have already produced the final HTML.
    return [KernelEvents::RESPONSE => ['onResponse', -100]];
  }

  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    $config = $this->configFactory->get('img_attr_module.settings');
    $response = $event->getResponse();
    if (!$config->get('enabled') || (!$config->get('process_admin') && str_starts_with($event->getRequest()->getPathInfo(), '/admin/')) || stripos((string) $response->headers->get('Content-Type', ''), 'text/html') === FALSE || $response->isRedirection()) {
      return;
    }
    $html = $response->getContent();
    if (!is_string($html) || stripos($html, '<img') === FALSE) {
      return;
    }
    $dom = Html::load($html);
    $classes = preg_split('/\s+/', trim((string) $config->get('skip_classes')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach ($dom->getElementsByTagName('img') as $image) {
      $skip = FALSE;
      foreach ($classes as $class) {
        $image_classes = preg_split('/\s+/', trim($image->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (in_array($class, $image_classes, TRUE)) {
          $skip = TRUE;
          break;
        }
      }
      if ($skip) {
        continue;
      }
      $width = $this->positiveNumber($image->getAttribute('width'));
      $height = $this->positiveNumber($image->getAttribute('height'));
      if ($config->get('use_css_dimensions')) {
        $style = $image->getAttribute('style');
        $width ??= $this->styleDimension($style, 'width');
        $height ??= $this->styleDimension($style, 'height');
      }
      $intrinsic = $this->getIntrinsicDimensions($image->getAttribute('src'), (bool) $config->get('use_file_dimensions'), (bool) $config->get('use_svg_viewbox'));
      if ($width === NULL && $height === NULL && $intrinsic) {
        [$width, $height] = $intrinsic;
      }
      elseif ($intrinsic && $width !== NULL && $height === NULL) {
        $height = max(1, (int) round($width * $intrinsic[1] / $intrinsic[0]));
      }
      elseif ($intrinsic && $height !== NULL && $width === NULL) {
        $width = max(1, (int) round($height * $intrinsic[0] / $intrinsic[1]));
      }
      if ($config->get('add_width') && !$image->hasAttribute('width') && $width !== NULL) {
        $image->setAttribute('width', (string) $width);
      }
      if ($config->get('add_height') && !$image->hasAttribute('height') && $height !== NULL) {
        $image->setAttribute('height', (string) $height);
      }
    }
    $response->setContent(Html::serialize($dom));
  }

  private function positiveNumber(string $value): ?int {
    return preg_match('/^\s*(\d+)\s*$/', $value, $matches) && (int) $matches[1] > 0 ? (int) $matches[1] : NULL;
  }

  private function styleDimension(string $style, string $property): ?int {
    if (preg_match('/(?:^|;)\s*' . preg_quote($property, '/') . '\s*:\s*(\d+(?:\.\d+)?)\s*(?:px)?\s*(?:;|$)/i', $style, $matches)) {
      return (float) $matches[1] > 0 ? (int) round((float) $matches[1]) : NULL;
    }
    return NULL;
  }

  /** Gets intrinsic dimensions only from a local file, never by making HTTP requests. */
  private function getIntrinsicDimensions(string $src, bool $enabled, bool $svgViewBox): ?array {
    if (!$enabled || $src === '' || preg_match('@^(?:[a-z][a-z0-9+.-]*:|//|#|data:)@i', $src)) {
      return NULL;
    }
    $path = parse_url($src, PHP_URL_PATH);
    if (!$path) {
      return NULL;
    }
    $uri = NULL;
    foreach (['public://', 'private://'] as $scheme) {
      $relative = rawurldecode($path);
      // The standard public-files URL contains the webroot prefix; it is not
      // part of the public:// stream-wrapper URI.
      $relative = preg_replace('@^.*?/sites/[^/]+/files/@', '', $relative);
      $candidate = $scheme . ltrim($relative, '/');
      $real = $this->fileSystem->realpath($candidate);
      if ($real && is_file($real)) {
        $uri = $real;
        break;
      }
    }
    if (!$uri) {
      return NULL;
    }
    $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    if ($extension === 'svg') {
      if (!$svgViewBox) {
        return NULL;
      }
      $contents = @file_get_contents($uri);
      if (!is_string($contents) || !preg_match('/<svg\b[^>]*>/i', $contents, $tag)) {
        return NULL;
      }
      if (preg_match('/\bwidth=["\']\s*(\d+(?:\.\d+)?)(?:px)?["\']/i', $tag[0], $w) && preg_match('/\bheight=["\']\s*(\d+(?:\.\d+)?)(?:px)?["\']/i', $tag[0], $h)) {
        return [(int) round((float) $w[1]), (int) round((float) $h[1])];
      }
      if (preg_match('/\bviewBox=["\']\s*[-\d.]+[ ,]+[-\d.]+[ ,]+([\d.]+)[ ,]+([\d.]+)["\']/i', $tag[0], $view) && (float) $view[1] > 0 && (float) $view[2] > 0) {
        return [(int) round((float) $view[1]), (int) round((float) $view[2])];
      }
      return NULL;
    }
    $size = @getimagesize($uri);
    return $size && $size[0] > 0 && $size[1] > 0 ? [(int) $size[0], (int) $size[1]] : NULL;
  }

}
