<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            <div class="flex items-center gap-2 flex-wrap">
                @if($currentFolder)
                    <x-filament::button 
                        icon="heroicon-o-arrow-left" 
                        wire:click="goUp"
                        size="sm"
                        color="gray"
                    >
                        Назад
                    </x-filament::button>
                @endif
                
                <x-filament::button 
                    icon="heroicon-o-folder-plus" 
                    wire:click="$set('showCreateFolderModal', true)"
                    size="sm"
                >
                    Создать папку
                </x-filament::button>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <x-filament::input.wrapper>
                    <x-filament::input 
                        type="search" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Поиск по названию..."
                    />
                </x-filament::input.wrapper>

                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="sortBy">
                        <option value="created_at">Дата</option>
                        <option value="file_name">Имя</option>
                        <option value="mime_type">Расширение</option>
                        <option value="size">Размер</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <x-filament::button 
                    icon="{{ $sortDirection === 'asc' ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down' }}"
                    wire:click="$set('sortDirection', '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}')"
                    size="sm"
                    color="gray"
                />
            </div>
        </div>

        <!-- Breadcrumbs -->
        @if($currentFolder)
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <a wire:click="goUp" class="hover:text-gray-900 cursor-pointer">Корень</a>
                @if($currentFolder)
                    <span>/</span>
                    <span class="text-gray-900">{{ $currentFolder->name }}</span>
                @endif
            </div>
        @endif

        <!-- Drop Zone -->
        <div 
            x-data="{ 
                isDragging: false,
                handleDrop(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.isDragging = false;
                    const items = e.dataTransfer.items;
                    const files = [];
                    
                    const processItems = async (items) => {
                        const filePromises = [];
                        
                        for (let i = 0; i < items.length; i++) {
                            const item = items[i];
                            
                            if (item.kind === 'file') {
                                const entry = item.webkitGetAsEntry ? item.webkitGetAsEntry() : null;
                                
                                if (entry && entry.isDirectory) {
                                    // Handle directory
                                    const dirFiles = await this.getDirectoryFiles(entry);
                                    filePromises.push(...dirFiles);
                                } else {
                                    // Handle file
                                    const file = item.getAsFile();
                                    filePromises.push(this.processFile(file));
                                }
                            }
                        }
                        
                        const fileData = await Promise.all(filePromises);
                        @this.call('handleFilesUploaded', fileData);
                    };
                    
                    processItems(items);
                },
                async getDirectoryFiles(directoryEntry) {
                    const files = [];
                    const reader = directoryEntry.createReader();
                    
                    const readEntries = async () => {
                        const entries = await new Promise((resolve) => {
                            reader.readEntries(resolve);
                        });
                        
                        for (const entry of entries) {
                            if (entry.isFile) {
                                const file = await new Promise((resolve) => {
                                    entry.file(resolve);
                                });
                                files.push(await this.processFile(file));
                            } else if (entry.isDirectory) {
                                const dirFiles = await this.getDirectoryFiles(entry);
                                files.push(...dirFiles);
                            }
                        }
                        
                        if (entries.length > 0) {
                            const moreEntries = await readEntries();
                            files.push(...moreEntries);
                        }
                    };
                    
                    await readEntries();
                    return files;
                },
                async processFile(file) {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            resolve({
                                name: file.name,
                                type: file.type,
                                size: file.size,
                                content: e.target.result.split(',')[1]
                            });
                        };
                        reader.readAsDataURL(file);
                    });
                },
                handleFileSelect(e) {
                    const files = Array.from(e.target.files);
                    const filePromises = files.map(file => this.processFile(file));
                    Promise.all(filePromises).then(fileData => {
                        @this.call('handleFilesUploaded', fileData);
                    });
                }
            }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
            class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
            :class="isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300'"
        >
            <div class="space-y-2">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-sm text-gray-600">
                    Перетащите файлы или папки сюда или 
                    <label class="text-primary-600 cursor-pointer hover:underline">
                        выберите файлы
                        <input 
                            type="file" 
                            multiple 
                            class="hidden"
                            x-on:change="handleFileSelect($event)"
                        />
                    </label>
                </p>
            </div>
        </div>

        <!-- Folders Grid -->
        @if($folders->count() > 0)
            <div>
                <h3 class="text-lg font-semibold mb-4">Папки</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
                    @foreach($folders as $folder)
                        <div 
                            class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors group"
                            wire:click="enterFolder({{ $folder->id }})"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <span class="text-sm text-center truncate w-full">{{ $folder->name }}</span>
                                <button 
                                    wire:click.stop="deleteFolder({{ $folder->id }})"
                                    class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-xs"
                                    onclick="return confirm('Удалить папку?')"
                                >
                                    Удалить
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Files Grid -->
        @if($files->count() > 0)
            <div>
                <h3 class="text-lg font-semibold mb-4">Файлы ({{ $files->count() }})</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
                    @foreach($files as $file)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors group relative">
                            <div class="flex flex-col items-center gap-2">
                                @if(in_array($this->getFileExtension($file->file_name), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
                                    <img 
                                        src="{{ $this->getFileUrl($file->id) }}" 
                                        alt="{{ $file->name }}"
                                        class="w-full h-24 object-cover rounded"
                                    />
                                @else
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                @endif
                                <span class="text-xs text-center truncate w-full" title="{{ $file->name }}">
                                    {{ $file->name }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $this->formatFileSize($file->size) }}
                                </span>
                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a 
                                        href="{{ $this->getFileUrl($file->id) }}" 
                                        target="_blank"
                                        class="text-blue-500 hover:text-blue-700 text-xs"
                                    >
                                        Открыть
                                    </a>
                                    <button 
                                        wire:click="deleteFile({{ $file->id }})"
                                        class="text-red-500 hover:text-red-700 text-xs"
                                        onclick="return confirm('Удалить файл?')"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif(!$isUploading && $search === '')
            <div class="text-center py-12 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p>Нет файлов. Загрузите файлы, перетащив их в область выше.</p>
            </div>
        @endif

        @if($isUploading)
            <div class="text-center py-4">
                <x-filament::loading-indicator class="w-8 h-8 mx-auto" />
                <p class="mt-2 text-sm text-gray-600">Загрузка файлов...</p>
            </div>
        @endif
    </div>

    <!-- Create Folder Modal -->
    <x-filament::modal 
        id="create-folder-modal"
        wire:model="showCreateFolderModal"
    >
        <x-slot name="heading">
            Создать папку
        </x-slot>

        <x-slot name="description">
            Введите название новой папки
        </x-slot>

        <x-filament::input.wrapper>
            <x-filament::input 
                wire:model="newFolderName"
                placeholder="Название папки"
                autofocus
            />
        </x-filament::input.wrapper>

        <x-slot name="footer">
            <x-filament::button 
                wire:click="createFolder"
                color="primary"
            >
                Создать
            </x-filament::button>
            <x-filament::button 
                wire:click="$set('showCreateFolderModal', false)"
                color="gray"
            >
                Отмена
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
