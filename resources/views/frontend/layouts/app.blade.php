<!DOCTYPE html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="sm-hover"
    data-sidebar-image="none">

@include('frontend.layouts.head')

<body class="mini-sidebar">

    <!-- Main Wrapper -->
    <div class="main-wrapper">

        @include('frontend.layouts.header')

        @include('frontend.layouts.sidebar')
        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid" style="margin:0; padding-bottom:0;">
                <!-- Alerts -->
                @include('layouts.flash-message')
                <!-- /Alerts -->
            </div>
            @yield('content')
        </div>
        <!-- /Page Wrapper -->

    </div>
    <!-- /Main Wrapper -->

    @include('frontend.layouts.right_sidebar')

    <!-- Bootstrap Core JS -->
    <script src="{{asset('assets')}}/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS -->
    <script src="{{ asset('assets') }}/plugins/select2/js/select2.min.js"></script>

    <!-- Feather Icon JS -->
    <script src="{{asset('assets')}}/js/feather.min.js"></script>

    <!-- Slimscroll JS -->
    <script src="{{asset('assets')}}/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Chart JS -->
    <script src="{{asset('assets')}}/plugins/apexchart/apexcharts.min.js"></script>
    <script src="{{asset('assets')}}/plugins/apexchart/chart-data.js"></script>

    <!-- Theme Settings JS -->
    <script src="{{asset('assets')}}/js/theme-settings.js"></script>
    <script src="{{asset('assets')}}/js/greedynav.js"></script>

    <!-- Custom JS -->
    <script src="{{asset('assets')}}/js/script.js"></script>

    <script>
        $(document).ready(function() {
            // Mini-Sidebar Hover to Expand & Auto-Collapse on Mouse Leave
            if ($(window).width() >= 992) {
                $('body').addClass('mini-sidebar');

                $('#sidebar, .header .main-logo').on('mouseenter', function() {
                    $('body').addClass('expand-menu');
                });

                $('#sidebar, .header .main-logo').on('mouseleave', function() {
                    $('body').removeClass('expand-menu');
                    // Collapse any opened non-active submenus when mouse leaves
                    $('#sidebar-menu .submenu:not(.active) > ul').slideUp(150);
                    $('#sidebar-menu .submenu:not(.active) > a').removeClass('subdrop');
                });

                // Auto collapse when clicking an active menu link and moving away
                $('#sidebar-menu a').on('click', function() {
                    var href = $(this).attr('href');
                    if (href && href !== 'javascript:void(0);' && href !== '#' && href !== 'javascript:void(0)') {
                        $('body').removeClass('expand-menu');
                    }
                });
            }

            if ($.fn.select2) {
                $('.select2').each(function() {
                    const $select = $(this);
                    const $modal = $select.closest('.modal');
                    
                    $select.select2({
                        width: '100%',
                        dropdownParent: $modal.length ? $modal : $(document.body)
                    });
                });
            }

            // Ensure all 3-dot dropdowns in tables float with fixed positioning strategy
            $('[data-bs-toggle="dropdown"]').each(function() {
                if (!$(this).attr('data-bs-popper-config')) {
                    $(this).attr('data-bs-popper-config', '{"strategy":"fixed"}');
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
