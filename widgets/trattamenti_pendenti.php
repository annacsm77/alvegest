<?php
// widgets/trattamenti_pendenti.php
echo '<link rel="stylesheet" href="' . TPL_URL . 'widget_style.css?v=' . time() . '">';

// 1. Identificazione della fase di trattamento aperta
$sql_fase = "SELECT TP_ID, TP_DESCR FROM TR_PFASE WHERE TP_CHIU IS NULL LIMIT 1";
$res_fase = $conn->query($sql_fase);

if ($res_fase && $res_fase->num_rows > 0) {
    $fase = $res_fase->fetch_assoc();
    $id_fase = $fase['TP_ID'];
    $descr_fase = !empty($fase['TP_DESCR']) ? $fase['TP_DESCR'] : "Fase " . $id_fase;

    // 2. Recupero elenco arnie mancanti con AR_attenzione e AR_posizione
    $sql_dettaglio = "
        SELECT A.AR_ID, A.AR_CODICE, A.AR_posizione, A.AR_attenzione, L.AI_LUOGO 
        FROM AP_Arnie A
        LEFT JOIN TA_Apiari L ON A.AR_LUOGO = L.AI_ID
        WHERE A.AR_ATTI = 0 
        AND A.AR_ID NOT IN (
            SELECT F.TF_ARNIA FROM TR_FFASE F WHERE F.TF_PFASE = $id_fase
        )
        ORDER BY L.AI_LUOGO ASC, A.AR_posizione ASC, A.AR_CODICE ASC";
    
    $res_dettaglio = $conn->query($sql_dettaglio);
    $apiari_arnie = []; $totale_mancanti = 0;

    if ($res_dettaglio) {
        while ($row = $res_dettaglio->fetch_assoc()) {
            $luogo = !empty($row['AI_LUOGO']) ? $row['AI_LUOGO'] : "Apiario non definito";
            $apiari_arnie[$luogo][] = [
                'id' => $row['AR_ID'],
                'codice' => $row['AR_CODICE'],
                'attenzione' => $row['AR_attenzione']
            ];
            $totale_mancanti++;
        }
    }

    if ($totale_mancanti > 0): ?>
        <div class="widget-card draggable" data-filename="trattamenti_pendenti.php" id="trattamenti_pendenti" 
             style="position: absolute !important; width: <?php echo $w_width; ?>; height: <?php echo $w_height; ?>; left: <?php echo $w_x; ?>; top: <?php echo $w_y; ?>;">
        
            <div class="widget-header">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 0.75em; color: #d9534f; font-weight: bold; text-transform: uppercase;">Trattamenti Pendenti</span>
                    <h4 style="margin:0; color:#333; font-size: 0.9em;">Mancanti: <?php echo $totale_mancanti; ?></h4>
                </div>
                <span class="drag-handle">⠿</span>
            </div>
            
            <div style="flex-grow: 1; overflow-y: auto; padding: 2px;">
                <p style="margin-bottom: 5px; color: #666; font-size: 0.8em; font-style: italic; border-bottom: 1px solid #f0f0f0;">
                    <?php echo htmlspecialchars($descr_fase); ?>
                </p>

                <?php foreach ($apiari_arnie as $apiario => $arnie): ?>
                    <div style="margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #f9f9f9;">
                        <strong style="color: #472014; font-size: 0.85em; display: block; margin-bottom: 2px;">📍 <?php echo htmlspecialchars($apiario); ?></strong>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <?php foreach ($arnie as $a): 
                                $classe = ($a['attenzione'] == 1) ? 'widget-badge-attenzione' : 'widget-badge-arnia';
                            ?>
                                <a href="pages/gestatt.php?arnia_id=<?php echo $a['id']; ?>&tab=movimenti#tab-movimenti" class="<?php echo $classe; ?>">
                                    <?php echo $a['codice']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; 
} ?>