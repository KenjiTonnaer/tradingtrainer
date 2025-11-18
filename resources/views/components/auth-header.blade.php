@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center mb-2">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500 bg-clip-text text-transparent mb-2">
        {{ $title }}
    </h1>
    <p class="text-gray-600 text-sm">{{ $description }}</p>
</div>
