-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Gen 07, 2026 alle 17:05
-- Versione del server: 10.11.6-MariaDB
-- Versione PHP: 8.2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `AlveGest`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `AI_SPOS`
--

CREATE TABLE `AI_SPOS` (
  `SP_ID` int(11) NOT NULL,
  `SP_DA` int(11) NOT NULL COMMENT 'ID Apiario di Partenza (FK a TA_Apiari)',
  `SP_A` int(11) NOT NULL COMMENT 'ID Apiario di Arrivo (FK a TA_Apiari)',
  `SP_DATA` date NOT NULL COMMENT 'Data dello Spostamento',
  `SP_ARNIE` text DEFAULT NULL COMMENT 'Codici Arnie Spostate (separati da ;)',
  `SP_TOT` int(11) NOT NULL COMMENT 'Numero Totale Arnie Spostate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `AP_Arnie`
--

CREATE TABLE `AP_Arnie` (
  `AR_ID` int(11) NOT NULL,
  `AR_CODICE` int(11) NOT NULL,
  `AR_NOME` varchar(60) NOT NULL,
  `AR_LUOGO` int(11) DEFAULT NULL,
  `AR_posizione` int(11) NOT NULL DEFAULT 0,
  `AR_PROP` varchar(60) NOT NULL,
  `AR_CREG` char(3) NOT NULL,
  `AR_TREG` varchar(15) NOT NULL,
  `AR_ATTI` tinyint(1) NOT NULL DEFAULT 0,
  `AR_attenzione` tinyint(1) NOT NULL DEFAULT 0,
  `AR_DATA` date DEFAULT current_timestamp(),
  `AR_NUCL` tinyint(1) NOT NULL DEFAULT 0,
  `AR_Note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `AT_FOTO`
--

CREATE TABLE `AT_FOTO` (
  `FO_ID` int(11) NOT NULL COMMENT 'ID Foto',
  `FO_ATT` int(11) NOT NULL COMMENT 'Chiave esterna: ID Attivita (AT_INSATT.IA_ID)',
  `FO_NOME` varchar(255) NOT NULL COMMENT 'Nome file univoco (AR_CODICE + Timestamp)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro delle foto associate alle attività';

-- --------------------------------------------------------

--
-- Struttura della tabella `AT_INSATT`
--

CREATE TABLE `AT_INSATT` (
  `IA_ID` int(11) NOT NULL COMMENT 'Chiave primaria',
  `IA_DATA` date NOT NULL COMMENT 'Data dell''attività (solo data)',
  `IA_CodAr` int(11) NOT NULL COMMENT 'Codice Arnia (FK a AP_Arnie.AR_ID)',
  `IA_ATT` int(11) NOT NULL COMMENT 'Tipo di attività (FK a TA_Attivita.AT_ID)',
  `IA_NOTE` text DEFAULT NULL COMMENT 'Note lunghe sull''attività',
  `IA_PERI` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Pericolo (Boolean)',
  `IA_VREG` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Visita Registrata (Boolean)',
  `IA_OP1` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Opzione 1 (Boolean)',
  `IA_OP2` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Opzione 2 (Boolean)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Tabella di registrazione delle attività svolte in apiario';

-- --------------------------------------------------------

--
-- Struttura della tabella `CF_GLOB`
--

CREATE TABLE `CF_GLOB` (
  `ID` int(11) NOT NULL,
  `CF_DATO` varchar(20) NOT NULL,
  `CF_VAL` varchar(500) NOT NULL,
  `CF_DESCR` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `CF_WIDGET_POS`
--

CREATE TABLE `CF_WIDGET_POS` (
  `WP_ID` int(11) NOT NULL,
  `WP_WIDGET_NAME` varchar(100) NOT NULL,
  `WP_ORDINE` int(11) NOT NULL DEFAULT 0,
  `WP_WIDTH` varchar(20) DEFAULT '300px',
  `WP_HEIGHT` varchar(20) DEFAULT '250px',
  `WP_X` varchar(20) DEFAULT '0px',
  `WP_Y` varchar(20) DEFAULT '0px'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `MA_Articoli`
--

CREATE TABLE `MA_Articoli` (
  `ART_ID` int(11) NOT NULL,
  `ART_Codice` varchar(20) NOT NULL COMMENT 'Codice univoco interno o SKU',
  `ART_Descrizione` varchar(100) NOT NULL COMMENT 'Descrizione completa dell''articolo',
  `ART_Mastro_ID` int(11) NOT NULL COMMENT 'FK a ID della riga TA_MAG (Sottomastro)',
  `ART_UM` char(3) NOT NULL COMMENT 'Unità di Misura (Es: KG, PZ, LT)',
  `ART_PrezzoMedio` decimal(10,2) DEFAULT 0.00 COMMENT 'Prezzo medio di acquisto (per valorizzare stock)',
  `ART_Note` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `MA_MOVI`
--

CREATE TABLE `MA_MOVI` (
  `MV_ID` int(11) NOT NULL,
  `MV_Data` date NOT NULL COMMENT 'Data del movimento',
  `MV_Descrizione` varchar(100) NOT NULL COMMENT 'Causale o descrizione del movimento',
  `MV_MAG_ID` int(11) NOT NULL COMMENT 'FK a TA_MAG (Sottomastro)',
  `MV_Carico` decimal(10,2) DEFAULT 0.00,
  `MV_Scarico` decimal(10,2) DEFAULT 0.00,
  `MV_InsData` timestamp NULL DEFAULT current_timestamp() COMMENT 'Data inserimento record'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TA_Apiari`
--

CREATE TABLE `TA_Apiari` (
  `AI_ID` int(11) NOT NULL,
  `AI_CODICE` varchar(12) NOT NULL DEFAULT '',
  `AI_LUOGO` varchar(60) NOT NULL,
  `AI_NOTE` text NOT NULL,
  `AI_LINK` varchar(2048) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TA_Apicoltore`
--

CREATE TABLE `TA_Apicoltore` (
  `AP_ID` int(11) NOT NULL,
  `AP_Nome` varchar(60) NOT NULL,
  `AP_codap` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TA_ARTICOLI`
--

CREATE TABLE `TA_ARTICOLI` (
  `ID` int(11) NOT NULL,
  `AR_Mastro` int(2) NOT NULL COMMENT 'Codice Mastro (Categoria Padre)',
  `AR_SMastro` int(2) NOT NULL COMMENT 'Codice Sottomastro (Categoria Figlio)',
  `AR_Codice` varchar(20) NOT NULL COMMENT 'Codice univoco interno (es. SCIROPPO-25KG)',
  `AR_Descrizione` varchar(100) NOT NULL,
  `AR_UnitaMisura` varchar(10) NOT NULL COMMENT 'Es: kg, L, Pz, Conf',
  `AR_Note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TA_Attivita`
--

CREATE TABLE `TA_Attivita` (
  `AT_ID` int(11) NOT NULL,
  `AT_DESCR` varchar(60) NOT NULL,
  `AT_NOTE` text DEFAULT NULL,
  `AT_NR` int(11) DEFAULT 0 COMMENT 'Numero di ripetizioni per l attivita',
  `AT_GG` int(11) DEFAULT 0 COMMENT 'Numero di giorni di validita o scadenza per l attivita',
  `AT_TRAT` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag: 1=Trattamento, 0=Non Trattamento',
  `AT_MAG_ID` int(11) DEFAULT NULL,
  `AT_SCARICO_FISSO` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TA_InsAtt`
--

CREATE TABLE `TA_InsAtt` (
  `AV_id` int(11) NOT NULL,
  `AV_IDLU` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TA_MAG`
--

CREATE TABLE `TA_MAG` (
  `ID` int(11) NOT NULL,
  `TM_Mastro` int(2) NOT NULL COMMENT 'Categoria Principale',
  `TM_SMastro` int(2) NOT NULL DEFAULT 0 COMMENT 'Sottocategoria (0 se è un Mastro)',
  `TM_Descrizione` varchar(60) NOT NULL,
  `TM_Note` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TR_FFASE`
--

CREATE TABLE `TR_FFASE` (
  `TF_ID` int(10) NOT NULL COMMENT 'ID autoincrementante (Chiave Primaria)',
  `TF_PFASE` int(10) UNSIGNED NOT NULL COMMENT 'Chiave esterna: ID della Fase Padre (TR_PFASE.TP_ID)',
  `TF_ARNIA` int(10) NOT NULL COMMENT 'Chiave esterna: ID dell arnia (AP_Arnie.AR_ID)',
  `TF_ATT` int(10) NOT NULL COMMENT 'Chiave esterna: ID del Tipo Attività (TA_Attivita.AT_ID) - Il tipo di trattamento',
  `TF_CATT` int(10) NOT NULL COMMENT 'Chiave esterna: ID del record attività (AT_INSATT.IA_ID)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fase Figlio: Dettaglio arnie trattate e attività collegate';

-- --------------------------------------------------------

--
-- Struttura della tabella `TR_PFASE`
--

CREATE TABLE `TR_PFASE` (
  `TP_ID` int(10) UNSIGNED NOT NULL COMMENT 'Codice ID autoincrementante',
  `TP_DAP` date NOT NULL COMMENT 'Data di Apertura (Obbligatoria)',
  `TP_CHIU` date DEFAULT NULL COMMENT 'Data di Chiusura (Opzionale)',
  `TP_STAG` int(4) NOT NULL COMMENT 'Anno della Stagione di Trattamento',
  `TP_DESCR` varchar(500) DEFAULT NULL COMMENT 'Note descrittive sulla fase'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fase Padre: Apertura e chiusura stagione trattamenti';

-- --------------------------------------------------------

--
-- Struttura della tabella `TR_SCAD`
--

CREATE TABLE `TR_SCAD` (
  `SC_ID` int(10) NOT NULL COMMENT 'ID Numero Incrementale',
  `SC_ARNIA` int(10) NOT NULL COMMENT 'Chiave esterna: ID dell arnia (AP_Arnie.AR_ID)',
  `SC_TATT` int(10) NOT NULL COMMENT 'Chiave esterna: ID Tipologia Attività (TA_Attivita.AT_ID)',
  `SC_DINIZIO` date NOT NULL COMMENT 'Data Inizio Trattamento',
  `SC_DATAF` date DEFAULT NULL COMMENT 'Data Fine Trattamento o Scadenza (Opzionale)',
  `SC_CHIUSO` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag: 1=Completato/Chiuso, 0=Attivo/In Corso',
  `SC_AVA` int(10) NOT NULL DEFAULT 1 COMMENT 'Avanzamento del ciclo: 1 = prima fase, 2 = seconda, ecc.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro Trattamenti Attivi e Scadenze';

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `AI_SPOS`
--
ALTER TABLE `AI_SPOS`
  ADD PRIMARY KEY (`SP_ID`),
  ADD KEY `FK_Spos_Da` (`SP_DA`),
  ADD KEY `FK_Spos_A` (`SP_A`);

--
-- Indici per le tabelle `AP_Arnie`
--
ALTER TABLE `AP_Arnie`
  ADD PRIMARY KEY (`AR_ID`),
  ADD KEY `FK_Arnie_Apiari` (`AR_LUOGO`);

--
-- Indici per le tabelle `AT_FOTO`
--
ALTER TABLE `AT_FOTO`
  ADD PRIMARY KEY (`FO_ID`),
  ADD UNIQUE KEY `FO_NOME` (`FO_NOME`),
  ADD KEY `FO_ATT` (`FO_ATT`);

--
-- Indici per le tabelle `AT_INSATT`
--
ALTER TABLE `AT_INSATT`
  ADD PRIMARY KEY (`IA_ID`),
  ADD KEY `FK_InsAtt_Attivita` (`IA_ATT`);

--
-- Indici per le tabelle `CF_GLOB`
--
ALTER TABLE `CF_GLOB`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `CF_DATO_UNIQUE` (`CF_DATO`);

--
-- Indici per le tabelle `CF_WIDGET_POS`
--
ALTER TABLE `CF_WIDGET_POS`
  ADD PRIMARY KEY (`WP_ID`),
  ADD UNIQUE KEY `WP_WIDGET_NAME` (`WP_WIDGET_NAME`);

--
-- Indici per le tabelle `MA_Articoli`
--
ALTER TABLE `MA_Articoli`
  ADD PRIMARY KEY (`ART_ID`),
  ADD UNIQUE KEY `UK_ARTICOLO_CODICE` (`ART_Codice`),
  ADD KEY `FK_ARTICOLO_MASTRO` (`ART_Mastro_ID`);

--
-- Indici per le tabelle `MA_MOVI`
--
ALTER TABLE `MA_MOVI`
  ADD PRIMARY KEY (`MV_ID`),
  ADD KEY `FK_MOVI_MAG` (`MV_MAG_ID`);

--
-- Indici per le tabelle `TA_Apiari`
--
ALTER TABLE `TA_Apiari`
  ADD PRIMARY KEY (`AI_ID`);

--
-- Indici per le tabelle `TA_Apicoltore`
--
ALTER TABLE `TA_Apicoltore`
  ADD PRIMARY KEY (`AP_ID`);

--
-- Indici per le tabelle `TA_ARTICOLI`
--
ALTER TABLE `TA_ARTICOLI`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `AR_Codice` (`AR_Codice`),
  ADD KEY `IDX_ARTICOLI_MAG` (`AR_Mastro`,`AR_SMastro`);

--
-- Indici per le tabelle `TA_Attivita`
--
ALTER TABLE `TA_Attivita`
  ADD PRIMARY KEY (`AT_ID`),
  ADD KEY `FK_ATTIVITA_MAG` (`AT_MAG_ID`);

--
-- Indici per le tabelle `TA_InsAtt`
--
ALTER TABLE `TA_InsAtt`
  ADD PRIMARY KEY (`AV_id`),
  ADD KEY `AV_IDLU` (`AV_IDLU`);

--
-- Indici per le tabelle `TA_MAG`
--
ALTER TABLE `TA_MAG`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UK_MASTRO_SM` (`TM_Mastro`,`TM_SMastro`);

--
-- Indici per le tabelle `TR_FFASE`
--
ALTER TABLE `TR_FFASE`
  ADD PRIMARY KEY (`TF_ID`),
  ADD KEY `fk_tf_pfase` (`TF_PFASE`),
  ADD KEY `fk_tf_arnia` (`TF_ARNIA`),
  ADD KEY `fk_tf_att` (`TF_ATT`),
  ADD KEY `fk_tf_catt` (`TF_CATT`);

--
-- Indici per le tabelle `TR_PFASE`
--
ALTER TABLE `TR_PFASE`
  ADD PRIMARY KEY (`TP_ID`);

--
-- Indici per le tabelle `TR_SCAD`
--
ALTER TABLE `TR_SCAD`
  ADD PRIMARY KEY (`SC_ID`),
  ADD KEY `SC_ARNIA` (`SC_ARNIA`),
  ADD KEY `SC_TATT` (`SC_TATT`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `AI_SPOS`
--
ALTER TABLE `AI_SPOS`
  MODIFY `SP_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `AP_Arnie`
--
ALTER TABLE `AP_Arnie`
  MODIFY `AR_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `AT_FOTO`
--
ALTER TABLE `AT_FOTO`
  MODIFY `FO_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID Foto';

--
-- AUTO_INCREMENT per la tabella `AT_INSATT`
--
ALTER TABLE `AT_INSATT`
  MODIFY `IA_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Chiave primaria';

--
-- AUTO_INCREMENT per la tabella `CF_GLOB`
--
ALTER TABLE `CF_GLOB`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `CF_WIDGET_POS`
--
ALTER TABLE `CF_WIDGET_POS`
  MODIFY `WP_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `MA_Articoli`
--
ALTER TABLE `MA_Articoli`
  MODIFY `ART_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `MA_MOVI`
--
ALTER TABLE `MA_MOVI`
  MODIFY `MV_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `TA_Apiari`
--
ALTER TABLE `TA_Apiari`
  MODIFY `AI_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `TA_Apicoltore`
--
ALTER TABLE `TA_Apicoltore`
  MODIFY `AP_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `TA_ARTICOLI`
--
ALTER TABLE `TA_ARTICOLI`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `TA_Attivita`
--
ALTER TABLE `TA_Attivita`
  MODIFY `AT_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `TA_InsAtt`
--
ALTER TABLE `TA_InsAtt`
  MODIFY `AV_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `TA_MAG`
--
ALTER TABLE `TA_MAG`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `TR_FFASE`
--
ALTER TABLE `TR_FFASE`
  MODIFY `TF_ID` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID autoincrementante (Chiave Primaria)';

--
-- AUTO_INCREMENT per la tabella `TR_PFASE`
--
ALTER TABLE `TR_PFASE`
  MODIFY `TP_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Codice ID autoincrementante';

--
-- AUTO_INCREMENT per la tabella `TR_SCAD`
--
ALTER TABLE `TR_SCAD`
  MODIFY `SC_ID` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID Numero Incrementale';

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `AI_SPOS`
--
ALTER TABLE `AI_SPOS`
  ADD CONSTRAINT `FK_Spos_A` FOREIGN KEY (`SP_A`) REFERENCES `TA_Apiari` (`AI_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Spos_Da` FOREIGN KEY (`SP_DA`) REFERENCES `TA_Apiari` (`AI_ID`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `AP_Arnie`
--
ALTER TABLE `AP_Arnie`
  ADD CONSTRAINT `FK_Arnie_Apiari` FOREIGN KEY (`AR_LUOGO`) REFERENCES `TA_Apiari` (`AI_ID`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `AT_FOTO`
--
ALTER TABLE `AT_FOTO`
  ADD CONSTRAINT `AT_FOTO_ibfk_1` FOREIGN KEY (`FO_ATT`) REFERENCES `AT_INSATT` (`IA_ID`) ON DELETE CASCADE;

--
-- Limiti per la tabella `AT_INSATT`
--
ALTER TABLE `AT_INSATT`
  ADD CONSTRAINT `FK_InsAtt_Attivita` FOREIGN KEY (`IA_ATT`) REFERENCES `TA_Attivita` (`AT_ID`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `MA_Articoli`
--
ALTER TABLE `MA_Articoli`
  ADD CONSTRAINT `FK_ARTICOLO_MASTRO` FOREIGN KEY (`ART_Mastro_ID`) REFERENCES `TA_MAG` (`ID`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `MA_MOVI`
--
ALTER TABLE `MA_MOVI`
  ADD CONSTRAINT `FK_MOVI_MAG` FOREIGN KEY (`MV_MAG_ID`) REFERENCES `TA_MAG` (`ID`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `TA_Attivita`
--
ALTER TABLE `TA_Attivita`
  ADD CONSTRAINT `FK_ATTIVITA_MAG` FOREIGN KEY (`AT_MAG_ID`) REFERENCES `TA_MAG` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `TA_InsAtt`
--
ALTER TABLE `TA_InsAtt`
  ADD CONSTRAINT `TA_InsAtt_ibfk_1` FOREIGN KEY (`AV_IDLU`) REFERENCES `TA_Apiari` (`AI_ID`);

--
-- Limiti per la tabella `TR_FFASE`
--
ALTER TABLE `TR_FFASE`
  ADD CONSTRAINT `fk_tf_arnia` FOREIGN KEY (`TF_ARNIA`) REFERENCES `AP_Arnie` (`AR_ID`),
  ADD CONSTRAINT `fk_tf_att` FOREIGN KEY (`TF_ATT`) REFERENCES `TA_Attivita` (`AT_ID`),
  ADD CONSTRAINT `fk_tf_catt` FOREIGN KEY (`TF_CATT`) REFERENCES `AT_INSATT` (`IA_ID`),
  ADD CONSTRAINT `fk_tf_pfase` FOREIGN KEY (`TF_PFASE`) REFERENCES `TR_PFASE` (`TP_ID`) ON DELETE CASCADE;

--
-- Limiti per la tabella `TR_SCAD`
--
ALTER TABLE `TR_SCAD`
  ADD CONSTRAINT `TR_SCAD_ibfk_1` FOREIGN KEY (`SC_ARNIA`) REFERENCES `AP_Arnie` (`AR_ID`),
  ADD CONSTRAINT `TR_SCAD_ibfk_2` FOREIGN KEY (`SC_TATT`) REFERENCES `TA_Attivita` (`AT_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
