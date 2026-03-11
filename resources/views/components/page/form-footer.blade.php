<div class="fixed right-0 bg-white border-t border-gray-200 py-3 px-4 sm:px-6 z-20 shadow-md transition-all duration-300 ease-in-out"
     :class="collapsed ? 'left-[var(--sidebar-collapsed-width)]' : 'left-[var(--sidebar-width)]'"
     x-data
     :style="window.innerWidth < 1024 ? 'left: 0; bottom: var(--bottom-nav-height)' : 'bottom: 0'">
    <div class="max-w-7xl mx-auto flex justify-end space-x-3">
        {{ $slot }}
    </div>
</div>

{{-- Spacer: taller on mobile to account for both footer + bottom nav --}}
<div class="h-32 lg:h-20"></div>
