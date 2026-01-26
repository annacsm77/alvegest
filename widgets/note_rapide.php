<?php
// widgets/note_rapide.php
$utente_id = $_SESSION['user_id'] ?? 0;

$sql_notes = "SELECT * FROM CF_NOTE_WIDGET 
              WHERE NW_UTENTE_ID = 0 OR NW_UTENTE_ID = $utente_id 
              ORDER BY NW_DATA DESC";
$res_notes = $conn->query($sql_notes);
$note_list = [];
if ($res_notes) {
    while ($row = $res_notes->fetch_assoc()) { $note_list[] = $row; }
}
?>

<div class="widget-card draggable widget-postit" data-file="note_rapide.php" 
     style="position: absolute; width: <?php echo $w_width; ?>; height: <?php echo $w_height; ?>; left: <?php echo $w_x; ?>; top: <?php echo $w_y; ?>; display: flex !important; flex-direction: column !important;">
    
    <div class="widget-header" style="flex-shrink: 0; margin-bottom: 0 !important;">
        <h3 style="font-size: 1.1em; text-align: left; margin: 0; flex-grow: 1;">NOTE</h3>
        <div style="display: flex; gap: 5px; align-items: center;">
            <button onclick="switchToAddNote(this)" class="widget-badge-arnia" title="Nuova">+</button>
            <button onclick="editCurrentNote(this)" class="widget-badge-arnia">✏️</button>
            <button onclick="deleteCurrentNote(this)" class="widget-badge-arnia" style="background: #e12326 !important; color: white !important;">✖</button>
            <span class="drag-handle">⠿</span>
        </div>
    </div>

    <div class="note-tabs-header">
        <?php foreach ($note_list as $index => $n): ?>
            <div class="note-tab-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                 id="tab-btn-<?php echo $n['NW_ID']; ?>"
                 data-id="<?php echo $n['NW_ID']; ?>"
                 data-titolo="<?php echo htmlspecialchars($n['NW_TITOLO']); ?>"
                 data-testo="<?php echo htmlspecialchars($n['NW_CONTENUTO']); ?>"
                 data-dest="<?php echo $n['NW_UTENTE_ID']; ?>"
                 onclick="showSpecificNote(this, 'note-body-<?php echo $n['NW_ID']; ?>')">
                <?php echo htmlspecialchars(mb_substr($n['NW_TITOLO'], 0, 6)) . (mb_strlen($n['NW_TITOLO']) > 6 ? '..' : ''); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="note-body-scroll">
        <div id="tab-note-form" class="note-body-content" style="display:none;">
            <input type="hidden" id="form_note_id" value="">
            <input type="text" id="form_note_title" placeholder="Titolo..." style="width:100%; font-weight:bold; background:transparent; border:none; border-bottom:1px solid #8b4513; margin-bottom:10px;">
            <textarea id="form_note_text" placeholder="Messaggio..." style="width:100%; height:100px; border:none; background:rgba(255,255,255,0.3); resize:vertical;"></textarea>
            <div style="display:flex; justify-content: space-between; margin-top:10px;">
                <select id="form_note_dest"><option value="<?php echo $utente_id; ?>">Privata</option><option value="0">Pubblica</option></select>
                <button onclick="salvaNotaDefinitiva()" class="widget-badge-arnia" style="background:#8b4513 !important; color:white !important;">SALVA</button>
            </div>
        </div>

        <?php foreach ($note_list as $index => $n): ?>
            <div id="note-body-<?php echo $n['NW_ID']; ?>" class="note-body-content" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                <span class="widget-badge-arnia note-badge-date"><?php echo date('d/m/Y H:i', strtotime($n['NW_DATA'])); ?></span>
                <div style="font-weight: bold; font-size: 1.1em; color: #8b4513; margin: 5px 0;"><?php echo htmlspecialchars($n['NW_TITOLO']); ?></div>
                <p class="note-text" style="white-space: pre-wrap;"><?php echo htmlspecialchars($n['NW_CONTENUTO']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function showSpecificNote(btn, bodyId) {
    const widget = btn.closest('.widget-card');
    widget.querySelectorAll('.note-body-content').forEach(c => c.style.display = 'none');
    widget.querySelectorAll('.note-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(bodyId).style.display = 'block';
    btn.classList.add('active');
}

function switchToAddNote(btn) {
    const widget = btn.closest('.widget-card');
    widget.querySelectorAll('.note-body-content').forEach(c => c.style.display = 'none');
    document.getElementById('form_note_id').value = ""; // Svuota ID per nuovo inserimento
    document.getElementById('form_note_title').value = "";
    document.getElementById('form_note_text').value = "";
    document.getElementById('tab-note-form').style.display = 'block';
}

function editCurrentNote(btn) {
    const widget = btn.closest('.widget-card');
    const activeTab = widget.querySelector('.note-tab-btn.active');
    if (!activeTab) return;
    widget.querySelectorAll('.note-body-content').forEach(c => c.style.display = 'none');
    document.getElementById('form_note_id').value = activeTab.getAttribute('data-id');
    document.getElementById('form_note_title').value = activeTab.getAttribute('data-titolo');
    document.getElementById('form_note_text').value = activeTab.getAttribute('data-testo');
    document.getElementById('form_note_dest').value = activeTab.getAttribute('data-dest');
    document.getElementById('tab-note-form').style.display = 'block';
}

function deleteCurrentNote(btn) {
    const activeTab = btn.closest('.widget-card').querySelector('.note-tab-btn.active');
    if (!activeTab) return;
    if (confirm("Eliminare la nota selezionata?")) {
        fetch('includes/ajax/save_new_note.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: activeTab.getAttribute('data-id') })
        }).then(() => location.reload()); // Reload forzato per pulire i tab
    }
}

function salvaNotaDefinitiva() {
    const data = {
        id: document.getElementById('form_note_id').value,
        titolo: document.getElementById('form_note_title').value,
        testo: document.getElementById('form_note_text').value,
        dest: document.getElementById('form_note_dest').value
    };
    if (!data.titolo || !data.testo) return;
    fetch('includes/ajax/save_new_note.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(() => location.reload());
}
</script>