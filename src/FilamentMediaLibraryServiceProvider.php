<?php

namespace AP\FilamentMediaLibrary;

use AP\FilamentMediaLibrary\Filament\Pages\MediaLibrary;
use Illuminate\Support\ServiceProvider;

class FilamentMediaLibraryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-media-library');
        
        $this->publishes([
            __DIR__ . '/../config/filament-medialibrary.php' => config_path('filament-medialibrary.php'),
        ], 'filament-medialibrary-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-medialibrary.php',
            'filament-medialibrary'
        );
    }
}
