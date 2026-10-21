# Image Attributes for Drupal

<p align="center"><a href="#русский">Русский</a> | <a href="#english">English</a></p>

<a id="русский"></a>
## Русский

Модуль автоматически добавляет отсутствующие HTML-атрибуты `width` и `height` элементам `<img>` в итоговом HTML-ответе Drupal. Он обрабатывает изображения из Twig, CKEditor, полей Media, адаптивной разметки и других источников. Существующие атрибуты не перезаписываются.

Размеры берутся в следующем порядке: уже заданные числовые HTML-атрибуты, числовые `px`-размеры из inline `style`, затем реальные размеры локального файла. Если задан только один размер и известны пропорции локального файла, второй рассчитывается. Для SVG используются `width`/`height` или `viewBox`. Внешние URL, `data:` URI, фрагменты и файлы без определяемых размеров пропускаются; сетевые запросы не выполняются.

Атрибуты отражают пропорции исходного изображения, а не вычисленный размер после CSS-каскада. Для адаптивности используйте, например, `max-width: 100%; height: auto`. Модуль меняет только итоговый ответ, не переписывает сохранённый HTML поля.

### Требования

- Drupal 9, 10 или 11
- PHP 8.1 или новее
- Локальные файлы изображений должны быть доступны Drupal через стандартные `public://` или `private://` файловые URL.

### Установка через Composer

Релизные теги доступны в GitHub. Добавьте репозиторий как VCS-источник и установите стабильную версию (не `dev-main`):

```sh
composer config repositories.img-drupal-attributes vcs https://github.com/Mmitekk/ImgDrupalAttritutes
composer require mmitekk/img-drupal-attributes:^1.0
```

В проекте Drupal с нестандартной структурой укажите путь назначения для типа `drupal-module` в корневом `composer.json`, например:

```json
{
  "extra": {
    "installer-paths": {
      "web/modules/contrib/{$name}": ["type:drupal-module"]
    }
  }
}
```

Для стандартного `drupal/recommended-project` обычно достаточно команды Composer выше. Включите модуль:

```sh
drush en img_attr_module -y
drush cr
```

Либо включите его на странице **Расширение** (`/admin/modules`).

### Настройка

Откройте **Конфигурация → Медиа → Image attributes** (`/admin/config/media/image-attributes`). Вкладки **Русский** и **English** показывают описание и настройки на соответствующем языке.

- Включение обработки и обработка страниц администрирования.
- Независимое добавление отсутствующего `width` и `height`.
- Использование числовых `px` размеров из inline `style`.
- Чтение intrinsic-размеров локальных файлов.
- Использование SVG `viewBox`, если нет числовых `width` и `height`.
- Список CSS-классов изображений, которые нужно пропускать.

После изменения настроек очистите кэш Drupal (`drush cr`), если конфигурационное кэширование проекта этого требует.

### Ограничения

- Стили из внешних CSS-файлов, media queries, вычисления JavaScript и итоговая геометрия контейнера недоступны на серверном этапе. Модуль не пытается угадывать их.
- Для `srcset` и responsive image атрибуты вычисляются по `src`, который браузер использует как резервный URL. Файлы `srcset` могут иметь другие пропорции.
- Для нестандартных публичных путей, не соответствующих `/sites/<site>/files/`, Drupal может не сопоставить URL с локальным файлом; значения из HTML/style всё равно учитываются.
- На больших страницах включайте модуль после оценки времени обработки: финальный HTML разбирается DOM-парсером.

### Разработка и релизы

Репозиторий оформлен как Composer-пакет Drupal-модуля. Стабильные версии публикуются тегами `1.x` и GitHub Releases; Composer устанавливает их как обычные стабильные версии.

---

<a id="english"></a>
## English

Image Attributes automatically adds missing HTML `width` and `height` attributes to `<img>` elements in Drupal's final HTML response. It covers images rendered by Twig, CKEditor, Media fields, responsive markup, and other sources. Existing attributes are never overwritten.

Dimensions are resolved from existing numeric HTML attributes, numeric `px` values in inline `style`, and then the intrinsic dimensions of a local file. If only one dimension is known, the other is calculated from the local file's aspect ratio. SVG dimensions come from numeric `width`/`height` or `viewBox`. Remote URLs, `data:` URIs, fragments, and files without readable dimensions are skipped; no network requests are made.

The attributes describe the source image's proportions, not the final dimensions after the CSS cascade. Keep responsive behavior in CSS, for example `max-width: 100%; height: auto`. The module changes the rendered response only; it does not rewrite stored field content.

### Requirements

- Drupal 9, 10, or 11
- PHP 8.1 or later
- Local image files accessible through Drupal's standard `public://` or `private://` file URLs.

### Install with Composer

Stable GitHub release tags are available through the repository's VCS metadata. Add the VCS repository and require a stable version (not `dev-main`):

```sh
composer config repositories.img-drupal-attributes vcs https://github.com/Mmitekk/ImgDrupalAttritutes
composer require mmitekk/img-drupal-attributes:^1.0
```

For a custom Drupal project layout, configure the destination for packages of type `drupal-module` in the root `composer.json`, for example:

```json
{
  "extra": {
    "installer-paths": {
      "web/modules/contrib/{$name}": ["type:drupal-module"]
    }
  }
}
```

For the standard `drupal/recommended-project`, the Composer commands above are normally sufficient. Enable the module:

```sh
drush en img_attr_module -y
drush cr
```

Or enable it at **Extend** (`/admin/modules`).

### Configuration

Go to **Configuration → Media → Image attributes** (`/admin/config/media/image-attributes`). The **Русский** and **English** tabs contain the localized description and settings.

- Enable processing and optionally include administration pages.
- Independently add a missing `width` and/or `height`.
- Read numeric `px` dimensions from inline `style`.
- Read intrinsic dimensions from local image files.
- Use an SVG `viewBox` if numeric `width` and `height` are absent.
- Exclude images by CSS class.

Clear Drupal caches (`drush cr`) after changing settings if required by the site's configuration cache.

### Limitations

- External stylesheets, media queries, JavaScript layout changes, and the final container geometry are not available at this server-side stage, so the module does not guess them.
- For `srcset` and responsive images, dimensions are inferred from `src`, the fallback URL. Candidates in `srcset` can have different aspect ratios.
- Non-standard public file URL paths outside `/sites/<site>/files/` may not map back to local files. Existing HTML/style values are still honored.
- The final HTML is parsed with DOM; evaluate response-processing cost on very large pages before enabling it site-wide.

### Development and releases

This repository is a Drupal module Composer package. Stable versions are published as `1.x` tags and GitHub Releases, and Composer installs them as normal stable versions.
