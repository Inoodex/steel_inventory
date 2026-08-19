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

                {{-- ===== 1. DASHBOARD ===== --}}
                <li class="{{ $active(['index','dashboard']) ? 'active' : '' }}">
                    <a href="{{ route('index') }}">
                        <i class="fe fe-grid"></i><span> Dashboard</span>
                    </a>
                </li>

                {{-- ===== 2. SALES & COMMERCIAL ===== --}}
                @if($canView('Sales Management') || $canView('Customer Management') || $canView('Payment Management'))
                    <li class="menu-title"><span>Commercial &amp; Sales</span></li>

                    @if($canView('Sales Management'))
                        <li class="submenu {{ $active(['sales.create','sales.index','sales.show','sales.edit','sales.invoice','returns.*']) ? 'active' : '' }}">
                            <a href="javascript:void(0)">
                                <i class="fe fe-shopping-bag"></i><span> Sales Orders</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul style="{{ $active(['sales.create','sales.index','sales.show','sales.edit','sales.invoice','returns.*']) ? 'display:block' : '' }}">
                                <li class="{{ $active(['sales.create']) ? 'active' : '' }}">
                                    <a href="{{ route('sales.create') }}"><i class="fe fe-plus-circle"></i> New Sale Order</a>
                                </li>
                                <li class="{{ $active(['sales.index','sales.show','sales.edit','sales.invoice']) ? 'active' : '' }}">
                                    <a href="{{ route('sales.index') }}"><i class="fe fe-list"></i> Sales Orders List</a>
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

                    @if($canView('Payment Management') || $canView('Sales Management') || $canView('Customer Management'))
                        <li class="{{ $active(['due-payments.*']) ? 'active' : '' }}">
                            <a href="{{ route('due-payments.index') }}">
                                <i class="fe fe-user-check"></i><span> Customer Dues</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ===== 3. PROCUREMENT & PURCHASING ===== --}}
                @if($canView('Purchase Management') || $canView('Vendor Management'))
                    <li class="menu-title"><span>Procurement &amp; Purchases</span></li>

                    @if($canView('Purchase Management'))
                        <li class="submenu {{ $active(['purchase.create','purchase.index','purchase.show','purchase.edit','lots.*']) ? 'active' : '' }}">
                            <a href="javascript:void(0)">
                                <i class="fe fe-shopping-cart"></i><span> Purchases</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul style="{{ $active(['purchase.create','purchase.index','purchase.show','purchase.edit','lots.*']) ? 'display:block' : '' }}">
                                <li class="{{ $active(['purchase.create']) ? 'active' : '' }}">
                                    <a href="{{ route('purchase.create') }}"><i class="fe fe-plus-circle"></i> New Purchase</a>
                                </li>
                                <li class="{{ $active(['purchase.index','purchase.show','purchase.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('purchase.index') }}"><i class="fe fe-list"></i> Purchase Orders</a>
                                </li>
                                <li class="{{ $active(['lots.*']) ? 'active' : '' }}">
                                    <a href="{{ route('lots.index') }}"><i class="fe fe-layers"></i> Ship Lots Registry</a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if($canView('Vendor Management'))
                        <li class="{{ $active(['vendors.*']) ? 'active' : '' }}">
                            <a href="{{ route('vendors.index') }}">
                                <i class="fe fe-truck"></i><span> Vendors / Suppliers</span>
                            </a>
                        </li>
                    @endif

                    @if($canView('Payment Management') || $canView('Purchase Management') || $canView('Vendor Management'))
                        <li class="{{ $active(['vendor-due-payments.*']) ? 'active' : '' }}">
                            <a href="{{ route('vendor-due-payments.index') }}">
                                <i class="fe fe-dollar-sign"></i><span> Vendor Dues</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ===== 4. INVENTORY & STOCK ===== --}}
                @if($canView('Inventory Management'))
                    <li class="menu-title"><span>Inventory &amp; Stock</span></li>
                    <li class="submenu {{ $active(['inventory.*','coils.*','warehouses.*']) ? 'active' : '' }}">
                        <a href="javascript:void(0)">
                            <i class="fe fe-database"></i><span> Stock Management</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $active(['inventory.*','coils.*','warehouses.*']) ? 'display:block' : '' }}">
                            <li class="{{ $active(['inventory.*']) ? 'active' : '' }}">
                                <a href="{{ route('inventory.index') }}"><i class="fe fe-bar-chart-2"></i> Stock Overview</a>
                            </li>
                            <li class="{{ $active(['coils.*']) ? 'active' : '' }}">
                                <a href="{{ route('coils.index') }}"><i class="fe fe-disc"></i> Steel Coils Registry</a>
                            </li>
                            <li class="{{ $active(['warehouses.*']) ? 'active' : '' }}">
                                <a href="{{ route('warehouses.index') }}"><i class="fe fe-map-pin"></i> Warehouses &amp; Yards</a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- ===== 5. ACCOUNTS & FINANCE ===== --}}
                @if($canView('Accounts Management') || $isAdmin)
                    <li class="menu-title"><span>Accounts &amp; Finance</span></li>

                    {{-- Bookkeeping --}}
                    @if($isAdmin)
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

                        {{-- Financial Statements --}}
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
                                    <a href="{{ route('reports.profit-loss') }}"><i class="fe fe-pie-chart"></i> Profit &amp; Loss (P&amp;L)</a>
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

                    {{-- Expenses --}}
                    @if($canView('Accounts Management'))
                        <li class="submenu {{ $active(['dailyExpenses.*','expense-categories.*']) ? 'active' : '' }}">
                            <a href="javascript:void(0)">
                                <i class="fe fe-credit-card"></i><span> Daily Expenses</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul style="{{ $active(['dailyExpenses.*','expense-categories.*']) ? 'display:block' : '' }}">
                                <li class="{{ $active(['dailyExpenses.*']) ? 'active' : '' }}">
                                    <a href="{{ route('dailyExpenses.index') }}"><i class="fe fe-list"></i> Expense Records</a>
                                </li>
                                <li class="{{ $active(['expense-categories.*']) ? 'active' : '' }}">
                                    <a href="{{ route('expense-categories.index') }}"><i class="fe fe-tag"></i> Expense Categories</a>
                                </li>
                            </ul>
                        </li>

                        {{-- Bank & Cash Accounts --}}
                        <li class="{{ $active(['bank-details.*']) ? 'active' : '' }}">
                            <a href="{{ route('bank-details.index') }}">
                                <i class="fe fe-layers"></i><span> Bank &amp; MFS Accounts</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ===== 6. HR & PAYROLL ===== --}}
                @if($canView('Employee Management') || $isAdmin)
                    <li class="menu-title"><span>HR &amp; Staff</span></li>
                    <li class="{{ $active(['employees.*']) ? 'active' : '' }}">
                        <a href="{{ route('employees.index') }}">
                            <i class="fe fe-users"></i><span> Employees</span>
                        </a>
                    </li>
                    <li class="{{ $active(['salary.*']) ? 'active' : '' }}">
                        <a href="{{ route('salary.index') }}">
                            <i class="fe fe-dollar-sign"></i><span> Salary &amp; Payroll</span>
                        </a>
                    </li>
                @endif

                {{-- ===== EMPLOYEE PORTAL (For Employee role / linked staff) ===== --}}
                @if(auth()->check() && (auth()->user()->hasRole('Employee') || auth()->user()->employee))
                    <li class="menu-title"><span>Employee Self-Service</span></li>
                    <li class="submenu {{ $active(['employee.tada.*']) ? 'active' : '' }}">
                        <a href="javascript:void(0)">
                            <i class="fe fe-briefcase"></i><span> My TA/DA Portal</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $active(['employee.tada.*']) ? 'display:block' : '' }}">
                            <li class="{{ $active(['employee.tada.create']) ? 'active' : '' }}">
                                <a href="{{ route('employee.tada.create') }}"><i class="fe fe-plus-circle"></i> Submit TA/DA</a>
                            </li>
                            <li class="{{ $active(['employee.tada.index','employee.tada.edit']) ? 'active' : '' }}">
                                <a href="{{ route('employee.tada.index') }}"><i class="fe fe-list"></i> My Requests</a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- ===== 7. REPORTS & ANALYTICS ===== --}}
                @if($canView('Report Management'))
                    <li class="menu-title"><span>Reports &amp; Analytics</span></li>
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

                {{-- ===== 8. SYSTEM & SETTINGS ===== --}}
                @if($canView('Company Management') || $canView('Administration'))
                    <li class="menu-title"><span>System &amp; Settings</span></li>

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