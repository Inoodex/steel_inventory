@php
    $canView = function($permission) {
        if (!auth()->check()) {
            return true;
        }
        $user = auth()->user();
        return $user->hasRole(['Super Admin', 'Admin', 'admin']) || $user->can($permission);
    };
@endphp

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul class="sidebar-vertical">
                <!-- 1. Dashboard -->
                <li>
                    <a href="{{ route('index') }}" class="{{ request()->routeIs('index') ? 'active' : '' }}">
                        <i class="fe fe-grid"></i><span> Dashboard</span>
                    </a>
                </li>

                <!-- 2. Sales Management -->
                @if($canView('Sales Management'))
                    <li class="menu-title"><span>Sales Management</span></li>
                    <li>
                        <a href="{{ route('sales.create') }}" class="{{ request()->routeIs('sales.create') ? 'active' : '' }}">
                            <i class="fe fe-shopping-cart"></i> <span> Add Sales</span>
                        </a>
                        <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.index') || request()->routeIs('sales.show') ? 'active' : '' }}">
                            <i class="fe fe-shopping-bag"></i> <span>Sales List</span>
                        </a>
                        <a href="{{ route('returns.index') }}" class="{{ request()->routeIs('returns.*') ? 'active' : '' }}">
                            <i class="fe fe-refresh-cw"></i> <span> Return List</span>
                        </a>
                    </li>
                @endif

                <!-- 3. Customer Management -->
                @if($canView('Customer Management'))
                    <li class="menu-title"><span>Customer Management</span></li>
                    <li>
                        <a href="{{ route('customers.create') }}" class="{{ request()->routeIs('customers.create') ? 'active' : '' }}">
                            <i class="fe fe-user-plus"></i> <span> Add Customer</span>
                        </a>
                        <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.index') ? 'active' : '' }}">
                            <i class="fe fe-users"></i> <span>Customers List</span>
                        </a>
                    </li>
                @endif



                <!-- 5. Inventory & Stock -->
                @if($canView('Inventory Management'))
                    <li class="menu-title"><span>Inventory & Stock</span></li>
                    <li>
                        <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <i class="fe fe-database"></i> <span> Inventory Stock List</span>
                        </a>
                        <a href="{{ route('warehouses.index') }}" class="{{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                            <i class="fe fe-map-pin"></i> <span> Stockyards & Warehouses</span>
                        </a>
                    </li>
                @endif

                <!-- 6. Purchase & Lot Management -->
                @if($canView('Purchase Management'))
                    <li class="menu-title"><span>Purchases & Lots</span></li>
                    <li>
                        <a href="{{ route('lots.index') }}" class="{{ request()->routeIs('lots.*') ? 'active' : '' }}">
                            <i class="fe fe-package"></i> <span> Lot Management</span>
                        </a>
                        <a href="{{ route('purchase.create') }}" class="{{ request()->routeIs('purchase.create') ? 'active' : '' }}">
                            <i class="fe fe-plus-circle"></i> <span> Steel Purchase</span>
                        </a>
                        <a href="{{ route('purchase.index') }}" class="{{ request()->routeIs('purchase.index') ? 'active' : '' }}">
                            <i class="fe fe-shopping-cart"></i> <span> Purchase Orders</span>
                        </a>
                        <a href="{{ route('coils.index') }}" class="{{ request()->routeIs('coils.*') ? 'active' : '' }}">
                            <i class="fe fe-disc"></i> <span> Ship Coils & Plates</span>
                        </a>
                    </li>
                @endif

                <!-- 7. Vendor Management -->
                @if($canView('Vendor Management'))
                    <li class="menu-title"><span>Vendor Management</span></li>
                    <li>
                        <a href="{{ route('vendors.create') }}" class="{{ request()->routeIs('vendors.create') ? 'active' : '' }}">
                            <i class="fe fe-user-plus"></i> <span> Add Vendor</span>
                        </a>
                        <a href="{{ route('vendors.index') }}" class="{{ request()->routeIs('vendors.index') ? 'active' : '' }}">
                            <i class="fe fe-users"></i> <span>Vendor List</span>
                        </a>
                    </li>
                @endif

                <!-- 8. Billing & Payments -->
                @if($canView('Payment Management'))
                    <li class="menu-title"><span>Billing & Payments</span></li>
                    <li>
                        <a href="{{ route('due-payments.index') }}" class="{{ request()->routeIs('due-payments.*') ? 'active' : '' }}">
                            <i class="fe fe-dollar-sign"></i> <span>Due Payments</span>
                        </a>
                    </li>
                @endif

                <!-- 9. Accounts & Expenses -->
                @if($canView('Accounts Management'))
                    <li class="menu-title"><span>Accounts & Expenses</span></li>
                    <li>
                        <a href="{{ route('expense-categories.index') }}" class="{{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
                            <i class="fe fe-tag"></i> <span>Expense Categories</span>
                        </a>
                        <a href="{{ route('dailyExpenses.index') }}" class="{{ request()->routeIs('dailyExpenses.*') ? 'active' : '' }}">
                            <i class="fe fe-list"></i> <span>Daily Expense List</span>
                        </a>
                        <a href="{{ route('bank-details.index') }}" class="{{ request()->routeIs('bank-details.*') ? 'active' : '' }}">
                            <i class="fe fe-layers"></i> <span>Bank Details</span>
                        </a>
                    </li>
                @endif

                <!-- Double-Entry Accounting & Bookkeeping -->
                @if(auth()->check() && auth()->user()->hasRole(['Super Admin', 'Admin', 'admin']))
                    <li class="menu-title"><span>Accounting & Bookkeeping</span></li>
                    <li>
                        <a href="{{ route('chart-of-accounts.index') }}" class="{{ request()->routeIs('chart-of-accounts.*') ? 'active' : '' }}">
                            <i class="fe fe-folder"></i> <span>Chart of Accounts</span>
                        </a>
                        <a href="{{ route('journal-entries.index') }}" class="{{ request()->routeIs('journal-entries.*') ? 'active' : '' }}">
                            <i class="fe fe-file-text"></i> <span>Journal Vouchers</span>
                        </a>
                        <a href="{{ route('ledger.index') }}" class="{{ request()->routeIs('ledger.*') ? 'active' : '' }}">
                            <i class="fe fe-book"></i> <span>General Ledger</span>
                        </a>
                        <a href="{{ route('trial-balance.index') }}" class="{{ request()->routeIs('trial-balance.*') ? 'active' : '' }}">
                            <i class="fe fe-check-square"></i> <span>Trial Balance</span>
                        </a>
                        <a href="{{ route('reports.profit-loss') }}" class="{{ request()->routeIs('reports.profit-loss*') ? 'active' : '' }}">
                            <i class="fe fe-trending-up"></i> <span>Profit & Loss (P&L)</span>
                        </a>
                        <a href="{{ route('reports.balance-sheet') }}" class="{{ request()->routeIs('reports.balance-sheet*') ? 'active' : '' }}">
                            <i class="fe fe-bar-chart-2"></i> <span>Balance Sheet</span>
                        </a>
                        <a href="{{ route('reports.cash-flow') }}" class="{{ request()->routeIs('reports.cash-flow*') ? 'active' : '' }}">
                            <i class="fe fe-dollar-sign"></i> <span>Cash Flow Statement</span>
                        </a>
                        <a href="{{ route('fiscal-years.index') }}" class="{{ request()->routeIs('fiscal-years.*') ? 'active' : '' }}">
                            <i class="fe fe-calendar"></i> <span>Fiscal Years & Closing</span>
                        </a>
                    </li>
                @endif

                <!-- 13. Employee Portal -->
                @if(auth()->check() && (auth()->user()->hasRole(['Employee', 'employee']) || auth()->user()->employee))
                    <li class="menu-title"><span>Employee Portal</span></li>
                    <li>
                        <a href="{{ route('employee.tada.index') }}" class="{{ request()->routeIs('employee.tada.index') ? 'active' : '' }}">
                            <i class="fe fe-list"></i> <span>My TA/DA List</span>
                        </a>
                        <a href="{{ route('employee.tada.create') }}" class="{{ request()->routeIs('employee.tada.create') ? 'active' : '' }}">
                            <i class="fe fe-upload"></i> <span>Submit TA/DA</span>
                        </a>
                    </li>
                @endif

                <!-- 14. Company Details -->
                @if($canView('Company Management'))
                    <li class="menu-title"><span>Company Config</span></li>
                    <li>
                        <a href="{{ route('company-details.index') }}" class="{{ request()->routeIs('company-details.*') ? 'active' : '' }}">
                            <i class="fe fe-briefcase"></i> <span> Company Details</span>
                        </a>
                    </li>
                @endif

                <!-- 15. Reports & Analytics -->
                @if($canView('Report Management'))
                    <li class="menu-title"><span>Reports & Analytics</span></li>
                    <li>
                        <a href="{{ route('sales.report') }}" class="{{ request()->routeIs('sales.report') ? 'active' : '' }}">
                            <i class="fe fe-file-text"></i> <span> Sales Report</span>
                        </a>
                        <a href="{{ route('purchase.report') }}" class="{{ request()->routeIs('purchase.report') ? 'active' : '' }}">
                            <i class="fe fe-file-text"></i> <span> Purchase Report</span>
                        </a>
                        <a href="{{ route('revenues.index') }}" class="{{ request()->routeIs('revenues.*') ? 'active' : '' }}">
                            <i class="fe fe-bar-chart-2"></i> <span> Revenue Report</span>
                        </a>
                        <a href="{{ route('sales.extra-charges-report') }}" class="{{ request()->routeIs('sales.extra-charges-report*') ? 'active' : '' }}">
                            <i class="fe fe-truck"></i> <span> Extra Charges Report</span>
                        </a>
                    </li>
                @endif

                <!-- 16. System Authorization & Users -->
                @if($canView('Administration'))
                    <li class="menu-title"><span>System & Security</span></li>
                    <li>
                        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="fe fe-user"></i> <span> Users</span>
                        </a>
                        <a href="{{ route('role.index') }}" class="{{ request()->routeIs('role.*') ? 'active' : '' }}">
                            <i class="fe fe-shield"></i> <span> Roles</span>
                        </a>
                        <a href="{{ route('permission.index') }}" class="{{ request()->routeIs('permission.*') ? 'active' : '' }}">
                            <i class="fe fe-lock"></i> <span> Permissions</span>
                        </a>
                    </li>
                @endif

            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
                if (subUl) {
                    subUl.style.display = 'block';
                }
            }
        }
    }

    setTimeout(scrollToActiveSidebar, 100);
    setTimeout(scrollToActiveSidebar, 350);
});
</script>