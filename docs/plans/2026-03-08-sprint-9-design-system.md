# Sprint 9: Design System & Layout Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Establish a design system with emerald branding, Inter font, CSS variables, and replace the Jetstream horizontal navbar with a collapsible sidebar layout with role-based navigation.

**Architecture:** CSS custom properties define all brand tokens in `app.css`. Tailwind config consumes them via `rgb(var(...))` pattern. A new Blade-based sidebar (`x-ui.sidebar`) with Alpine.js replaces the Jetstream `navigation-menu`. Existing atomic components are updated to use `brand` token classes. The layout (`app.blade.php`) switches from vertical (navbar + content) to horizontal (sidebar + content area with topbar).

**Tech Stack:** Laravel 12, Tailwind CSS 3.4, Alpine.js, Livewire 3, Heroicons, Inter (Google Fonts)

**Design doc:** `docs/plans/2026-03-08-sprint-9-design-system-design.md`

**Branch:** `feat/S9-design-system`

**Baseline:** 430 tests, 7 skipped

---

### Task 1: CSS Variables & Tailwind Config

**Files:**
- Modify: `resources/css/app.css`
- Modify: `tailwind.config.js`
- Modify: `resources/views/layouts/app.blade.php:11-12` (font link)
- Modify: `resources/views/layouts/guest.blade.php:11-12` (font link)

**Step 1: Update `resources/css/app.css` with design tokens**

Replace the entire file:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  :root {
    --color-primary: 5 150 105;
    --color-primary-hover: 4 120 87;
    --color-primary-light: 236 253 245;
    --color-primary-dark: 6 95 70;
    --color-danger: 220 38 38;
    --color-warning: 245 158 11;
    --color-info: 14 165 233;
    --color-surface: 255 255 255;
    --color-background: 249 250 251;
    --color-border: 229 231 235;
    --color-text: 17 24 39;
    --color-text-muted: 107 114 128;

    --sidebar-width: 240px;
    --sidebar-collapsed-width: 64px;
    --font-sans: 'Inter', sans-serif;
  }
}

[x-cloak] {
    display: none;
}
```

**Step 2: Update `tailwind.config.js`**

Replace the entire file:

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                    hover: 'rgb(var(--color-primary-hover) / <alpha-value>)',
                    light: 'rgb(var(--color-primary-light) / <alpha-value>)',
                    dark: 'rgb(var(--color-primary-dark) / <alpha-value>)',
                },
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                background: 'rgb(var(--color-background) / <alpha-value>)',
            },
            fontFamily: {
                sans: ['var(--font-sans)', ...defaultTheme.fontFamily.sans],
            },
            width: {
                sidebar: 'var(--sidebar-width)',
                'sidebar-collapsed': 'var(--sidebar-collapsed-width)',
            },
        },
    },

    plugins: [forms, typography],
};
```

**Step 3: Replace font links in both layouts**

In `resources/views/layouts/app.blade.php`, replace lines 11-12:
```html
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
```
with:
```html
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
```

Do the same replacement in `resources/views/layouts/guest.blade.php` lines 11-12.

**Step 4: Verify Vite builds without errors**

Run: `./vendor/bin/sail npm run build`
Expected: Build completes with no errors.

**Step 5: Commit**

```bash
git add resources/css/app.css tailwind.config.js resources/views/layouts/app.blade.php resources/views/layouts/guest.blade.php
git commit -m "feat(S9): add CSS design tokens and Tailwind brand config

Emerald branding palette, Inter font, sidebar width variables.
Replaces Figtree/bunny.net with Inter/Google Fonts."
```

---

### Task 2: Adapt Existing Components to Brand Tokens

**Files:**
- Modify: `resources/views/components/ui/button/primary.blade.php`
- Modify: `resources/views/components/ui/badge.blade.php`
- Modify: `resources/views/components/page/container.blade.php`
- Modify: `resources/views/components/page/header.blade.php`
- Modify: `resources/views/components/page/form-footer.blade.php`
- Modify: `resources/views/components/modals/confirm.blade.php`
- Modify: `resources/views/components/input.blade.php`
- Modify: `resources/views/components/checkbox.blade.php`
- Modify: `resources/views/components/button.blade.php`
- Modify: `resources/views/components/banner.blade.php`
- Modify: `resources/views/components/application-mark.blade.php`
- Modify: `resources/views/components/application-logo.blade.php`

**Step 1: Update `x-ui.button.primary`**

Replace all occurrences of `bg-indigo-600` with `bg-brand`, `bg-indigo-700` with `bg-brand-hover`, `ring-indigo-500` with `ring-brand`:

```blade
@props(['href' => null, 'type' => 'button'])

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-brand border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-hover focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-brand border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-hover focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </button>
@endif
```

**Step 2: Add `brand` variant to `x-ui.badge`**

Add `'brand' => 'bg-brand-light text-brand-dark'` to the colors array:

```blade
@props(['color' => 'blue'])

@php
$colors = [
    'blue'   => 'bg-blue-100 text-blue-800',
    'green'  => 'bg-green-100 text-green-800',
    'yellow' => 'bg-yellow-100 text-yellow-800',
    'red'    => 'bg-red-100 text-red-800',
    'gray'   => 'bg-gray-100 text-gray-800',
    'purple' => 'bg-purple-100 text-purple-800',
    'brand'  => 'bg-brand-light text-brand-dark',
];
$colorClass = $colors[$color] ?? $colors['blue'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$colorClass}"]) }}>
    {{ $slot }}
</span>
```

**Step 3: Update `x-input` focus ring**

Replace:
```blade
<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) !!}>
```
with:
```blade
<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 focus:border-brand focus:ring-brand rounded-md shadow-sm']) !!}>
```

**Step 4: Update `x-checkbox` color**

Replace:
```blade
<input type="checkbox" {!! $attributes->merge(['class' => 'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500']) !!}>
```
with:
```blade
<input type="checkbox" {!! $attributes->merge(['class' => 'rounded border-gray-300 text-brand shadow-sm focus:ring-brand']) !!}>
```

**Step 5: Update Jetstream `x-button` focus ring**

In `resources/views/components/button.blade.php`, replace `focus:ring-indigo-500` with `focus:ring-brand`:

```blade
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
```

**Step 6: Update `x-banner` success color**

In `resources/views/components/banner.blade.php`, replace `bg-indigo-500` with `bg-brand`, `bg-indigo-600` with `bg-brand-hover` in both the banner background and the icon span:

Line 4: `'bg-indigo-500': style == 'success'` → `'bg-brand': style == 'success'`
Line 15: `'bg-indigo-600': style == 'success'` → `'bg-brand-hover': style == 'success'`
Line 38: `'hover:bg-indigo-600 focus:bg-indigo-600': style == 'success'` → `'hover:bg-brand-hover focus:bg-brand-hover': style == 'success'`

**Step 7: Update application logo and mark SVG colors**

In `resources/views/components/application-mark.blade.php`, replace `fill="#6875F5"` with `fill="rgb(5,150,105)"` (emerald-600) on both paths.

In `resources/views/components/application-logo.blade.php`, replace `fill="#6875F5"` with `fill="rgb(5,150,105)"` on both paths.

**Step 8: Run test suite**

Run: `./vendor/bin/sail artisan test`
Expected: 430 passed, 7 skipped (no regressions)

**Step 9: Commit**

```bash
git add resources/views/components/
git commit -m "feat(S9): adapt existing components to brand tokens

Replace indigo hardcoded colors with brand CSS variables.
Update button, badge, input, checkbox, banner, logo SVGs."
```

---

### Task 3: Sidebar Component — `x-ui.sidebar`

**Files:**
- Create: `resources/views/components/ui/sidebar.blade.php`
- Create: `resources/views/components/ui/sidebar-group.blade.php`
- Create: `resources/views/components/ui/sidebar-item.blade.php`

**Step 1: Create `x-ui.sidebar-item`**

Create `resources/views/components/ui/sidebar-item.blade.php`:

```blade
@props([
    'href',
    'icon' => null,
    'active' => false,
    'badge' => null,
    'collapsed' => false,
])

@php
$activeClass = $active
    ? 'bg-brand-light text-brand-dark border-l-2 border-brand'
    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-2 border-transparent';
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => "group flex items-center px-3 py-2 text-sm font-medium rounded-r-md transition-colors {$activeClass}"]) }}>
    @if($icon)
        <span class="shrink-0 w-5 h-5" :class="collapsed ? 'mx-auto' : 'mr-3'">
            {{ $icon }}
        </span>
    @endif

    <span x-show="!collapsed" x-cloak class="flex-1 truncate">{{ $slot }}</span>

    @if($badge)
        <span x-show="!collapsed" x-cloak class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-brand-light text-brand-dark">
            {{ $badge }}
        </span>
    @endif
</a>
```

**Step 2: Create `x-ui.sidebar-group`**

Create `resources/views/components/ui/sidebar-group.blade.php`:

```blade
@props([
    'label',
    'icon' => null,
    'active' => false,
])

<div x-data="{ expanded: {{ $active ? 'true' : 'false' }} }" class="space-y-1">
    {{-- Group header --}}
    <button @click="expanded = !expanded"
            x-show="!collapsed" x-cloak
            class="w-full flex items-center px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 hover:text-gray-600 transition-colors">
        @if($icon)
            <span class="shrink-0 w-5 h-5 mr-3">{{ $icon }}</span>
        @endif
        <span class="flex-1 text-left">{{ $label }}</span>
        <svg class="w-4 h-4 transition-transform" :class="expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    {{-- Collapsed: just show icon with tooltip --}}
    <div x-show="collapsed" x-cloak class="relative group py-2 flex justify-center">
        @if($icon)
            <span class="w-5 h-5 text-gray-400 group-hover:text-gray-600">{{ $icon }}</span>
        @endif
        {{-- Tooltip popover --}}
        <div class="absolute left-full ml-2 top-0 hidden group-hover:block z-50">
            <div class="bg-gray-900 text-white text-xs rounded-md py-2 px-3 whitespace-nowrap shadow-lg">
                <div class="font-semibold mb-1">{{ $label }}</div>
                {{ $tooltip ?? '' }}
            </div>
        </div>
    </div>

    {{-- Items --}}
    <div x-show="!collapsed && expanded" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="space-y-0.5 ml-2">
        {{ $slot }}
    </div>
</div>
```

**Step 3: Create `x-ui.sidebar`**

Create `resources/views/components/ui/sidebar.blade.php`:

```blade
@props(['team' => null, 'user' => null])

<div x-data="{
        collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        mobileOpen: false,
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed);
        }
     }"
     x-on:keydown.escape.window="mobileOpen = false"
     class="relative">

    {{-- Mobile backdrop --}}
    <div x-show="mobileOpen" x-cloak
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-600/75 lg:hidden"
         @click="mobileOpen = false">
    </div>

    {{-- Sidebar panel --}}
    <aside :class="[
               mobileOpen ? 'translate-x-0' : '-translate-x-full',
               collapsed ? 'lg:w-[var(--sidebar-collapsed-width)]' : 'lg:w-[var(--sidebar-width)]',
               'lg:translate-x-0'
           ]"
           class="fixed inset-y-0 left-0 z-50 flex flex-col w-[var(--sidebar-width)] bg-white border-r border-gray-200 transition-all duration-300 ease-in-out">

        {{-- Logo --}}
        <div class="flex items-center h-16 px-4 border-b border-gray-100 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <x-application-mark class="block h-8 w-auto" />
                <span x-show="!collapsed" x-cloak class="text-lg font-semibold text-gray-900 truncate">
                    SPP 2026
                </span>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1">
            {{ $slot }}
        </nav>

        {{-- Footer: team switcher + user --}}
        <div class="border-t border-gray-200 px-2 py-3 space-y-2 shrink-0">
            {{-- Team switcher --}}
            @if($team)
                <div x-show="!collapsed" x-cloak class="px-3 py-2">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Unidad Responsable</div>
                    <div class="text-sm font-medium text-gray-700 truncate">{{ $team->name }}</div>
                </div>
                <div x-show="collapsed" x-cloak class="flex justify-center py-2 relative group">
                    <span class="w-8 h-8 rounded-full bg-brand-light text-brand-dark flex items-center justify-center text-xs font-bold">
                        {{ substr($team->name, 0, 2) }}
                    </span>
                    <div class="absolute left-full ml-2 top-0 hidden group-hover:block z-50">
                        <div class="bg-gray-900 text-white text-xs rounded-md py-1 px-2 whitespace-nowrap shadow-lg">
                            {{ $team->name }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- User --}}
            @if($user)
                <div x-show="!collapsed" x-cloak class="flex items-center px-3 py-2">
                    <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ collect(explode(' ', $user->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('') }}
                    </span>
                    <div class="ml-3 min-w-0">
                        <div class="text-sm font-medium text-gray-700 truncate">{{ $user->name }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
                <div x-show="collapsed" x-cloak class="flex justify-center py-2 relative group">
                    <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-bold">
                        {{ collect(explode(' ', $user->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('') }}
                    </span>
                    <div class="absolute left-full ml-2 top-0 hidden group-hover:block z-50">
                        <div class="bg-gray-900 text-white text-xs rounded-md py-1 px-2 whitespace-nowrap shadow-lg">
                            {{ $user->name }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Collapse toggle (desktop only) --}}
            <button @click="toggle()" class="hidden lg:flex w-full items-center justify-center py-2 text-gray-400 hover:text-gray-600 transition-colors">
                <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7"/>
                </svg>
                <svg x-show="collapsed" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </aside>
</div>
```

**Step 4: Verify Vite builds**

Run: `./vendor/bin/sail npm run build`
Expected: No errors

**Step 5: Commit**

```bash
git add resources/views/components/ui/sidebar.blade.php resources/views/components/ui/sidebar-group.blade.php resources/views/components/ui/sidebar-item.blade.php
git commit -m "feat(S9): create sidebar component system

x-ui.sidebar: collapsible shell with logo, nav, team, user footer.
x-ui.sidebar-group: expandable nav sections with tooltip on collapse.
x-ui.sidebar-item: nav link with icon, active state, optional badge."
```

---

### Task 4: Topbar Component — `x-ui.topbar`

**Files:**
- Create: `resources/views/components/ui/topbar.blade.php`
- Create: `resources/views/components/ui/avatar.blade.php`

**Step 1: Create `x-ui.avatar`**

Create `resources/views/components/ui/avatar.blade.php`:

```blade
@props([
    'name' => '',
    'src' => null,
    'size' => 'md',
])

@php
$sizes = [
    'sm' => 'w-6 h-6 text-xs',
    'md' => 'w-8 h-8 text-sm',
    'lg' => 'w-10 h-10 text-base',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => "rounded-full object-cover {$sizeClass}"]) }}>
@else
    <span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-full bg-gray-200 text-gray-600 font-semibold {$sizeClass}"]) }}>
        {{ $initials }}
    </span>
@endif
```

**Step 2: Create `x-ui.topbar`**

Create `resources/views/components/ui/topbar.blade.php`:

```blade
@props(['user' => null])

<header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 sm:px-6 bg-white border-b border-gray-200">
    {{-- Left: mobile hamburger + breadcrumb --}}
    <div class="flex items-center space-x-4">
        {{-- Mobile hamburger --}}
        <button @click="mobileOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Breadcrumb slot --}}
        @if(isset($breadcrumb))
            <nav class="hidden sm:flex text-sm text-gray-500 space-x-1">
                {{ $breadcrumb }}
            </nav>
        @endif
    </div>

    {{-- Right: user dropdown --}}
    <div class="flex items-center">
        @if($user)
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center space-x-2 text-sm text-gray-500 hover:text-gray-700 focus:outline-none transition">
                        <x-ui.avatar :name="$user->name" :src="Laravel\Jetstream\Jetstream::managesProfilePhotos() ? $user->profile_photo_url : null" size="sm" />
                        <span class="hidden md:inline font-medium">{{ $user->name }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="block px-4 py-2 text-xs text-gray-400">{{ __('Manage Account') }}</div>

                    <x-dropdown-link href="{{ route('profile.show') }}">
                        {{ __('Profile') }}
                    </x-dropdown-link>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <x-dropdown-link href="{{ route('api-tokens.index') }}">
                            {{ __('API Tokens') }}
                        </x-dropdown-link>
                    @endif

                    @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                        <div class="border-t border-gray-200"></div>
                        <div class="block px-4 py-2 text-xs text-gray-400">{{ __('Switch Teams') }}</div>
                        @foreach (Auth::user()->allTeams() as $team)
                            <x-switchable-team :team="$team" />
                        @endforeach
                    @endif

                    <div class="border-t border-gray-200"></div>

                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        @endif
    </div>
</header>
```

**Step 3: Verify Vite builds**

Run: `./vendor/bin/sail npm run build`
Expected: No errors

**Step 4: Commit**

```bash
git add resources/views/components/ui/topbar.blade.php resources/views/components/ui/avatar.blade.php
git commit -m "feat(S9): create topbar and avatar components

x-ui.topbar: sticky header with mobile hamburger, breadcrumb slot, user dropdown.
x-ui.avatar: initials or photo circle in sm/md/lg sizes."
```

---

### Task 5: Navigation Menu with Role-Based Sections

**Files:**
- Create: `resources/views/components/layout/sidebar-nav.blade.php`

This component defines the full navigation structure and is the only file that knows about routes and permissions.

**Step 1: Create `resources/views/components/layout/sidebar-nav.blade.php`**

```blade
{{-- Navigation structure — role-based visibility via @can --}}

{{-- Inicio --}}
<x-ui.sidebar-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
    </x-slot:icon>
    Inicio
</x-ui.sidebar-item>

{{-- Planeación --}}
@canany(['crear_programa', 'editar_mir'])
<x-ui.sidebar-group label="Planeación" :active="request()->routeIs('mml.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('mml.programas') }}" class="block py-1 hover:text-brand-light">Programas</a>
        <a href="{{ route('mml.importaciones') }}" class="block py-1 hover:text-brand-light">Importaciones</a>
    </x-slot:tooltip>

    @can('crear_programa')
        <x-ui.sidebar-item href="{{ route('mml.programas') }}" :active="request()->routeIs('mml.programas') || request()->routeIs('mml.etapa*') || request()->routeIs('mml.mir')">
            Programas
        </x-ui.sidebar-item>
        <x-ui.sidebar-item href="{{ route('mml.importaciones') }}" :active="request()->routeIs('mml.importar*') || request()->routeIs('mml.importaciones')">
            Importaciones
        </x-ui.sidebar-item>
    @endcan
</x-ui.sidebar-group>
@endcanany

{{-- Seguimiento --}}
@canany(['revisar_avance', 'capturar_avance'])
<x-ui.sidebar-group label="Seguimiento" :active="request()->routeIs('tracking.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        @can('revisar_avance')<a href="{{ route('tracking.panel') }}" class="block py-1 hover:text-brand-light">Panel</a>@endcan
        @can('capturar_avance')<a href="{{ route('tracking.mis-pendientes') }}" class="block py-1 hover:text-brand-light">Mis Indicadores</a>@endcan
    </x-slot:tooltip>

    @can('revisar_avance')
        <x-ui.sidebar-item href="{{ route('tracking.panel') }}" :active="request()->routeIs('tracking.panel')">
            Panel
        </x-ui.sidebar-item>
    @endcan
    @can('capturar_avance')
        <x-ui.sidebar-item href="{{ route('tracking.mis-pendientes') }}" :active="request()->routeIs('tracking.mis-pendientes')">
            Mis Indicadores
        </x-ui.sidebar-item>
    @endcan
    @can('revisar_avance')
        <x-ui.sidebar-item href="{{ route('tracking.vencidos') }}" :active="request()->routeIs('tracking.vencidos')">
            Vencidos
        </x-ui.sidebar-item>
    @endcan
</x-ui.sidebar-group>
@endcanany

{{-- Catálogos --}}
@can('gestionar_catalogos')
<x-ui.sidebar-group label="Catálogos" :active="request()->routeIs('cascade.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('cascade.ped.index') }}" class="block py-1 hover:text-brand-light">Plan Estatal</a>
        <a href="{{ route('cascade.programas-derivados.index') }}" class="block py-1 hover:text-brand-light">Prog. Derivados</a>
        <a href="{{ route('cascade.alineacion.index') }}" class="block py-1 hover:text-brand-light">Alineación</a>
    </x-slot:tooltip>

    <x-ui.sidebar-item href="{{ route('cascade.ped.index') }}" :active="request()->routeIs('cascade.ped.*')">
        Plan Estatal
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('cascade.programas-derivados.index') }}" :active="request()->routeIs('cascade.programas-derivados.*')">
        Programas Derivados
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('cascade.alineacion.index') }}" :active="request()->routeIs('cascade.alineacion.*')">
        Matriz de Alineación
    </x-ui.sidebar-item>
</x-ui.sidebar-group>
@endcan

{{-- Reportes --}}
@can('exportar_reportes')
<x-ui.sidebar-group label="Reportes" :active="request()->routeIs('evaluation.*') || request()->routeIs('datos-abiertos.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('evaluation.programa.index') }}" class="block py-1 hover:text-brand-light">Evaluación</a>
        <a href="{{ route('evaluation.transversal') }}" class="block py-1 hover:text-brand-light">Transversal</a>
    </x-slot:tooltip>

    <x-ui.sidebar-item href="{{ route('evaluation.programa.index') }}" :active="request()->routeIs('evaluation.programa.*')">
        Evaluación
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('evaluation.transversal') }}" :active="request()->routeIs('evaluation.transversal')">
        Transversal
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('evaluation.datos-abiertos.index') }}" :active="request()->routeIs('evaluation.datos-abiertos.*')">
        Datos Abiertos
    </x-ui.sidebar-item>
</x-ui.sidebar-group>
@endcan

{{-- Administración --}}
@can('administrar_usuarios')
<x-ui.sidebar-group label="Administración" :active="request()->routeIs('admin.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('admin.users') }}" class="block py-1 hover:text-brand-light">Usuarios</a>
        <a href="{{ route('admin.monitoreo-ia') }}" class="block py-1 hover:text-brand-light">Monitor IA</a>
    </x-slot:tooltip>

    <x-ui.sidebar-item href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users*')">
        Usuarios
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('admin.monitoreo-ia') }}" :active="request()->routeIs('admin.monitoreo-ia')">
        Monitor IA
    </x-ui.sidebar-item>
</x-ui.sidebar-group>
@endcan
```

> **Note:** The route names `admin.users`, `admin.monitoreo-ia`, `tracking.mis-pendientes`, `tracking.vencidos`, `evaluation.programa.index`, `evaluation.transversal`, `evaluation.datos-abiertos.index` must exist. If any route doesn't exist yet, verify its name in the corresponding route file and adjust. Some routes may need to be registered (Task 6 handles admin routes).

**Step 2: Commit**

```bash
git add resources/views/components/layout/sidebar-nav.blade.php
git commit -m "feat(S9): define navigation structure with role-based sections

6 sections: Inicio, Planeación, Seguimiento, Catálogos, Reportes, Admin.
Uses @can/@canany for permission-based visibility."
```

---

### Task 6: Admin Routes & Layout Integration

**Files:**
- Create: `routes/web/admin.php`
- Modify: `routes/web.php` (include admin routes)
- Modify: `resources/views/layouts/app.blade.php` (new layout structure)
- Modify: `resources/views/components/page/form-footer.blade.php`
- Modify: `resources/views/components/page/container.blade.php`

**Step 1: Create `routes/web/admin.php`**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'can:administrar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    // Users management — placeholder route until CRUD is built
    Route::view('/usuarios', 'admin.users-placeholder')->name('users');

    // AI monitoring
    Route::get('/monitoreo-ia', \App\Livewire\Admin\MonitoreoIa::class)->name('monitoreo-ia');
});
```

**Step 2: Create placeholder view `resources/views/admin/users-placeholder.blade.php`**

```blade
<x-app-layout>
    <x-page.container title="Gestión de Usuarios" subtitle="Próximamente">
        <x-ui.empty-state
            title="En construcción"
            description="La gestión de usuarios se implementará en un sprint futuro." />
    </x-page.container>
</x-app-layout>
```

> **Note:** This requires the `x-ui.empty-state` component from Task 7.

**Step 3: Include admin routes in `routes/web.php`**

Add after the existing route includes:
```php
require __DIR__.'/web/admin.php';
```

**Step 4: Verify existing evaluation routes match expected names**

Read `routes/web/evaluation.php` and confirm route names match `evaluation.programa.index`, `evaluation.transversal`, `evaluation.datos-abiertos.index`. If different, update `sidebar-nav.blade.php` to match the actual route names.

Also verify `routes/web/tracking.php` has `tracking.mis-pendientes` and `tracking.vencidos` routes.

**Step 5: Rewrite `resources/views/layouts/app.blade.php`**

Replace the entire file with the new sidebar layout:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased" x-data="{
        collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        mobileOpen: false,
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed);
        }
    }">
        <x-banner />

        <div class="min-h-screen bg-background">
            {{-- Sidebar --}}
            <x-ui.sidebar :team="Auth::user()?->currentTeam" :user="Auth::user()">
                <x-layout.sidebar-nav />
            </x-ui.sidebar>

            {{-- Main content area --}}
            <div class="transition-all duration-300 ease-in-out"
                 :class="collapsed ? 'lg:ml-[var(--sidebar-collapsed-width)]' : 'lg:ml-[var(--sidebar-width)]'">

                {{-- Topbar --}}
                <x-ui.topbar :user="Auth::user()">
                    @if(isset($breadcrumb))
                        <x-slot:breadcrumb>{{ $breadcrumb }}</x-slot:breadcrumb>
                    @endif
                </x-ui.topbar>

                {{-- Page Content --}}
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
```

**Step 6: Update `x-page.form-footer` for sidebar-aware offset**

Replace `resources/views/components/page/form-footer.blade.php`:

```blade
<div class="fixed bottom-0 right-0 bg-white border-t border-gray-200 py-4 px-6 z-10 shadow-md transition-all duration-300 ease-in-out"
     :class="collapsed ? 'left-[var(--sidebar-collapsed-width)]' : 'left-[var(--sidebar-width)]'"
     x-bind:style="window.innerWidth < 1024 ? 'left: 0' : ''">
    <div class="max-w-7xl mx-auto flex justify-end space-x-3">
        {{ $slot }}
    </div>
</div>

{{-- Spacer --}}
<div class="h-20"></div>
```

**Step 7: Update `x-page.container` background**

In `resources/views/components/page/container.blade.php`, no changes needed — `bg-background` is set by the layout's outer div. The container itself is transparent.

**Step 8: Run test suite**

Run: `./vendor/bin/sail artisan test`
Expected: 430 passed, 7 skipped

If tests fail due to missing routes (e.g., `admin.users`, `tracking.mis-pendientes`), check the actual route names and adjust `sidebar-nav.blade.php` accordingly. Routes that don't exist yet need placeholder registrations.

**Step 9: Commit**

```bash
git add routes/web/admin.php routes/web.php resources/views/layouts/app.blade.php resources/views/components/page/form-footer.blade.php resources/views/admin/
git commit -m "feat(S9): integrate sidebar layout replacing Jetstream navbar

New app layout with sidebar + topbar. Admin routes registered.
form-footer adjusts left offset based on sidebar state."
```

---

### Task 7: New Atomic Components — Widget, Stat, Empty State, Tooltip

**Files:**
- Create: `resources/views/components/ui/widget.blade.php`
- Create: `resources/views/components/ui/stat.blade.php`
- Create: `resources/views/components/ui/empty-state.blade.php`
- Create: `resources/views/components/ui/tooltip.blade.php`

**Step 1: Create `x-ui.widget`**

```blade
@props([
    'title' => '',
    'value' => '',
    'subtitle' => '',
    'icon' => null,
    'trend' => null,
    'trendUp' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-gray-200 p-5']) }}>
    <div class="flex items-center justify-between">
        <div class="min-w-0">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider truncate">{{ $title }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
            @if($subtitle)
                <p class="mt-1 text-xs text-gray-500">{{ $subtitle }}</p>
            @endif
            @if($trend)
                <p class="mt-1 text-xs font-medium {{ $trendUp ? 'text-brand' : 'text-red-600' }}">
                    {{ $trendUp ? '↑' : '↓' }} {{ $trend }}
                </p>
            @endif
        </div>
        @if($icon)
            <div class="shrink-0 ml-4 w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center">
                {{ $icon }}
            </div>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $slot }}
        </div>
    @endif
</div>
```

**Step 2: Create `x-ui.stat`**

```blade
@props([
    'label' => '',
    'value' => '',
    'change' => null,
    'changeUp' => true,
])

<div {{ $attributes->merge(['class' => 'text-center']) }}>
    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ $label }}</p>
    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $value }}</p>
    @if($change)
        <p class="mt-1 text-sm font-medium {{ $changeUp ? 'text-brand' : 'text-red-600' }}">
            {{ $changeUp ? '+' : '' }}{{ $change }}
        </p>
    @endif
</div>
```

**Step 3: Create `x-ui.empty-state`**

```blade
@props([
    'title' => 'Sin datos',
    'description' => '',
    'icon' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    @if($icon)
        <div class="mx-auto w-12 h-12 text-gray-300">
            {{ $icon }}
        </div>
    @else
        <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
    @endif

    <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ $title }}</h3>

    @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif

    @if($actionUrl && $actionLabel)
        <div class="mt-6">
            <x-ui.button.primary :href="$actionUrl">
                {{ $actionLabel }}
            </x-ui.button.primary>
        </div>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-6">
            {{ $slot }}
        </div>
    @endif
</div>
```

**Step 4: Create `x-ui.tooltip`**

```blade
@props(['text' => ''])

<div x-data="{ show: false }" class="relative inline-block" @mouseenter="show = true" @mouseleave="show = false">
    {{ $slot }}
    <div x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-full ml-2 top-1/2 -translate-y-1/2 z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded-md whitespace-nowrap shadow-lg pointer-events-none">
        {{ $text }}
    </div>
</div>
```

**Step 5: Verify Vite builds**

Run: `./vendor/bin/sail npm run build`
Expected: No errors

**Step 6: Commit**

```bash
git add resources/views/components/ui/widget.blade.php resources/views/components/ui/stat.blade.php resources/views/components/ui/empty-state.blade.php resources/views/components/ui/tooltip.blade.php
git commit -m "feat(S9): create widget, stat, empty-state, tooltip components

Shell components for Sprint 10 dashboard. Widget with icon/trend,
stat with change indicator, empty-state with action, tooltip for sidebar."
```

---

### Task 8: Tests — Layout & Navigation Rendering

**Files:**
- Create: `tests/Feature/LayoutTest.php`

**Step 1: Write layout and navigation tests**

Create `tests/Feature/LayoutTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\SystemPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole($roleName);

        return $user;
    }

    public function test_sidebar_renders_for_authenticated_user(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('SPP 2026');
        $response->assertSee('Inicio');
    }

    public function test_admin_sees_all_navigation_sections(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Planeación');
        $response->assertSee('Seguimiento');
        $response->assertSee('Catálogos');
        $response->assertSee('Reportes');
        $response->assertSee('Administración');
    }

    public function test_operador_sees_limited_navigation(): void
    {
        $user = $this->createUserWithRole('operador');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Inicio');
        $response->assertSee('Seguimiento');
        $response->assertDontSee('Planeación');
        $response->assertDontSee('Catálogos');
        $response->assertDontSee('Administración');
    }

    public function test_planeador_sees_planning_and_catalogs(): void
    {
        $user = $this->createUserWithRole('planeador');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Planeación');
        $response->assertSee('Catálogos');
        $response->assertSee('Reportes');
        $response->assertDontSee('Administración');
    }

    public function test_sidebar_shows_user_name(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee($user->name);
    }

    public function test_sidebar_shows_team_name(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee($user->currentTeam->name);
    }

    public function test_topbar_renders_with_user_dropdown(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Profile');
        $response->assertSee('Log Out');
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_inter_font_loaded(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('fonts.googleapis.com');
        $response->assertSee('Inter');
    }
}
```

**Step 2: Run the tests**

Run: `./vendor/bin/sail artisan test --filter=LayoutTest`
Expected: All tests pass. If any fail, debug by checking:
- Route names in `sidebar-nav.blade.php` match actual registered routes
- Permissions are seeded correctly
- The layout renders the sidebar components

**Step 3: Run full test suite for regressions**

Run: `./vendor/bin/sail artisan test`
Expected: 440+ passed (430 baseline + ~10 new), 7 skipped

**Step 4: Commit**

```bash
git add tests/Feature/LayoutTest.php
git commit -m "test(S9): add layout and navigation rendering tests

Verify sidebar visibility by role, user/team display, font loading,
and guest redirect. 10 tests covering admin, planeador, operador views."
```

---

### Task 9: Dashboard Placeholder with Empty State

**Files:**
- Modify: `resources/views/dashboard.blade.php`

**Step 1: Replace the default dashboard with a clean placeholder**

Replace `resources/views/dashboard.blade.php`:

```blade
<x-app-layout>
    <x-page.container title="Dashboard" subtitle="Sistema de Planeación y Programación 2026">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Placeholder widgets — will be populated in Sprint 10 --}}
            @can('revisar_avance')
                <x-ui.widget title="Programas" value="—" subtitle="Cargando datos..." />
                <x-ui.widget title="Indicadores" value="—" subtitle="Cargando datos..." />
                <x-ui.widget title="Avance Promedio" value="—" subtitle="Cargando datos..." />
            @endcan
            @can('capturar_avance')
                <x-ui.widget title="Pendientes" value="—" subtitle="Cargando datos..." />
            @endcan
        </div>

        <div class="mt-6">
            <x-ui.empty-state
                title="Dashboard en construcción"
                description="Los widgets operativos con datos reales se implementarán en Sprint 10." />
        </div>
    </x-page.container>
</x-app-layout>
```

**Step 2: Verify the page renders**

Run: `./vendor/bin/sail artisan test --filter=test_sidebar_renders_for_authenticated_user`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/views/dashboard.blade.php
git commit -m "feat(S9): replace default dashboard with widget placeholders

Clean dashboard using new widget and empty-state components.
Real data integration deferred to Sprint 10."
```

---

### Task 10: Visual Verification & Cleanup

**Files:**
- Possibly modify: `resources/views/components/ui/sidebar.blade.php` (refinements)
- Possibly modify: `resources/views/components/layout/sidebar-nav.blade.php` (route fixes)

**Step 1: Build frontend assets**

Run: `./vendor/bin/sail npm run build`
Expected: Build completes successfully

**Step 2: Run full test suite**

Run: `./vendor/bin/sail artisan test`
Expected: All previous tests + new LayoutTest pass (440+, 7 skipped)

**Step 3: Visual check list**

Start the dev server and verify in browser:
- [ ] Sidebar renders on left with emerald active accent
- [ ] Sidebar collapse/expand works and persists across page loads
- [ ] Mobile: sidebar hidden, hamburger opens overlay
- [ ] Navigation sections match role (test as admin, then as operador)
- [ ] Topbar sticky with user dropdown
- [ ] Team name visible in sidebar footer
- [ ] Dashboard renders with widget placeholders
- [ ] All existing pages still accessible (Programas, MIR, Plan Estatal, etc.)
- [ ] Forms (inputs, checkboxes) show emerald focus ring
- [ ] Primary buttons are emerald
- [ ] Logo SVG is emerald
- [ ] Inter font loads correctly

**Step 4: Fix any issues found during visual check**

Adjust CSS classes, route names, or component props as needed.

**Step 5: Run test suite one final time**

Run: `./vendor/bin/sail artisan test`
Expected: All pass

**Step 6: Final commit (if any fixes)**

```bash
git add -A
git commit -m "fix(S9): visual refinements from layout review"
```

---

## Summary

| Task | Description | Files | Commit |
|------|-------------|:---:|--------|
| 1 | CSS Variables & Tailwind Config | 4 | `feat(S9): add CSS design tokens and Tailwind brand config` |
| 2 | Adapt Existing Components | 12 | `feat(S9): adapt existing components to brand tokens` |
| 3 | Sidebar Component System | 3 | `feat(S9): create sidebar component system` |
| 4 | Topbar & Avatar | 2 | `feat(S9): create topbar and avatar components` |
| 5 | Navigation with Role Sections | 1 | `feat(S9): define navigation structure with role-based sections` |
| 6 | Admin Routes & Layout Integration | 5+ | `feat(S9): integrate sidebar layout replacing Jetstream navbar` |
| 7 | Widget, Stat, Empty State, Tooltip | 4 | `feat(S9): create widget, stat, empty-state, tooltip components` |
| 8 | Layout & Navigation Tests | 1 | `test(S9): add layout and navigation rendering tests` |
| 9 | Dashboard Placeholder | 1 | `feat(S9): replace default dashboard with widget placeholders` |
| 10 | Visual Verification & Cleanup | varies | `fix(S9): visual refinements from layout review` |

**Estimated new tests:** ~10
**Total components created:** 9 new, 8 modified
**Critical path:** T1 → T2 → T3 → T4 → T5 → T6 → T8
**Parallel after T6:** T7 ∥ T8 ∥ T9
