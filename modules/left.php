<?php
$currentPage = $_SERVER['REQUEST_URI'];
$isSubmenuActive = strpos($currentPage, '/zichtbare-routedagen') !== false || strpos($currentPage, '/leveringswijze') !== false || strpos($currentPage, '/routes-default') !== false;
?>


<div class="buttons-homepage">
    <a href="/verzamelpicklijsten"
        class="loading <?php echo (strpos($currentPage, '/verzamelpicklijsten') !== false) ? 'active' : ''; ?>">
        <i class="fa-solid fa-clipboard-list"></i>Verzamelpicklijsten
    </a>
    <a href="/klanten" class="loading <?php echo (strpos($currentPage, '/klanten') !== false) ? 'active' : ''; ?>">
    <i class="fa-solid fa-users"></i>Klanten
    </a>
    <a href="/routes/" class="loading <?php echo (strpos($currentPage, '/routes/') !== false) ? 'active' : ''; ?>">
    <i class="fa-solid fa-route"></i>Routes
    </a>
    <a href="/picklocaties"
        class="loading <?php echo (strpos($currentPage, '/picklocaties') !== false) ? 'active' : ''; ?>">
        <i class="fa-solid fa-location-dot"></i>Picklocaties
    </a>

    <div class="submenu-wrapper <?php echo $isSubmenuActive ? 'active' : ''; ?>">
        <a href="#" class="loading">
        <i class="fa-solid fa-gear"></i>Instellingen
            <i class="fa-solid fa-chevron-down arrow <?php echo $isSubmenuActive ? 'fa-rotate-180' : ''; ?>"></i>
        </a>
        <div class="submenu <?php echo $isSubmenuActive ? 'visible' : ''; ?>">
            <a href="/zichtbare-routedagen"
                class="<?php echo (strpos($currentPage, '/zichtbare-routedagen') !== false) ? 'active' : ''; ?>">Zichtbare
                routedagen</a>
            <a href="/routes-default"
                class="<?php echo (strpos($currentPage, '/routes-default') !== false) ? 'active' : ''; ?>">Default Routes</a>
            <a href="/leveringswijze"
                class="<?php echo (strpos($currentPage, '/leveringswijze') !== false) ? 'active' : ''; ?>">Leveringswijze</a>
        </div>
    </div>


    <a href="/uitloggen" id="uitloggen"
        class="loading <?php echo (strpos($currentPage, '/uitloggen') !== false) ? 'active' : ''; ?>">
        <i class="fa-solid fa-right-from-bracket"></i> Uitloggen
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const submenuToggles = document.querySelectorAll('.submenu-wrapper > a');
        const submenus = document.querySelectorAll('.submenu');
        const submenuWrappers = document.querySelectorAll('.submenu-wrapper');

        submenuToggles.forEach((toggle, index) => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const submenu = submenus[index];
                const submenuWrapper = submenuWrappers[index];

                submenu.classList.toggle('visible');
                const isVisible = submenu.classList.contains('visible');

                const arrow = toggle.querySelector('.arrow');
                if (isVisible) {
                    arrow.classList.add('fa-rotate-180');
                    submenuWrapper.classList.add('active');
                } else {
                    arrow.classList.remove('fa-rotate-180');
                    submenuWrapper.classList.remove('active');
                }
            });
        });

        document.addEventListener('click', function() {
            submenus.forEach(submenu => submenu.classList.remove('visible'));
            submenuWrappers.forEach(wrapper => wrapper.classList.remove('active'));
            submenuToggles.forEach(toggle => toggle.querySelector('.arrow').classList.remove('fa-rotate-180'));
        });

        submenus.forEach(submenu => {
            submenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    });
</script>
