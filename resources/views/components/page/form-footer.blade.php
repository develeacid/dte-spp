<div class="fixed bottom-0 right-0 bg-white border-t border-gray-200 py-4 px-6 z-10 shadow-md transition-all duration-300 ease-in-out"
     :class="collapsed ? 'left-[var(--sidebar-collapsed-width)]' : 'left-[var(--sidebar-width)]'"
     x-bind:style="window.innerWidth < 1024 ? 'left: 0' : ''">
    <div class="max-w-7xl mx-auto flex justify-end space-x-3">
        {{ $slot }}
    </div>
</div>

{{-- Spacer --}}
<div class="h-20"></div>
