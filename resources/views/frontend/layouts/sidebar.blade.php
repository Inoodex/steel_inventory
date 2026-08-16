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

                <!-- 1. Core Overview -->
                <li class="{{ request()->routeIs('index') || request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('index') }}" class="{{ request()->routeIs('index') || request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fe fe-grid"></i><span> Dashboard</span>
                    </a>
                </li>

                <!-- 2. Sales & Commercial -->
                @if($canView('Sales Management') || $canView('Customer Management') || $canView('Payment Management'))
                    <li class="menu-title"><span>Sales & Customers</span></li>

                    @if($canView('Sales Management'))
                        <li class="{{ request()->routeIs('sales.create') ? 'active' : '' }}">
                            <a href="{{ route('sales.create') }}" class="{{ request()->routeIs('sales.create') ? 'active' : '' }}">
                                <i class="fe fe-plus-circle"></i> <span> New Sale</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('sales.index') || request()->routeIs('sales.show') || request()->routeIs('sales.edit') || request()->routeIs('sales.invoice') ? 'active' : '' }}">
                            <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.index') || request()->routeIs('sales.show') || request()->routeIs('sales.edit') || request()->routeIs('sales.invoice') ? 'active' : '' }}">
                                <i class="fe fe-shopping-bag"></i> <span> Sales Orders</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('returns.*') ? 'active' : '' }}">
                            <a href="{{ route('returns.index') }}" class="{{ request()->routeIs('returns.*') ? 'active' : '' }}">
                                <i class="fe fe-refresh-cw"></i> <span> Sales Returns</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Customer Management'))
                        <li class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                <i class="fe fe-users"></i> <span> Customers Directory</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Payment Management'))
                        <li class="{{ request()->routeIs('due-payments.*') ? 'active' : '' }}">
                            <a href="{{ route('due-payments.index') }}" class="{{ request()->routeIs('due-payments.*') ? 'active' : '' }}">
                                <i class="fe fe-dollar-sign"></i> <span> Due Payments</span>
                            </a>
                        </li>
                    @endif
                @endif

                <!-- 3. Procurement & Stock Management -->
                @if($canView('Purchase Management') || $canView('Inventory Management') || $canView('Vendor Management'))
                    <li class="menu-title"><span>Procurement & Stock</span></li>

                    @if($canView('Purchase Management'))
                        <li class="{{ request()->routeIs('lots.*') ? 'active' : '' }}">
                            <a href="{{ route('lots.index') }}" class="{{ request()->routeIs('lots.*') ? 'active' : '' }}">
                                <i class="fe fe-package"></i> <span> Lots Management</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('purchase.create') ? 'active' : '' }}">
                            <a href="{{ route('purchase.create') }}" class="{{ request()->routeIs('purchase.create') ? 'active' : '' }}">
                                <i class="fe fe-plus-circle"></i> <span> Steel Purchase</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('purchase.index') || request()->routeIs('purchase.show') || request()->routeIs('purchase.edit') ? 'active' : '' }}">
                            <a href="{{ route('purchase.index') }}" class="{{ request()->routeIs('purchase.index') || request()->routeIs('purchase.show') || request()->routeIs('purchase.edit') ? 'active' : '' }}">
                                <i class="fe fe-shopping-cart"></i> <span> Purchase Orders</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('coils.*') ? 'active' : '' }}">
                            <a href="{{ route('coils.index') }}" class="{{ request()->routeIs('coils.*') ? 'active' : '' }}">
                                <i class="fe fe-disc"></i> <span> Coils List</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Inventory Management'))
                        <li class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                                <i class="fe fe-database"></i> <span> Inventory Stock</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                            <a href="{{ route('warehouses.index') }}" class="{{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                                <i class="fe fe-map-pin"></i> <span> Stockyards & Warehouses</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Vendor Management'))
                        <li class="{{ request()->routeIs('vendors.*') ? 'active' : '' }}">
                            <a href="{{ route('vendors.index') }}" class="{{ request()->routeIs('vendors.*') ? 'active' : '' }}">
                                <i class="fe fe-truck"></i> <span> Vendors Directory</span>
                            </a>
                        </li>
                    @endif
                @endif

                <!-- 4. Expenses & Banking -->
                @if($canView('Accounts Management'))
                    <li class="menu-title"><span>Expenses & Banking</span></li>
                    <li class="{{ request()->routeIs('dailyExpenses.*') ? 'active' : '' }}">
                        <a href="{{ route('dailyExpenses.index') }}" class="{{ request()->routeIs('dailyExpenses.*') ? 'active' : '' }}">
                            <i class="fe fe-list"></i> <span> Daily Expenses</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
                        <a href="{{ route('expense-categories.index') }}" class="{{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
                            <i class="fe fe-tag"></i> <span> Expense Categories</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('bank-details.*') ? 'active' : '' }}">
                        <a href="{{ route('bank-details.index') }}" class="{{ request()->routeIs('bank-details.*') ? 'active' : '' }}">
                            <i class="fe fe-layers"></i> <span> Bank Accounts</span>
                        </a>
                    </li>
                @endif

                <!-- 5. Double-Entry Accounting & Bookkeeping -->
                @if(auth()->check() && auth()->user()->hasRole(['Super Admin', 'Admin', 'admin']))
                    <li class="menu-title"><span>Accounting & Books</span></li>
                    <li class="{{ request()->routeIs('chart-of-accounts.*') ? 'active' : '' }}">
                        <a href="{{ route('chart-of-accounts.index') }}" class="{{ request()->routeIs('chart-of-accounts.*') ? 'active' : '' }}">
                            <i class="fe fe-folder"></i> <span> Chart of Accounts</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('journal-entries.*') ? 'active' : '' }}">
                        <a href="{{ route('journal-entries.index') }}" class="{{ request()->routeIs('journal-entries.*') ? 'active' : '' }}">
                            <i class="fe fe-file-text"></i> <span> Journal Vouchers</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('ledger.*') ? 'active' : '' }}">
                        <a href="{{ route('ledger.index') }}" class="{{ request()->routeIs('ledger.*') ? 'active' : '' }}">
                            <i class="fe fe-book"></i> <span> General Ledger</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('trial-balance.*') ? 'active' : '' }}">
                        <a href="{{ route('trial-balance.index') }}" class="{{ request()->routeIs('trial-balance.*') ? 'active' : '' }}">
                            <i class="fe fe-check-square"></i> <span> Trial Balance</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('reports.profit-loss*') ? 'active' : '' }}">
                        <a href="{{ route('reports.profit-loss') }}" class="{{ request()->routeIs('reports.profit-loss*') ? 'active' : '' }}">
                            <i class="fe fe-trending-up"></i> <span> Profit & Loss (P&L)</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('reports.balance-sheet*') ? 'active' : '' }}">
                        <a href="{{ route('reports.balance-sheet') }}" class="{{ request()->routeIs('reports.balance-sheet*') ? 'active' : '' }}">
                            <i class="fe fe-bar-chart-2"></i> <span> Balance Sheet</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('reports.cash-flow*') ? 'active' : '' }}">
                        <a href="{{ route('reports.cash-flow') }}" class="{{ request()->routeIs('reports.cash-flow*') ? 'active' : '' }}">
                            <i class="fe fe-dollar-sign"></i> <span> Cash Flow Statement</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('fiscal-years.*') ? 'active' : '' }}">
                        <a href="{{ route('fiscal-years.index') }}" class="{{ request()->routeIs('fiscal-years.*') ? 'active' : '' }}">
                            <i class="fe fe-calendar"></i> <span> Fiscal Years & Closing</span>
                        </a>
                    </li>
                @endif

                <!-- 6. HR & Staff Management -->
                @if($canView('Employee Management') || (auth()->check() && auth()->user()->hasRole(['Super Admin', 'Admin', 'admin'])))
                    <li class="menu-title"><span>HR & Staff Management</span></li>
                    <li class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                            <i class="fe fe-users"></i> <span> Employee Directory</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('salary.*') ? 'active' : '' }}">
                        <a href="{{ route('salary.index') }}" class="{{ request()->routeIs('salary.*') ? 'active' : '' }}">
                            <i class="fe fe-dollar-sign"></i> <span> Salary & Payroll</span>
                        </a>
                    </li>
                @endif

                <!-- 7. Reports & Analytics -->
                @if($canView('Report Management'))
                    <li class="menu-title"><span>Reports & Analytics</span></li>
                    <li class="{{ request()->routeIs('sales.report') ? 'active' : '' }}">
                        <a href="{{ route('sales.report') }}" class="{{ request()->routeIs('sales.report') ? 'active' : '' }}">
                            <i class="fe fe-file-text"></i> <span> Sales Report</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('purchase.report') ? 'active' : '' }}">
                        <a href="{{ route('purchase.report') }}" class="{{ request()->routeIs('purchase.report') ? 'active' : '' }}">
                            <i class="fe fe-file-text"></i> <span> Purchase Report</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('revenues.*') ? 'active' : '' }}">
                        <a href="{{ route('revenues.index') }}" class="{{ request()->routeIs('revenues.*') ? 'active' : '' }}">
                            <i class="fe fe-bar-chart-2"></i> <span> Revenue Report</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('sales.extra-charges-report*') ? 'active' : '' }}">
                        <a href="{{ route('sales.extra-charges-report') }}" class="{{ request()->routeIs('sales.extra-charges-report*') ? 'active' : '' }}">
                            <i class="fe fe-truck"></i> <span> Extra Charges Report</span>
                        </a>
                    </li>
                @endif

                <!-- 8. System & Settings -->
                @if($canView('Company Management') || $canView('Administration'))
                    <li class="menu-title"><span>System & Settings</span></li>

                    @if($canView('Company Management'))
                        <li class="{{ request()->routeIs('company-details.*') ? 'active' : '' }}">
                            <a href="{{ route('company-details.index') }}" class="{{ request()->routeIs('company-details.*') ? 'active' : '' }}">
                                <i class="fe fe-briefcase"></i> <span> Company Profile</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Administration'))
                        <li class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i class="fe fe-user"></i> <span> Users</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('role.*') ? 'active' : '' }}">
                            <a href="{{ route('role.index') }}" class="{{ request()->routeIs('role.*') ? 'active' : '' }}">
                                <i class="fe fe-shield"></i> <span> Roles</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('permission.*') ? 'active' : '' }}">
                            <a href="{{ route('permission.index') }}" class="{{ request()->routeIs('permission.*') ? 'active' : '' }}">
                                <i class="fe fe-lock"></i> <span> Permissions</span>
                            </a>
                        </li>
                    @endif
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