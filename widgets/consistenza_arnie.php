<?php
// widgets/consistenza_arnie.php
echo '<link rel="stylesheet" href="' . TPL_URL . 'widget_style.css?v=' . time() . '">';

// 1. Recupero dati incluse posizione e attenzione
$sql_consistenza = "
    SELECT A.AR_ID, A.AR_CODICE, A.AR_posizione, A.AR_attenzione, L.AI_LUOGO 
    FROM AP_Arnie A
    LEFT JOIN TA_Apiari L ON A.AR_LUOGO = L.AI_ID
    WHERE A.AR_ATTI = 0 
    ORDER BY L.AI_LUOGO ASC, A.AR_posizione ASC, A.AR_CODICE ASC";

$res_consistenza = $conn->query($sql_consistenza);
$apiari_totali = []; $totale_arnie_attive = 0;

if ($res_consistenza) {
    while ($row = $res_consistenza->fetch_assoc()) {
        $luogo = !empty($row['AI_LUOGO']) ? $row['AI_LUOGO'] : "Senza Apiario";
        $apiari_totali[$luogo][] = [
            'id' => $row['AR_ID'],
            'codice' => $row['AR_CODICE'],
            'attenzione' => $row['AR_attenzione']
        ];
        $totale_arnie_attive++;
    }
}

if ($totale_arnie_attive > 0): ?>
    <div class="widget-card draggable" data-filename="consistenza_arnie.php" id="consistenza_arnie" 
         style="position: absolute !important; width: <?php echo $w_width; ?>; height: <?php echo $w_height; ?>; left: <?php echo $w_x; ?>; top: <?php echo $w_y; ?>;">
    
        <div class="widget-header">
            <div style="display: flex; flex-direction: column;">
                <span style="font-size: 0.75em; color: #666; font-weight: bold; text-transform: uppercase;">Situazione Apiari</span>
                <h4 style="margin:0; color:#333; font-size: 0.9em;">Totale: <?php echo $totale_arnie_attive; ?></h4>
            </div>
            <span class="drag-handle">⠿</span>
        </div>
        
        <div style="flex-grow: 1; overflow-y: auto; padding: 2px;">
            <?php foreach ($apiari_totali as $apiario => $arnie): ?>
                <div style="margin-bottom: 12px; border-bottom: 1px solid #f0f0f0; padding-bottom: 6px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <strong style="color: #e12326; font-size: 0.85em;">🏠 <?php echo htmlspecialchars($apiario); ?></strong>
                        <span style="font-size: 0.75em; background: #eee; padding: 0 4px; border-radius: 8px;"><?php echo count($arnie); ?></span>
                    </div>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end;">
                        <?php foreach ($arnie as $a): 
                            $classe = ($a['attenzione'] == 1) ? 'widget-badge-attenzione' : 'widget-badge-arnia';
                            // Colore del bordo per i melari basato sulla classe
                            $border_color = ($a['attenzione'] == 1) ? '#b31b1d' : '#c8e6c9';
                        ?>
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <div style="display: flex; flex-direction: column; gap: 1px; margin-bottom: 1px;">
                                    <span style="width: 20px; height: 3px; background: #eee; border: 1px solid <?php echo $border_color; ?>; border-radius: 1px;"></span>
                                    <span style="width: 20px; height: 3px; background: #eee; border: 1px solid <?php echo $border_color; ?>; border-radius: 1px;"></span>
                                    <span style="width: 20px; height: 3px; background: #eee; border: 1px solid <?php echo $border_color; ?>; border-radius: 1px;"></span>
                                </div>
                                
                                <a href="pages/gestatt.php?arnia_id=<?php echo $a['id']; ?>&tab=movimenti#tab-movimenti" 
                                   class="<?php echo $classe; ?>" 
                                   style="padding: 1px 4px; font-size: 0.8em; min-width: 32px;">
                                    <?php echo $a['codice']; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>