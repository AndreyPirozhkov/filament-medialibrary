# Filament Media Library

Медиа библиотека для Filament 5 с визуальным файловым менеджером на основе `spatie/laravel-medialibrary`.

## Установка

### Через Composer (локальный пакет)

Добавьте в `composer.json` вашего проекта:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/andrey/filament-medialibrary"
        }
    ],
    "require": {
        "andrey/filament-medialibrary": "*"
    }
}
```

Затем выполните:

```bash
composer require andrey/filament-medialibrary
```

### Публикация конфигурации

```bash
php artisan vendor:publish --tag=filament-medialibrary-config
```

### Запуск миграций

Миграции будут автоматически загружены при установке пакета. Выполните:

```bash
php artisan migrate
```

## Использование

После установки пакета страница "Медиа" автоматически появится в навигации Filament панели.

## Конфигурация

Отредактируйте `config/filament-medialibrary.php` для настройки:

```php
return [
    'navigation_label' => 'Медиа',
    'navigation_icon' => 'heroicon-o-photo',
    'navigation_sort' => 10,
    'folders_table' => 'media_folders',
    'items_table' => 'media_items',
];
```

## Возможности

- ✅ Просмотр всех загруженных файлов
- ✅ Сортировка по дате, имени, расширению, размеру
- ✅ Поиск по названию файла
- ✅ Загрузка файлов через drag-and-drop
- ✅ Загрузка папок через drag-and-drop
- ✅ Создание папок в файловой структуре
- ✅ Удаление файлов и папок
- ✅ Навигация по папкам

## Структура пакета

```
packages/andrey/filament-medialibrary/
├── src/
│   ├── Filament/
│   │   └── Pages/
│   │       └── MediaLibrary.php
│   ├── Models/
│   │   ├── MediaFolder.php
│   │   └── MediaItem.php
│   └── FilamentMediaLibraryServiceProvider.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000002_create_media_folders_table.php
│       └── 2024_01_01_000003_create_media_items_table.php
├── resources/
│   └── views/
│       └── filament/
│           └── pages/
│               └── media-library.blade.php
├── config/
│   └── filament-medialibrary.php
└── composer.json
```

## Требования

- PHP ^8.2
- Laravel ^12.0
- Filament ^5.0
- spatie/laravel-medialibrary ^11.0

## Лицензия

MIT
