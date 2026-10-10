<div class="main-sidebar">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand mt-3">
            <img src="{{ $pengaturan?->logo ? URL::asset($pengaturan->logo) : 'https://via.placeholder.com/300' }}" alt="" style="width: 100px">
            <a href=""></a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="#">{{ strtoupper(substr(config('app.name'), 0, 2)) }}</a>
        </div>
        <ul class="sidebar-menu">
            @if (Auth::check() && Auth::user()->roles == 'admin')
            <li class="{{ request()->routeIs('admin.dashboard.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="fas fa-columns"></i> <span>Dashboard</span></a></li>
            <li class="menu-header">Master Data</li>
            <li class="{{ request()->routeIs('gudang-peralatan.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('gudang-peralatan.index') }}"><i class="fas fa-warehouse"></i> <span>Gudang Peralatan</span>
           </a></li>

            <li class="{{ request()->routeIs('pengaturan.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('pengaturan.index') }}"><i class="fas fa-cog"></i> <span>Pengaturan</span></a></li>

            @endif

        </ul>
    </aside>
</div>
