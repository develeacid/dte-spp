{{-- resources/views/components/ui/bottom-action-bar.blade.php --}}
@if(isset($slot) && $slot->isNotEmpty())
    <div class="fixed inset-x-0 z-30 bg-white border-t border-gray-200 px-4 py-2.5 shadow-sm lg:hidden"
         style="bottom: var(--bottom-nav-height);">
        <div class="flex items-center justify-end space-x-2">
            {{ $slot }}
        </div>
    </div>
@endif
