@switch($source)
    @case('articles') <x-article-card :article="$item" :layout="$layout" /> @break
    @case('videos') <x-video-card :video="$item" :layout="$layout" /> @break
    @case('questions') <x-question-card :question="$item" /> @break
    @default <x-case-card :case="$item" :layout="$layout" />
@endswitch
