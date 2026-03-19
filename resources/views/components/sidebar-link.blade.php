@props(['active' => false])

<a {{ $attributes }}
   class="{{ $active
        ? 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-indigo-600 text-white'
        : 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition' }}">
    <span class="flex-shrink-0">{{ $icon }}</span>
    <span>{{ $slot }}</span>
</a>
