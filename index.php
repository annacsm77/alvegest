<?php
// Versione: H.1.5 - Supporto Visibilità Widget
include 'includes/config.php'; 
include TPL_PATH . 'header.php'; 

$utente_id = $_SESSION['user_id'] ?? 0;
?>

<div class="main-content flex-layout">
    <div class="left-column"></div>
    <div class="center-column">
        <main>
            <h2>Dashboard Apiario</h2>
            <div class="widget-grid" id="grid-container" style="position: relative; width: 100%; min-height: 85vh; padding: 20px; border: 1px dashed #ccc; overflow: hidden; background-color: #f3f3f3;">
                <?php
                $config_widgets = [];
                $res_pos = $conn->query("SELECT * FROM CF_WIDGET_POS WHERE WP_UTENTE_ID = $utente_id");
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
                        
                        // Controllo Visibilità: Mostra se è 1 o se non è ancora nel database
                        $visibile = isset($config_widgets[$p_name]) ? (int)$config_widgets[$p_name]['WP_VISIBILE'] : 1;
                        
                        if ($visibile === 1) {
                            $w_width  = $config_widgets[$p_name]['WP_WIDTH'] ?? '300px';
                            $w_height = $config_widgets[$p_name]['WP_HEIGHT'] ?? '250px';
                            $w_x      = $config_widgets[$p_name]['WP_X'] ?? '10px';
                            $w_y      = $config_widgets[$p_name]['WP_Y'] ?? '10px';
                            
                            include($file);
                        }
                    }
                }
                ?>
            </div>
        </main>
    </div>
    <div class="right-column"></div>
</div>

<script>
    // Logica JavaScript originale per data-file, drag e resize (Invariata)
    document.querySelectorAll('.widget-card').forEach((card, index) => {
        const widgetFiles = <?php 
            // Generiamo l'elenco dei file filtrando solo quelli visibili per mantenere l'ordine
            $visibili_files = [];
            foreach(glob($widget_folder . "*.php") as $f) {
                $bn = basename($f);
                if (!isset($config_widgets[$bn]) || $config_widgets[$bn]['WP_VISIBILE'] == 1) $visibili_files[] = $bn;
            }
            echo json_encode($visibili_files); 
        ?>;
        if(widgetFiles[index]) {
            card.setAttribute('data-file', widgetFiles[index]);
        }
    });

    // ... (Logica Mousedown, Mousemove, Mouseup e Resize Invariata) ...
    const grid = document.getElementById('grid-container');
    let activeWidget = null;
    let initialX, initialY, initialLeft, initialTop;

    document.addEventListener('mousedown', function(e) {
        const handle = e.target.closest('.drag-handle');
        if (handle) {
            activeWidget = handle.closest('.widget-card');
            activeWidget.style.zIndex = 1000;
            initialX = e.clientX; initialY = e.clientY;
            initialLeft = parseInt(activeWidget.style.left) || 0;
            initialTop = parseInt(activeWidget.style.top) || 0;
            e.preventDefault();
        }
    });

    document.addEventListener('mousemove', function(e) {
        if (!activeWidget) return;
        activeWidget.style.left = (initialLeft + (e.clientX - initialX)) + 'px';
        activeWidget.style.top = (initialTop + (e.clientY - initialY)) + 'px';
    });

    document.addEventListener('mouseup', function() {
        if (activeWidget) {
            salvaStato(activeWidget);
            activeWidget.style.zIndex = 100;
            activeWidget = null;
        }
    });

    function salvaStato(widget) {
        const data = {
            file: widget.getAttribute('data-file'),
            width: widget.style.width,
            height: widget.style.height,
            x: widget.style.left,
            y: widget.style.top
        };
        fetch('includes/ajax/save_widget_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    }

    const resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            if (window.resizeInitDone) {
                clearTimeout(window.resizeTimeout);
                window.resizeTimeout = setTimeout(() => { salvaStato(entry.target); }, 500);
            }
        }
    });
    document.querySelectorAll('.widget-card').forEach(w => resizeObserver.observe(w));
    setTimeout(() => { window.resizeInitDone = true; }, 1000);
</script>

<?php include TPL_PATH . 'footer.php'; ?>