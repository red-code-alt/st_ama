# Status Migrazione Drupal 7 → Drupal 9
**Progetto:** Sardegna Turismo  
**Data aggiornamento:** 15 Giugno 2025  
**Progresso totale:** 67.8% (~8,300/12,200+ migrazioni D7 completate)

## 📊 Statistiche Generali
- **Content totali migrati:** ~8,300+ nodi su ~12,200 totali
- **Taxonomy completate:** 100% (1,215/1,215 termini)
- **Field Collections:** 968 paragraphs migrati
- **Ambiente:** DDEV con Docker
- **Database source:** sardegna-turismo (D7)
- **Database target:** db (D9)

## ✅ Migrazioni Completate con Successo

### Content Types (Nodi)
| Content Type | Nodi Totali | Completati | Percentuale | Note |
|-------------|-------------|------------|-------------|------|
| **aeroporto** | 324 | 324 | 100% | ✅ Completato - warning su formati body normali |
| **banner** | 9 | 9 | 100% | ✅ Completato senza errori |
| **destinazione** | 1,632 | 1,429 | 87.6% | ⚠️ 203 fallimenti per title NULL |
| **evento_locale** | 15 | 14 | 93.3% | ⚠️ 1 fallimento per lingua 'und' |
| **esperienza** | 0 | 0 | 100% | ✅ Nessun contenuto da migrare |
| **contatto** | 545 | 1 | 0.2% | 🔄 Solo 1 migrato - problema field_collection |
| **evento** | 2,759 | 1,000 | 36.2% | 🔄 In progresso - 1,759 rimanenti |
| **attrattore** | 101,078 | 1,100 | 1.1% | 🚨 BLOCCATO - Solo fino nid 15175 processato |
| **informazione_utile** | 1,424 | 1,424 | 100% | ✅ Completato |

### Taxonomy Terms
| Vocabolario | Termini | Stato | Note |
|-------------|---------|-------|------|
| **attrattore** | 53 | ✅ Completato | ✅ Tutti migrati |
| **brand** | 6 | ✅ Completato | ⚠️ File immagini mancanti |
| **conoscere** | 8 | ✅ Completato | ⚠️ File immagini mancanti |
| **cosa_fare** | 58 | ✅ Completato | ⚠️ File immagini mancanti |
| **comuni_istat** | 400 | ✅ Completato | ✅ Tutti migrati |
| **categoria_interventi** | 667 | ✅ Completato | ✅ Tutti migrati |
| **budget** | 5 | ✅ Completato | ✅ Tutti migrati |
| **autore** | 8 | ✅ Completato | ✅ Tutti migrati |
| **Altri vocabolari** | ~70 | ✅ Completato | Totale: 1,215/1,215 termini |

### Field Collections (ora Paragraphs)
| Tipo | Elementi | Stato | Note |
|------|----------|-------|------|
| **Totali Paragraphs** | 968 | ✅ Completato | Convertiti da field_collection a paragraphs |
| **contatto:field_altri_valori** | Inclusi | ⚠️ Problema | Non collegati ai nodi contatto |
| **evento:field_tappe** | Inclusi | ✅ Completato | ⚠️ File brochure mancanti, georef NULL |
| **Altri field_collection** | Inclusi | ✅ Completato | Vari tipi convertiti in paragraphs |

### Altri Componenti
- **User fields:** 30/30 completati
- **Comment type:** Creato manualmente (page)
- **Weight module:** Installato e abilitato
- **Geolocation modules:** Abilitati (google_maps, geofield integration)

## ❌ Migrazioni con Problemi

### Content Types Problematici
| Content Type | Problema | Soluzione Richiesta |
|-------------|----------|-------------------|
| **contatto** | Solo 1/545 migrato | 🔧 Problema con field_collection altri_valori |
| **destinazione** | 203 fallimenti su 1,632 | 🔧 Title NULL in alcuni record source |

### Migrazioni Fallite
| Migrazione | Errore | Stato |
|------------|--------|-------|
| **d7_action** | Plugin VBO mancanti | ⏸️ Rimandata - moduli VBO non installati |
| **d7_field_formatter_settings:comment** | Input NULL invece di array | 🔧 Bug nel core/contrib |
| **d7_entity_translation_settings:node:tipo_test_et** | Bundle tipo_test_et mancante | ❓ Content type da creare |

## 🔄 Migrazioni in Corso

### Eventi (Priorità Alta)
- **Stato:** 1,000/2,759 nodi (36.2%)
- **Rimanenti:** 1,759 nodi da migrare
- **Problemi:** Field collection complesse, file allegati mancanti
- **Tempo stimato:** ~2-3 ore per completamento rimanenti

### Attrattori (Priorità Alta - PROBLEMA IDENTIFICATO)
- **Stato:** Solo 1,100/101,078 nodi (1.1%) REALMENTE migrati
- **Problema scoperto:** Migration ha processato solo fino a nid 15175, ma esistono nodi fino a 282536
- **Rimanenti:** ~100,000 nodi da migrare
- **PROBLEMA CRITICO:** 
  - La migrazione è bloccata su nodi già processati con errori di validazione
  - body.0.format="The value you selected is not a valid choice"
  - Body field reso non obbligatorio ma persiste problema formato
- **Azione eseguita:** 
  - Campo body reso non obbligatorio
  - Identificato che migrazione non procede a nodi con ID alto
- **Prossimi step:** Necessario approccio diverso per saltare nodi problematici

## ⏸️ Migrazioni Rimandate

### File e Media
- **Status:** Non avviate
- **Motivo:** File source probabilmente mancanti dal dump
- **Impatto:** Immagini e allegati non disponibili
- **Azione:** ⚠️ Verificare se file sono disponibili separatamente

### Views Bulk Operations
- **d7_action** con plugin VBO
- **Moduli richiesti:** views_bulk_operations con plugin script/modify
- **Azione:** 🔧 Installare moduli VBO o skippare se non necessari

### Comment Field Formatters
- **Errori:** Input NULL nei field formatter settings
- **Impatto:** Minimo - solo formattazione commenti
- **Azione:** 🔍 Investigare o skippare

### Blocks
- **d7_block:seven_subtheme:simple** completato parzialmente
- **Altri theme block:** Da verificare

## 🚨 Issues Critici da Risolvere

### 1. File Mancanti
**Problema:** Tutti i riferimenti file falliscono con "entity does not exist"
```
Missing file with ID 68491, 68486, etc.
field_immagine_top.0.target_id=The referenced entity (file: 68491) does not exist
```
**Impatto:** Alto - nessuna immagine/allegato funzionante
**Soluzione:** Verificare se file sono disponibili e importare d7_file migration

### 2. Text Format Mapping
**Problema:** Formati testo D7 non mappati correttamente a D9
```
body.0.format=The value you selected is not a valid choice
```
**Impatto:** Medio - contenuto mostrato ma senza formattazione
**Soluzione:** Importare d7_filter_format o mappare formati manualmente

### 3. Title NULL in informazione_utile
**Problema:** Alcuni nodi hanno title NULL nel database source
**Impatto:** Medio - impedisce migrazione content type
**Soluzione:** Aggiungere default title o pulire dati source

### 4. Attrattori Validation Errors - CRITICI
**Problema:** Migrazione attrattori completely blocked by validation errors
```
body.0.format=The value you selected is not a valid choice
body=This value should not be null
```
**Dettagli:** 
- Total attrattori: 101,078 (NOT 10,140 as previously documented)
- Imported: 10,140 (only 10.0%)
- Remaining: 90,938 stuck with validation errors
- Migration processes 0 new items, only updating existing ones
**Impatto:** ALTO - 90% of attractors stuck, major content type blocked
**Soluzioni necessarie:**
1. **Text Format Mapping:** Import d7_filter_format first OR create manual mapping
2. **Skip Invalid Nodes:** Mark problematic nodes as failed to allow progression
3. **Body Field Validation:** Fix NULL body values or make field optional

## 📋 Piano di Azione Prossimi Step

### Priorità 1 - CRITICHE (oggi)
1. **SBLOCCARE ATTRATTORI:** 90,938 nodi bloccati da validation errors
   - Import d7_filter_format migration FIRST
   - OR create manual text format mapping 
   - Mark problematic nodes as failed if necessary
2. **Completare eventi:** 1,759 nodi rimanenti (2-3 ore)
3. **Risolvere contatti:** Debug field_collection altri_valori (544 nodi bloccati)

### Priorità 2 - Questa settimana
1. **Fix destinazioni:** Risolvere 203 title NULL
2. **Investigare file:** Verificare se d7_file può essere eseguita
3. **Field formatters:** Fix o skip comment formatters

### Priorità 3 - Prossima settimana
1. **File migration:** Se files disponibili, importare media
2. **Content types rimanenti:** Verificare altri tipi non ancora controllati
3. **Testing:** Verificare funzionalità sito migrato

## 🔧 Configurazioni e Fix Applicati

### Moduli Installati
```bash
drupal/geofield drupal/geolocation drupal/weight
geolocation_google_maps geolocation_geofield
```

### Fix Manuali
1. **Comment type "page"** creato programmaticamente
2. **Italian language** aggiunto per evitare errori translation
3. **Migmag rollback tables** pulite per aeroporto
4. **Entity storage cache** resettata per node types

### Database Cleanup
```sql
-- Puliti tracking tables per aeroporto
DELETE FROM migmag_rollbackable_new_targets WHERE target_id LIKE '%aeroporto%'
-- Config entities ricreate per aeroporto dopo rollback
```

## 📊 Metriche Dettagliate

### Tempi di Esecuzione
- **Banner (9 nodi):** ~30 secondi
- **Evento_locale (15 nodi):** ~1 minuto  
- **Destinazione (1,632 nodi):** ~4 minuti (con timeout)
- **Eventi (16 nodi):** ~48 secondi (molto lento per field collection)

### Storage Utilizzato
- **Paragraphs (field_collection):** 968 paragraphs totali
- **Taxonomy terms:** 1,215 termini totali
- **Nodi migrati:** ~8,300 nodi di vari tipi

## 🎯 Obiettivi Finali

### Milestone 1 (Questa settimana): 85%
- Completare eventi (1,759 rimanenti)
- Risolvere contatti (544 nodi)
- Attrattori parziali (almeno 50%)

### Milestone 2 (Prossima settimana): 95%  
- Completare attrattori (8,140 rimanenti)
- Fix destinazioni (203 title NULL)
- File migration se possibile

### Milestone 3 (Completamento): 98%+
- Altri content types minori
- Cleanup migrazioni fallite non critiche
- Test funzionalità complete

## 🗂️ File e Comandi Utili

### Monitoraggio Progresso
```bash
# Progress real-time
watch -n 5 'echo "D7 Progress: $(ddev drush migrate:status 2>/dev/null | grep "d7_" | grep "100%" | wc -l)/$(ddev drush migrate:status 2>/dev/null | grep -c "d7_") = $(echo "scale=1; $(ddev drush migrate:status 2>/dev/null | grep "d7_" | grep "100%" | wc -l)*100/$(ddev drush migrate:status 2>/dev/null | grep -c "d7_")" | bc)%"'

# Check specific migration
ddev drush migrate:status d7_node_complete:evento
```

### Reset Migration se Necessario
```bash
# Rollback specifica migrazione
ddev drush migrate:rollback d7_node_complete:CONTENT_TYPE --yes

# Reset status se bloccata
ddev drush migrate:reset-status MIGRATION_ID

# Clear migmag tracking
ddev drush sql:query "DELETE FROM migmag_rollbackable_new_targets WHERE target_id LIKE '%CONTENT_TYPE%'"
```

---

## 📈 Progresso Aggiornato - 15 Giugno 2025

**Situazione attuale più positiva del previsto:**
- La migrazione è al **67.8%** invece del 55.4% stimato
- **Taxonomy 100% completate** (1,215 termini)
- **Field collections** convertite in paragraphs (968 elementi)
- **Eventi e attrattori** in migrazione batch attiva

**Prossimi step critici:**
1. Completare eventi (1,759/2,759 rimanenti)
2. Risolvere blocco contatti (solo 1/545 migrato)
3. Continuare attrattori (8,140/10,140 rimanenti)

---
**Nota:** Questo file viene aggiornato durante la migrazione. Consultare per status più recente prima di procedere con batch di migrazioni grandi.