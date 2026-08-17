@php
    $canView = function($permission) {
        if (!auth()->check()) {
            return true;
        }
        $user = auth()->user();
        return $user->hasRole(['Super Admin', 'Admin', 'admin']) || $user->can($permission);
    };
    $isAdmin = auth()->check() && auth()->user()->hasRole(['Super Admin', 'Admin', 'admin']);

    // Helper: is any of these routes active?
    $active = function(array $routes) {
        foreach ($routes as $r) {
            if (request()->routeIs($r)) return true;
        }
        return false;
    };
@endphp

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul class="sidebar-vertical">

                {{-- ===== DASHBOARD ===== --}}
                <li class="{{ $active(['index','dashboard']) ? 'active' : '' }}">
                    <a href="{{ route('index') }}">
                        <i class="fe fe-grid"></i><span> Dashboard</span>
                    </a>
                </li>

                {{-- ===== SALES & CUSTOMERS ===== --}}
                @if($canView('Sales Management') || $canView('Customer Management') || $canView('Payment Management'))
                    <li class="menu-title"><span>Sales & Commercial</span></li>

                    @if($canView('Sales Management'))
                        {{-- New Sale (direct link, prominent) --}}
                        <li class="{{ $active(['sales.create']) ? 'active' : '' }}">
                            <a href="{{ route('sales.create') }}">
                                <i class="fe fe-plus-circle"></i><span> New Sale</span>
                            </a>
                        </li>

                        {{-- Sales submenu --}}
                        <li class="submenu {{ $active(['sales.index','sales.show','sales.edit','sales.invoice','returns.*']) ? 'active' : '' }}">
                            <a href="javascript:void(0)">
                                <i class="fe fe-shopping-bag"></i><span> Sales</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="{{ $active(['sales.index','sales.show','sales.edit','sales.invoice','returns.*']) ? '' : '' }}" style="{{ $active(['sales.index','sales.show','sales.edit','sales.invoice','returns.*']) ? 'display:block' : '' }}">
                                <li class="{{ $active(['sales.index','sales.show','sales.edit','sales.invoice']) ? 'active' : '' }}">
                                    <a href="{{ route('sales.index') }}"><i class="fe fe-list"></i> Sales Orders</a>
                                </li>
                                <li class="{{ $active(['returns.*']) ? 'active' : '' }}">
                                    <a href="{{ route('returns.index') }}"><i class="fe fe-refresh-cw"></i> Sales Returns</a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if($canView('Customer Management'))
                        <li class="{{ $active(['customers.*']) ? 'active' : '' }}">
                            <a href="{{ route('customers.index') }}">
                                <i class="fe fe-users"></i><span> Customers</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Payment Management'))
                        <li class="{{ $active(['due-payments.*']) ? 'active' : '' }}">
                            <a href="{{ route('due-payments.index') }}">
                                <i class="fe fe-credit-card"></i><span> Due Payments</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ===== PROCUREMENT & STOCK ===== --}}
                @if($canView('Purchase Management') || $canView('Inventory Management') || $canView('Vendor Management'))
                    <li class="menu-title"><span>Procurement & Stock</span></li>

                    @if($canView('Purchase Management'))
                        {{-- Lots / Purchases submenu --}}
                        <li class="submenu {{ $active(['lots.*','purchase.create','purchase.index','purchase.show','purchase.edit','coils.*']) ? 'active' : '' }}">
                            <a href="javascript:void(0)">
                                <i class="fe fe-package"></i><span> Purchasing</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul style="{{ $active(['lots.*','purchase.create','purchase.index','purchase.show','purchase.edit','coils.*']) ? 'display:block' : '' }}">
                                <li class="{{ $active(['lots.*']) ? 'active' : '' }}">
                                    <a href="{{ route('lots.index') }}"><i class="fe fe-layers"></i> Lots</a>
                                </li>
                                <li class="{{ $active(['purchase.create']) ? 'active' : '' }}">
                                    <a href="{{ route('purchase.create') }}"><i class="fe fe-plus-circle"></i> New Purchase</a>
                                </li>
                                <li class="{{ $active(['purchase.index','purchase.show','purchase.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('purchase.index') }}"><i class="fe fe-shopping-cart"></i> Purchase Orders</a>
                                </li>
                                <li class="{{ $active(['coils.*']) ? 'active' : '' }}">
                                    <a href="{{ route('coils.index') }}"><i class="fe fe-disc"></i> Steel Coils</a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if($canView('Inventory Management'))
                        <li class="submenu {{ $active(['inventory.*','warehouses.*']) ? 'active' : '' }}">
                            <a href="javascript:void(0)">
                                <i class="fe fe-database"></i><span> Inventory</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul style="{{ $active(['inventory.*','warehouses.*']) ? 'display:block' : '' }}">
                                <li class="{{ $active(['inventory.*']) ? 'active' : '' }}">
                                    <a href="{{ route('inventory.index') }}"><i class="fe fe-bar-chart-2"></i> Stock Levels</a>
                                </li>
                                <li class="{{ $active(['warehouses.*']) ? 'active' : '' }}">
                                    <a href="{{ route('warehouses.index') }}"><i class="fe fe-map-pin"></i> Warehouses</a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if($canView('Vendor Management'))
                        <li class="{{ $active(['vendors.*']) ? 'active' : '' }}">
                            <a href="{{ route('vendors.index') }}">
                                <i class="fe fe-truck"></i><span> Vendors</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ===== EXPENSES & BANKING ===== --}}
                @if($canView('Accounts Management'))
                    <li class="menu-title"><span>Expenses & Banking</span></li>
                    <li class="submenu {{ $active(['dailyExpenses.*','expense-categories.*','bank-details.*']) ? 'active' : '' }}">
                        <a href="javascript:void(0)">
                            <i class="fe fe-credit-card"></i><span> Expenses</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $active(['dailyExpenses.*','expense-categories.*','bank-details.*']) ? 'display:block' : '' }}">
                            <li class="{{ $active(['dailyExpenses.*']) ? 'active' : '' }}">
                                <a href="{{ route('dailyExpenses.index') }}"><i class="fe fe-list"></i> Daily Expenses</a>
                            </li>
                            <li class="{{ $active(['expense-categories.*']) ? 'active' : '' }}">
                                <a href="{{ route('expense-categories.index') }}"><i class="fe fe-tag"></i> Expense Categories</a>
                            </li>
                            <li class="{{ $active(['bank-details.*']) ? 'active' : '' }}">
                                <a href="{{ route('bank-details.index') }}"><i class="fe fe-layers"></i> Bank Accounts</a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- ===== ACCOUNTING & BOOKS ===== --}}
                @if($isAdmin)
                    <li class="menu-title"><span>Accounting & Books</span></li>

                    {{-- Bookkeeping sub --}}
                    <li class="submenu {{ $active(['chart-of-accounts.*','journal-entries.*','ledger.*','fiscal-years.*']) ? 'active' : '' }}">
                        <a href="javascript:void(0)">
                            <i class="fe fe-book"></i><span> Bookkeeping</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $active(['chart-of-accounts.*','journal-entries.*','ledger.*','fiscal-years.*']) ? 'display:block' : '' }}">
                            <li class="{{ $active(['chart-of-accounts.*']) ? 'active' : '' }}">
                                <a href="{{ route('chart-of-accounts.index') }}"><i class="fe fe-folder"></i> Chart of Accounts</a>
                            </li>
                            <li class="{{ $active(['journal-entries.*']) ? 'active' : '' }}">
                                <a href="{{ route('journal-entries.index') }}"><i class="fe fe-file-text"></i> Journal Vouchers</a>
                            </li>
                            <li class="{{ $active(['ledger.*']) ? 'active' : '' }}">
                                <a href="{{ route('ledger.index') }}"><i class="fe fe-book-open"></i> General Ledger</a>
                            </li>
                            <li class="{{ $active(['fiscal-years.*']) ? 'active' : '' }}">
                                <a href="{{ route('fiscal-years.index') }}"><i class="fe fe-calendar"></i> Fiscal Years</a>
                            </li>
                        </ul>
                    </li>

                    {{-- Financial Statements sub --}}
                    <li class="submenu {{ $active(['trial-balance.*','reports.profit-loss*','reports.balance-sheet*','reports.cash-flow*']) ? 'active' : '' }}">
                        <a href="javascript:void(0)">
                            <i class="fe fe-trending-up"></i><span> Financial Statements</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $active(['trial-balance.*','reports.profit-loss*','reports.balance-sheet*','reports.cash-flow*']) ? 'display:block' : '' }}">
                            <li class="{{ $active(['trial-balance.*']) ? 'active' : '' }}">
                                <a href="{{ route('trial-balance.index') }}"><i class="fe fe-check-square"></i> Trial Balance</a>
                            </li>
                            <li class="{{ $active(['reports.profit-loss*']) ? 'active' : '' }}">
                                <a href="{{ route('reports.profit-loss') }}"><i class="fe fe-trending-up"></i> Profit & Loss</a>
                            </li>
                            <li class="{{ $active(['reports.balance-sheet*']) ? 'active' : '' }}">
                                <a href="{{ route('reports.balance-sheet') }}"><i class="fe fe-bar-chart-2"></i> Balance Sheet</a>
                            </li>
                            <li class="{{ $active(['reports.cash-flow*']) ? 'active' : '' }}">
                                <a href="{{ route('reports.cash-flow') }}"><i class="fe fe-dollar-sign"></i> Cash Flow</a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- ===== HR & STAFF ===== --}}
                @if($canView('Employee Management') || $isAdmin)
                    <li class="menu-title"><span>HR & Staff</span></li>
                    <li class="{{ $active(['employees.*']) ? 'active' : '' }}">
                        <a href="{{ route('employees.index') }}">
                            <i class="fe fe-users"></i><span> Employees</span>
                        </a>
                    </li>
                    <li class="{{ $active(['salary.*']) ? 'active' : '' }}">
                        <a href="{{ route('salary.index') }}">
                            <i class="fe fe-dollar-sign"></i><span> Salary & Payroll</span>
                        </a>
                    </li>
                @endif

                {{-- ===== REPORTS & ANALYTICS ===== --}}
                @if($canView('Report Management'))
                    <li class="menu-title"><span>Reports & Analytics</span></li>
                    <li class="submenu {{ $active(['sales.report','purchase.report','revenues.*','sales.extra-charges-report*']) ? 'active' : '' }}">
                        <a href="javascript:void(0)">
                            <i class="fe fe-pie-chart"></i><span> Reports</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $active(['sales.report','purchase.report','revenues.*','sales.extra-charges-report*']) ? 'display:block' : '' }}">
                            <li class="{{ $active(['sales.report']) ? 'active' : '' }}">
                                <a href="{{ route('sales.report') }}"><i class="fe fe-shopping-bag"></i> Sales Report</a>
                            </li>
                            <li class="{{ $active(['purchase.report']) ? 'active' : '' }}">
                                <a href="{{ route('purchase.report') }}"><i class="fe fe-shopping-cart"></i> Purchase Report</a>
                            </li>
                            <li class="{{ $active(['revenues.*']) ? 'active' : '' }}">
                                <a href="{{ route('revenues.index') }}"><i class="fe fe-bar-chart-2"></i> Revenue Report</a>
                            </li>
                            <li class="{{ $active(['sales.extra-charges-report*']) ? 'active' : '' }}">
                                <a href="{{ route('sales.extra-charges-report') }}"><i class="fe fe-truck"></i> Extra Charges</a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- ===== SYSTEM & SETTINGS ===== --}}
                @if($canView('Company Management') || $canView('Administration'))
                    <li class="menu-title"><span>System & Settings</span></li>

                    @if($canView('Company Management'))
                        <li class="{{ $active(['company-details.*']) ? 'active' : '' }}">
                            <a href="{{ route('company-details.index') }}">
                                <i class="fe fe-briefcase"></i><span> Company Profile</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Administration'))
                        <li class="submenu {{ $active(['users.*','role.*','permission.*']) ? 'active' : '' }}">
                            <a href="javascript:void(0)">
                                <i class="fe fe-shield"></i><span> Access Control</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul style="{{ $active(['users.*','role.*','permission.*']) ? 'display:block' : '' }}">
                                <li class="{{ $active(['users.*']) ? 'active' : '' }}">
                                    <a href="{{ route('users.index') }}"><i class="fe fe-user"></i> Users</a>
                                </li>
                                <li class="{{ $active(['role.*']) ? 'active' : '' }}">
                                    <a href="{{ route('role.index') }}"><i class="fe fe-shield"></i> Roles</a>
                                </li>
                                <li class="{{ $active(['permission.*']) ? 'active' : '' }}">
                                    <a href="{{ route('permission.index') }}"><i class="fe fe-lock"></i> Permissions</a>
                                </li>
                            </ul>
                        </li>
                    @endif
                @endif

            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-expand and scroll to active sidebar item
    function scrollToActiveSidebar() {
        var activeLink = document.querySelector('#sidebar .sidebar-inner a.active') || document.querySelector('#sidebar a.active');
        if (activeLink) {
            var sidebarInner = document.querySelector('#sidebar .sidebar-inner');
            if (sidebarInner) {
                var activeRect = activeLink.getBoundingClientRect();
                var sidebarRect = sidebarInner.getBoundingClientRect();
                var currentScroll = sidebarInner.scrollTop;
                var targetScroll = Math.max(0, (activeRect.top - sidebarRect.top + currentScroll) - (sidebarInner.clientHeight / 2) + (activeLink.clientHeight / 2));
                if (window.jQuery && typeof jQuery.fn.slimScroll !== 'undefined') {
                    jQuery('#sidebar .sidebar-inner').slimScroll({ scrollTo: targetScroll + 'px' });
                }
                sidebarInner.scrollTop = targetScroll;
            }
            // Expand parent submenu if nested
            var parentSubmenu = activeLink.closest('li.submenu');
            if (parentSubmenu) {
                parentSubmenu.classList.add('active');
                var subUl = parentSubmenu.querySelector('ul');
                if (subUl) { subUl.style.display = 'block'; }
            }
        }
    }
    setTimeout(scrollToActiveSidebar, 100);
    setTimeout(scrollToActiveSidebar, 350);
});
</script>