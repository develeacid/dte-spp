@if ($tieneMir ?? false)
    <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 p-4">
        <div class="flex items-start gap-2">
            <svg class="h-5 w-5 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
            <div>
                <p class="text-sm font-medium text-amber-800">Este programa ya tiene una MIR creada.</p>
                <p class="text-xs text-amber-700 mt-0.5">Modificar el árbol o las alternativas puede afectar la coherencia de la MIR. Considere crear un snapshot antes de continuar.</p>
            </div>
        </div>
    </div>
@endif
