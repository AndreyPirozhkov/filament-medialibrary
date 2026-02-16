# Инструкция по установке пакета

## Для разработки (локальный пакет)

Пакет уже настроен в основном проекте через автозагрузку PSR-4.

## Для использования в других проектах

### Вариант 1: Локальный путь

1. Скопируйте папку `packages/andrey/filament-medialibrary` в ваш проект
2. Добавьте в `composer.json`:

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

3. Выполните:
```bash
composer require andrey/filament-medialibrary
```

### Вариант 2: Через Git репозиторий

Если пакет размещен в Git репозитории:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-username/filament-medialibrary"
        }
    ],
    "require": {
        "andrey/filament-medialibrary": "dev-main"
    }
}
```

### После установки

1. Опубликуйте конфигурацию (опционально):
```bash
php artisan vendor:publish --tag=filament-medialibrary-config
```

2. Запустите миграции:
```bash
php artisan migrate
```

3. Убедитесь, что Filament панель настроена для обнаружения страниц пакета в `AdminPanelProvider`:

```php
->discoverPages(
    in: base_path('packages/andrey/filament-medialibrary/src/Filament/Pages'), 
    for: 'Andrey\\FilamentMediaLibrary\\Filament\\Pages'
)
```

Или пакет автоматически зарегистрирует страницу через ServiceProvider.
