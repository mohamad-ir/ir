@props(['media' => null, 'variant' => 'md', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'alt' => null, 'eager' => false])
@if ($media)
    <img src="{{ $media->url(isset($media->variants[$variant]) ? $variant : null) }}"
         @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="{{ $sizes }}" @endif
         @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
         alt="{{ $alt ?? $media->altText() }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if($eager) fetchpriority="high" @endif
         {{ $attributes }}>
@endif
