<?php include_once MODX_MANAGER_PATH . 'includes/header.inc.php' ?>
@stack('styles')
<div class="container-fluid">
    <h1><i class="fa fa-boxes"></i> Package Navigator</h1>
    
    @yield('buttons')
    
    <div class="sectionBody">
        <div class="tab-pane" id="Panel">
            <script type="text/javascript">
                var tpModule = new WebFXTabPane(document.getElementById('Panel'), true);
            </script>
            @yield('content')
        </div>
    </div>
</div>

@stack('scripts')
<?php include_once MODX_MANAGER_PATH . 'includes/footer.inc.php' ?>