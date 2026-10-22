<?php

function fakeImage(string $name = 'banner.jpg'): \App\Models\Media
{
    return \App\Models\Media::forceCreate([
        'disk' => 'public', 'path' => 'media/'.$name, 'filename' => $name, 'original_name' => $name,
        'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 1000, 'width' => 1600, 'height' => 600,
    ]);
}
