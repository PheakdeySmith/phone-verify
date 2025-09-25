<div class="horizontal-bar-wrap">
    <div class="header-topnav">
        <div class="container-fluid">
            <div class=" topnav rtl-ps-none" id="" data-perfect-scrollbar data-suppress-scroll-x="true">
                <ul class="menu float-start">
                    <li class="{{ request()->is('dashboard/*') ? 'active' : '' }}">
                        <div>
                            <div>
                                <a href="{{ route('dashboard_version_4') }}">
                                    <i class="nav-icon me-2 i-Bar-Chart"></i>
                                    Dashboard
                                </a>
                            </div>
                        </div>
                    </li>

                    <li class="{{ request()->is('verify/*') ? 'active' : '' }}">
                        <div>
                            <div>
                                <label class="toggle" for="drop-2">
                                    Forms
                                </label>
                                <a href="#">
                                    <i class="nav-icon me-2 i-File-Clipboard-File--Text"></i> Forms
                                </a><input type="checkbox" id="drop-2">
                                <ul>

                                    <li class="nav-item">
                                        <a class="{{ Route::currentRouteName() == 'verify' ? 'open' : '' }}"
                                            href="{{ route('verify') }}">
                                            <i class="nav-icon me-2 i-File-Clipboard-Text--Image"></i>
                                            <span class="item-name">Verify</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <!-- end Forms -->

                </ul>
            </div>
        </div>
    </div>

</div>
<!--=============== Horizontal bar End ================-->
