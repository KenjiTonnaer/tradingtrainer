@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center mb-2">
    <h1 class="text-3xl font-semibold mb-2">
        {{ $title }}
    </h1>
    <p class="hint">{{ $description }}</p>
</div>
