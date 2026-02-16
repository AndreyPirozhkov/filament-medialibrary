<?php

namespace AP\FilamentMediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MediaItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'media_items';
    
    protected $fillable = [];

    public $timestamps = false;
}
