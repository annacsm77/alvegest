<?php
// widgets/calendario_fioriture.php

// Caricamento CSS specifico per i widget
echo '<link rel="stylesheet" href="' . TPL_URL . 'widget_style.css?v=' . time() . '">';

// 1. Recupero le fioriture future o attuali (dalla data di oggi in poi per la fine fioritura)
$oggi = date('Y-m-d');
$sql_fioriture = "
    SELECT F.FI_CODICE, A.AI_LUOGO, AF.FA_inizio, AF.FA_fine
    FROM TA_APIFIO AF
    JOIN TA_Fioriture F ON AF.FA_CodFio = F.FI_id
    JOIN TA_Apiari A ON AF.FA_COD_API = A.AI_ID
    WHERE AF.FA_fine >= '$oggi' OR AF.FA_fine IS NULL
    ORDER BY AF.FA_inizio ASC
    LIMIT 10";

$res_fio = $conn->query($sql_fioriture);
?>

<div class="widget-card draggable" data-filename="calendario_fioriture.php" id="calendario_fioriture" 
     style="position: absolute !important; width: <?php echo $w_width; ?>; height: <?php echo $w_height; ?>; left: <?php echo $w_x; ?>; top: <?php echo $w_y; ?>;">
    
    <div class="widget-header">
        <div style="display: flex; flex-direction: column;">
            <span style="font-size: 0.75em; color: #666; font-weight: bold; text-transform: uppercase;">Prossime Fioriture</span>
            <h4 style="margin:0; color:#333; font-size: 0.9em;">Calendario Nomadi</h4>
        </div>
        <span class="drag-handle">⠿</span>
    </div>

    <div style="flex-grow: 1; overflow-y: auto; padding: 5px;">
        <?php if ($res_fio && $res_fio->num_rows > 0): ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85em;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #eee; color: #666;">
                        <th style="padding: 4px;">Inizio</th>
                        <th style="padding: 4px;">Fioritura</th>
                        <th style="padding: 4px;">Luogo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($f = $res_fio->fetch_assoc()): 
                        $data_inizio = ($f['FA_inizio']) ? date('d/m', strtotime($f['FA_inizio'])) : '--/--';
                        $in_corso = ($f['FA_inizio'] <= $oggi && ($f['FA_fine'] >= $oggi || $f['FA_fine'] == null));
                    ?>
                        <tr style="border-bottom: 1px solid #f9f9f9; <?php echo $in_corso ? 'background-color: #fff9c4;' : ''; ?>">
                            <td style="padding: 6px 4px; font-weight: bold; color: #e12326;">
                                <?php echo $data_inizio; ?>
                            </td>
                            <td style="padding: 6px 4px;">
                                <span class="widget-badge-arnia" style="background-color: #472014 !important; color: white !important; border: none !important;">
                                    <?php echo htmlspecialchars($f['FI_CODICE']); ?>
                                </span>
                            </td>
                            <td style="padding: 6px 4px; color: #333;">
                                <?php echo htmlspecialchars($f['AI_LUOGO']); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #999; margin-top: 20px;">Nessuna fioritura in programma.</p>
        <?php endif; ?>
    </div>

    <div style="margin-top: 10px; text-align: right;">
        <a href="pages/fioriture.php" style="font-size: 0.75em; color: #e12326; text-decoration: none; font-weight: bold;">Gestisci Fioriture &rarr;</a>
    </div>
</div>