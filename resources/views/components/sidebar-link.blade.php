@props(['active' => false, 'badge' => null])

<a {{ $attributes }}
   class="{{ $active
        ? 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-green-500 text-white'
        : 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-green-50 hover:text-green-700 transition' }}">
    <span class="flex-shrink-0">{{ $icon }}</span>
    <span class="flex-1">{{ $slot }}</span>
    @if($badge)
        <span class="ml-auto text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1.5 leading-none
                     {{ $active ? 'bg-white text-green-700' : 'bg-amber-500 text-white' }}">
            {{ $badge > 99 ? '99+' : $badge }}
        </span>
    @endif
</a>
