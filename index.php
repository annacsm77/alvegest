<?php
// Versione del file: H.0.8 (Movimento Vincolato all'area)
include 'includes/config.php'; 
include TPL_PATH . 'header.php'; 
?>

<div class="main-content flex-layout">
    <div class="left-column"></div>
    <div class="center-column">
        <main>
            <h2>Dashboard Apiario</h2>
            <div class="widget-grid" id="grid-container" style="position: relative; width: 100%; min-height: 85vh; padding: 20px; border: 1px dashed #ccc; overflow: hidden;">
                <?php
                $config_widgets = [];
                $res_pos = $conn->query("SELECT * FROM CF_WIDGET_POS");
                if ($res_pos) {
                    while($row = $res_pos->fetch_assoc()){
                        $config_widgets[$row['WP_WIDGET_NAME']] = $row;
                    }
                }

                $widget_folder = __DIR__ . '/widgets/';
                if (is_dir($widget_folder)) {
                    $all_files = glob($widget_folder . "*.php");
                    foreach ($all_files as $file) {
                        $p_name = basename($file);
                        $w_width  = $config_widgets[$p_name]['WP_WIDTH'] ?? '300px';
                        $w_height = $config_widgets[$p_name]['WP_HEIGHT'] ?? '250px';
                        $w_x      = $config_widgets[$p_name]['WP_X'] ?? '10px';
                        $w_y      = $config_widgets[$p_name]['WP_Y'] ?? '10px';
                        include($file);
                    }
                }
                ?>
            </div>
        </main>
    </div>
    <div class="right-column"></div>
</div>

<footer><?php include TPL_PATH . 'footer.php'; ?></footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let activeWidget = null;
    let startX, startY, initialLeft, initialTop;
    const grid = document.getElementById('grid-container');

    function salvaStato(widget) {
        const dati = {
            file: widget.getAttribute('data-filename'),
            width: widget.style.width || (widget.offsetWidth + 'px'),
            height: widget.style.height || (widget.offsetHeight + 'px'),
            x: widget.style.left,
            y: widget.style.top
        };
        fetch('includes/ajax/save_widget_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dati)
        });
    }

    document.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('drag-handle')) {
            activeWidget = e.target.closest('.widget-card');
            if (activeWidget) {
                startX = e.clientX;
                startY = e.clientY;
                initialLeft = parseInt(activeWidget.style.left) || 0;
                initialTop = parseInt(activeWidget.style.top) || 0;
                activeWidget.style.zIndex = 1000;
                e.preventDefault();
            }
        }
    });

    document.addEventListener('mousemove', function(e) {
        if (!activeWidget) return;

        let deltaX = e.clientX - startX;
        let deltaY = e.clientY - startY;

        // Calcolo nuova posizione potenziale [cite: 2025-11-21]
        let newX = initialLeft + deltaX;
        let newY = initialTop + deltaY;

        // LIMITI DI AREA (CONTAINMENT) [cite: 2025-11-21]
        // Impedisce di uscire a sinistra e in alto
        if (newX < 0) newX = 0;
        if (newY < 0) newY = 0;

        // Impedisce di uscire a destra e in basso rispetto alla griglia
        const maxRight = grid.clientWidth - activeWidget.offsetWidth;
        const maxBottom = grid.clientHeight - activeWidget.offsetHeight;

        if (newX > maxRight) newX = maxRight;
        if (newY > maxBottom) newY = maxBottom;

        activeWidget.style.left = newX + 'px';
        activeWidget.style.top = newY + 'px';
    });

    document.addEventListener('mouseup', function() {
        if (activeWidget) {
            salvaStato(activeWidget);
            activeWidget.style.zIndex = 100;
            activeWidget = null;
        }
    });

    const resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            if (window.resizeInitDone) {
                clearTimeout(window.resizeTimeout);
                window.resizeTimeout = setTimeout(() => {
                    salvaStato(entry.target);
                }, 1000);
            }
        }
    });

    document.querySelectorAll('.widget-card').forEach(w => resizeObserver.observe(w));
    setTimeout(() => { window.resizeInitDone = true; }, 1000);
});
</script>