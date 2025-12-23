<nav>
    <ul>
        <li><a href="<?php echo url('index.php'); ?>">Home</a></li>
        
        <li class="dropdown">
            <a href="javascript:void(0)" class="dropbtn">Tabelle</a>
            <div class="dropdown-content">
                <a href="<?php echo url('pages/attivita.php'); ?>">Tipologie Attività</a>
                <a href="<?php echo url('pages/apicoltore.php'); ?>">Apicoltore</a>
                <a href="<?php echo url('pages/apiari.php'); ?>">Apiari</a>
                <a href="<?php echo url('pages/conf_gob.php'); ?>">Configurazione</a>
            </div>
        </li>
        
        <li class="dropdown">
            <a href="javascript:void(0)" class="dropbtn">Inserimenti</a>
            <div class="dropdown-content">
                <a href="<?php echo url('mobile.php'); ?>">📱 Inserisci Attività</a>
                <a href="<?php echo url('spostamenti.php'); ?>">🚚 Spostamento Arnie</a>
            </div>
        </li>

        <li class="dropdown">
            <a href="javascript:void(0)" class="dropbtn">Trattamenti</a>
            <div class="dropdown-content">
                <a href="<?php echo url('pages/gesttratt.php'); ?>">Storico Trattamenti</a> 
                <a href="<?php echo url('pages/fasetratt.php'); ?>">Fase Trattamenti</a>
                <a href="<?php echo url('pages/scadenziario.php'); ?>">Scadenziario</a>
            </div>
        </li>

      <li class="dropdown">
    <a href="javascript:void(0)" class="dropbtn">Magazzino</a>
    <div class="dropdown-content">
        <a href="<?php echo url('pages/ta_mag.php'); ?>">Tabelle Magazzino</a>
        <a href="<?php echo url('pages/ma_articoli.php'); ?>">Articoli</a>
        <a href="<?php echo url('pages/ma_movimenti.php'); ?>">Movimenti Magazzino</a>
    </div>
</li>
        
        <li><a href="<?php echo url('pages/arnie.php'); ?>">Arnie</a></li> 
        <li><a href="<?php echo url('pages/gestatt.php'); ?>">Gestione Attività</a></li>
        <li><a href="<?php echo url('pages/storico_spostamenti.php'); ?>">Storico Spostamenti</a></li>
    </ul>
</nav>