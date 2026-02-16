<?php

namespace Andrey\FilamentMediaLibrary\Filament\Pages;

use Andrey\FilamentMediaLibrary\Models\MediaFolder;
use Andrey\FilamentMediaLibrary\Models\MediaItem;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static string $view = 'filament-media-library::filament.pages.media-library';
    
    public static function getNavigationLabel(): string
    {
        return config('filament-medialibrary.navigation_label', 'Медиа');
    }
    
    public static function getNavigationIcon(): ?string
    {
        return config('filament-medialibrary.navigation_icon', 'heroicon-o-photo');
    }
    
    public static function getNavigationSort(): ?int
    {
        return config('filament-medialibrary.navigation_sort', 10);
    }

    public $files = [];
    public $folders = [];
    public $currentFolderId = null;
    public $currentFolder = null;
    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $selectedFiles = [];
    public $uploadedFiles = [];
    public $isUploading = false;
    public $showCreateFolderModal = false;
    public $newFolderName = '';

    public function mount(): void
    {
        $this->loadFiles();
        $this->loadFolders();
    }

    public function loadFiles(): void
    {
        $query = Media::query();

        if ($this->currentFolderId) {
            $query->whereRaw("JSON_EXTRACT(custom_properties, '$.folder_id') = ?", [$this->currentFolderId]);
        } else {
            $query->where(function($q) {
                $q->whereNull('custom_properties')
                  ->orWhereRaw("JSON_EXTRACT(custom_properties, '$.folder_id') IS NULL")
                  ->orWhereRaw("JSON_EXTRACT(custom_properties, '$.folder_id') = ''");
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('file_name', 'like', '%' . $this->search . '%');
            });
        }

        $this->files = $query->orderBy($this->sortBy, $this->sortDirection)->get();
    }

    public function loadFolders(): void
    {
        $query = MediaFolder::query()->where('parent_id', $this->currentFolderId);

        $this->folders = $query->orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->loadFiles();
    }

    public function updatedSortBy(): void
    {
        $this->loadFiles();
    }

    public function updatedSortDirection(): void
    {
        $this->loadFiles();
    }

    public function enterFolder($folderId): void
    {
        $this->currentFolderId = $folderId;
        $this->currentFolder = MediaFolder::find($folderId);
        $this->loadFiles();
        $this->loadFolders();
    }

    public function goUp(): void
    {
        if ($this->currentFolder && $this->currentFolder->parent_id) {
            $this->currentFolderId = $this->currentFolder->parent_id;
            $this->currentFolder = MediaFolder::find($this->currentFolderId);
        } else {
            $this->currentFolderId = null;
            $this->currentFolder = null;
        }
        $this->loadFiles();
        $this->loadFolders();
    }

    public function createFolder(): void
    {
        $this->validate([
            'newFolderName' => 'required|string|max:255',
        ]);

        MediaFolder::create([
            'name' => $this->newFolderName,
            'parent_id' => $this->currentFolderId,
            'path' => $this->currentFolder 
                ? $this->currentFolder->path . '/' . $this->newFolderName 
                : $this->newFolderName,
        ]);

        $this->showCreateFolderModal = false;
        $this->newFolderName = '';
        $this->loadFolders();
    }

    #[On('files-uploaded')]
    public function handleFilesUploaded($files): void
    {
        $this->isUploading = true;
        
        $mediaItem = MediaItem::firstOrCreate(['id' => 1]);
        
        foreach ($files as $file) {
            $tempPath = storage_path('app/temp/' . $file['name']);
            $directory = dirname($tempPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($tempPath, base64_decode($file['content']));
            
            $media = $mediaItem->addMedia($tempPath)
                ->withCustomProperties([
                    'folder_id' => $this->currentFolderId,
                ])
                ->toMediaCollection('default');
            
            unlink($tempPath);
        }

        $this->isUploading = false;
        $this->loadFiles();
    }

    public function deleteFile($fileId): void
    {
        $media = Media::find($fileId);
        if ($media) {
            $media->delete();
            $this->loadFiles();
        }
    }

    public function deleteFolder($folderId): void
    {
        $folder = MediaFolder::find($folderId);
        if ($folder) {
            // Move files to parent folder
            Media::whereRaw("JSON_EXTRACT(custom_properties, '$.folder_id') = ?", [$folderId])
                ->get()
                ->each(function($media) use ($folder) {
                    $props = $media->custom_properties ?? [];
                    $props['folder_id'] = $folder->parent_id;
                    $media->custom_properties = $props;
                    $media->save();
                });
            
            $folder->delete();
            $this->loadFolders();
        }
    }

    public function getFileUrl($mediaId): string
    {
        $media = Media::find($mediaId);
        if ($media) {
            return $media->getUrl();
        }
        return '';
    }

    public function getFileExtension($fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    public function formatFileSize($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
