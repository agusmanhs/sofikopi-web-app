@php
   use Illuminate\Support\Facades\Route;
   $configData = Helper::appClasses();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

   <!-- ! Hide app brand if navbar-full -->
   @if (!isset($navbarFull))
      <div class="app-brand demo">
         <a href="{{ url('/') }}" class="app-brand-link">
            <span class="app-brand-logo demo"><img src="{{ asset('assets/img/favicon/sofikopi.svg') }}" alt="Logo"
                  width="50" height="50"></span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">{{ config('variables.templateName') }}</span>
         </a>

         <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
               xmlns="{{ asset('assets/img/favicon/sofikopi.svg') }}">
               <path
                  d="M8.47365 11.7183C8.11707 12.0749 8.11707 12.6531 8.47365 13.0097L12.071 16.607C12.4615 16.9975 12.4615 17.6305 12.071 18.021C11.6805 18.4115 11.0475 18.4115 10.657 18.021L5.83009 13.1941C5.37164 12.7356 5.37164 11.9924 5.83009 11.5339L10.657 6.707C11.0475 6.31653 11.6805 6.31653 12.071 6.707C12.4615 7.09747 12.4615 7.73053 12.071 8.121L8.47365 11.7183Z"
                  fill-opacity="0.9" />
               <path
                  d="M14.3584 11.8336C14.0654 12.1266 14.0654 12.6014 14.3584 12.8944L18.071 16.607C18.4615 16.9975 18.4615 17.6305 18.071 18.021C17.6805 18.4115 17.0475 18.4115 16.657 18.021L11.6819 13.0459C11.3053 12.6693 11.3053 12.0587 11.6819 11.6821L16.657 6.707C17.0475 6.31653 17.6805 6.31653 18.071 6.707C18.4615 7.09747 18.4615 7.73053 18.071 8.121L14.3584 11.8336Z"
                  fill-opacity="0.4" />
            </svg>
         </a>
      </div>
   @endif

   <div class="menu-inner-shadow"></div>

   <ul class="menu-inner py-1">
      @php
         $displayMenu = $menuData[0];
         if (isset($displayMenu->menu)) {
             $displayMenu = $displayMenu->menu;
         }

         if (!function_exists('collectMenuPaths')) {
             function collectMenuPaths($menus): array
             {
                 $paths = [];
                 foreach ($menus as $m) {
                     if (isset($m->path) && $m->path && $m->path !== '#') {
                         $paths[] = ltrim($m->path, '/');
                     }
                     $children = $m->submenu ?? ($m->children ?? null);
                     if ($children) {
                         $paths = array_merge($paths, collectMenuPaths($children));
                     }
                 }
                 return $paths;
             }
         }

         if (!function_exists('bestMenuPathPrefix')) {
             // Longest sidebar path that the current URL falls under. Used by
             // isMenuActive's prefix fallback so that on /absensi/history only
             // the '/absensi/history' item lights up, never '/absensi' too.
             function bestMenuPathPrefix(array $paths, string $currentPath): ?string
             {
                 $best = null;
                 foreach ($paths as $p) {
                     if ($p === '') {
                         continue;
                     }
                     if ($currentPath === $p || str_starts_with($currentPath, $p . '/')) {
                         if ($best === null || strlen($p) > strlen($best)) {
                             $best = $p;
                         }
                     }
                 }
                 return $best;
             }
         }

         if (!function_exists('isMenuActive')) {
             function isMenuActive($menu, $currentRouteName, $currentPath, $bestPrefix = null)
             {
                 $hasChildren =
                     (isset($menu->submenu) && count($menu->submenu) > 0) ||
                     (isset($menu->children) && count($menu->children) > 0);

                 // Priority 1: Exact Slug Match
                 if (isset($menu->slug) && $menu->slug && $currentRouteName === $menu->slug) {
                     return true;
                 }

                 // Priority 2: Resource/Sub-route Match
                 if (isset($menu->slug) && $menu->slug) {
                     // 2.1: Direct sub-route (e.g., 'absensi.history' matches 'absensi.history.detail')
                     if (str_starts_with($currentRouteName, $menu->slug . '.')) {
                         return true;
                     }

                     // 2.2: Standard Resource Siblings (e.g., 'absensi.index' matches 'absensi.edit')
                     // Refined to avoid overlapping matches when multiple actions (like index and create) are in the menu
                     $resourceActions = ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy', 'details'];
                     $actionGroups = [
                         ['index', 'show', 'edit', 'update', 'destroy', 'details'], // View/Manage Group
                         ['create', 'store'], // Create Group
                     ];

                     $menuSlugParts = explode('.', $menu->slug);
                     $menuAction = end($menuSlugParts);

                     if (in_array($menuAction, $resourceActions)) {
                         array_pop($menuSlugParts);
                         $menuBase = implode('.', $menuSlugParts);

                         $currentRouteParts = explode('.', $currentRouteName);
                         $currentAction = end($currentRouteParts);

                         if (in_array($currentAction, $resourceActions)) {
                             array_pop($currentRouteParts);
                             $currentBase = implode('.', $currentRouteParts);

                             if ($menuBase !== '' && $menuBase === $currentBase) {
                                 // Only match if they belong to the same action group
                                 foreach ($actionGroups as $group) {
                                     if (in_array($menuAction, $group) && in_array($currentAction, $group)) {
                                         return true;
                                     }
                                 }
                             }
                         }
                     }
                 }

                 // Priority 3: Path Match
                 if (isset($menu->path) && $menu->path !== null && $menu->path !== '' && $menu->path !== '#') {
                     $path = ltrim($menu->path, '/');
                     $trimmedCurrentPath = ltrim($currentPath, '/');

                     if ($path !== '') {
                         if ($trimmedCurrentPath === $path) {
                             return true;
                         }

                         // 3.1: Prefix match for nested pages whose route
                         // action isn't in the resource map above (e.g.
                         // /mitra-pos/stock/movements under /mitra-pos/stock,
                         // /mitra-pos/opname/create, /mitra-pos/manage/{mitra}/...
                         // under /mitra-pos/manage). Only the LONGEST matching
                         // sidebar path wins ($bestPrefix), so sibling items
                         // like '/izin' vs '/izin/create' never both light up.
                         if (str_starts_with($trimmedCurrentPath, $path . '/') && $path === $bestPrefix) {
                             return true;
                         }
                     } elseif ($trimmedCurrentPath === '') {
                         return true;
                     }
                 }

                 // Priority 4: Recursive Children Check
                 if ($hasChildren) {
                     $children = $menu->submenu ?? $menu->children;
                     foreach ($children as $child) {
                         if (isMenuActive($child, $currentRouteName, $currentPath, $bestPrefix)) {
                             return true;
                         }
                     }
                 }

                 return false;
             }
         }
      @endphp

      @foreach ($displayMenu as $menu)
         {{-- adding active and open class if child is active --}}

         {{-- menu headers --}}
         @if (isset($menu->menuHeader))
            <li class="menu-header mt-5">
               <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
            </li>
         @else
            {{-- active menu method --}}
            @php
               $currentRouteName = Route::currentRouteName() ?? '';
               $currentPath = request()->path();

               $hasChildren =
                   (isset($menu->submenu) && count($menu->submenu) > 0) ||
                   (isset($menu->children) && count($menu->children) > 0);

               $menuBestPrefix = $menuBestPrefix
                   ?? bestMenuPathPrefix(collectMenuPaths($displayMenu), ltrim($currentPath, '/'));
               $isActive = isMenuActive($menu, $currentRouteName, $currentPath, $menuBestPrefix);
               $activeClass = $isActive ? ($hasChildren ? 'active open' : 'active') : '';
            @endphp

            {{-- main menu --}}
            <li class="menu-item {{ $activeClass }}">
               <a href="{{ isset($menu->path) ? url($menu->path) : (isset($menu->url) ? url($menu->url) : 'javascript:void(0);') }}"
                  class="{{ $hasChildren ? 'menu-link menu-toggle' : 'menu-link' }}"
                  @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
                  @isset($menu->icon)
                     <i class="menu-icon tf-icons {{ $menu->icon }} me-3"></i>
                  @endisset
                  <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
                  @isset($menu->badge)
                     <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
                  @endisset
               </a>

               {{-- submenu --}}
               @if ($hasChildren)
                  @php
                     $submenuData = $menu->submenu ?? $menu->children;
                  @endphp
                  @include('layouts.sections.menu.submenu', [
                      'menu' => $submenuData,
                      'configData' => $configData,
                      'menuBestPrefix' => $menuBestPrefix,
                  ])
               @endif
            </li>
         @endif
      @endforeach
   </ul>

</aside>
