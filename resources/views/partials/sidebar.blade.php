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
    @auth
        {{-- ==================== MENU ADMIN ==================== --}}
        @if (Auth::user()->roles == 'admin')
            <li class="{{ request()->routeIs('admin.dashboard.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-columns"></i> <span>Dashboard</span>
                </a>
            </li>


            <li class="menu-header">Master Data</li>
            <li class="{{ request()->routeIs('cctv.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('cctv.index') }}">
                    <i class="fas fa-video"></i> <span>Live CCTV</span>
                </a>
            </li>
            
            <li class="#">
                <a class="nav-link" href="#">
                    <i class="fas fa-file-import"></i> <span>Master Lokasi</span>
                </a>
            </li>
            <!-- <li class="{{ request()->routeIs('permohonan-barang-masuk.monitoring') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-masuk.monitoring') }}">
                    <i class="fas fa-clipboard-check"></i> <span></span>
                </a>
            </li>
            <li class="{{ request()->routeIs('permohonan-barang-out.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-out.index') }}">
                    <i class="fas fa-sign-out-alt"></i> <span>Permohonan Barang Keluar</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('pengaturan.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('pengaturan.index') }}">
                    <i class="fas fa-cog"></i> <span>Pengaturan</span>
                </a>
            </li> -->
            <li class="menu-header">Permohonan</li>
            
            <li class="{{ request()->routeIs('permohonan-barang-masuk.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-masuk.index') }}">
                    <i class="fas fa-file-import"></i> <span>Permohonan Barang Masuk</span>
                </a>
            </li>
            
            <li class="{{ request()->routeIs('permohonan-barang-out.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-out.index') }}">
                    <i class="fas fa-sign-out-alt"></i> <span>Permohonan Barang Keluar</span>
                </a>
            </li>
             <li class="menu-header">Approval</li>
            
            <li class="{{ request()->routeIs('permohonan-barang-masuk.monitoring') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-masuk.monitoring') }}">
                    <i class="fas fa-clipboard-check"></i> <span>Monitoring Approval</span>
                </a>
            </li>
            
            <li class="menu-header">Settings</li>
            
            <li class="{{ request()->routeIs('pengaturan.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('pengaturan.index') }}">
                    <i class="fas fa-cog"></i> <span>Logos</span>
                </a>
            </li>
        @endif

        {{-- ==================== MENU USER & SUPERVISOR ==================== --}}
        @if (in_array(Auth::user()->roles, ['user', 'sepervisior'], true))
            <li class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-columns"></i> <span>Dashboard</span>
                </a>
            </li>

            <li class="menu-header">Permohonan</li>

            <li class="{{ request()->routeIs('permohonan-barang-masuk.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-masuk.index') }}">
                    <i class="fas fa-file-import"></i> <span>Permohonan Barang Masuk</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('permohonan-barang-out.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-out.index') }}">
                    <i class="fas fa-sign-out-alt"></i> <span>Permohonan Barang Keluar</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('permohonan-barang-masuk.monitoring') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-masuk.monitoring') }}">
                    <i class="fas fa-clipboard-check"></i> <span>Monitoring Approval</span>
                </a>
            </li>
        @endif
        {{-- ==================== MENU PETUGAS ==================== --}}
        @if (Auth::user()->roles === 'petugas')
            <li class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-columns"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-header">Permohonan</li>
            <li class="{{ request()->routeIs('permohonan-barang-out.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('permohonan-barang-out.index') }}">
                    <i class="fas fa-sign-out-alt"></i> <span>Daftar Barang Keluar</span>
                </a>
            </li>
        @endif
{{-- ==================== MENU APPROVAL / INBOX (Admin, Supervisor, Petugas) ==================== --}}
        @if (in_array(Auth::user()->roles, ['admin', 'sepervisior', 'petugas'], true))
           @if (Auth::user()->roles != 'admin')
            
                <li class="menu-header">Persetujuan</li>
            @endif
            <li class="{{ request()->routeIs('approval-inbox.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('approval-inbox.index') }}">
                    <i class="fas fa-inbox"></i> <span>Pesan Masuk</span>
                    @if (isset($approvalInboxCount) && $approvalInboxCount > 0)
                        <span class="badge badge-danger ml-auto">{{ $approvalInboxCount }}</span>
                    @endif
                </a>
            </li>
        @endif     
    @endauth
</ul>
    </aside>
</div>
