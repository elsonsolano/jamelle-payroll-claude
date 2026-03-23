@props(['active' => false])

<a {{ $attributes }}
   class="{{ $active
        ? 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-green-500 text-white'
        : 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-green-50 hover:text-green-700 transition' }}">
    <span class="flex-shrink-0">{{ $icon }}</span>
    <span>{{ $slot }}</span>
</a>
