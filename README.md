# Sardegna Turismo - Migrazione Drupal 7 → Drupal 9

Questo repository contiene il progetto di migrazione del sito Sardegna Turismo da Drupal 7 a Drupal 9 utilizzando Acquia Migrate Accelerate.

## 📋 Prerequisiti

- **DDEV** installato localmente ([Guida installazione](https://ddev.readthedocs.io/en/stable/))
- **Docker** attivo e funzionante
- **Dump del database sorgente** Drupal 7
- **PHP 8.1** (configurato in DDEV)
- **Composer 2** (configurato in DDEV)

## 🚀 Setup Iniziale

### 1. Clonare il repository
```bash
cd ~/Developer
git clone [URL_REPOSITORY] st_migrate_d9
cd st_migrate_d9
```

### 2. Avviare DDEV
```bash
ddev start
```

### 3. Importare il database sorgente (Drupal 7)
Il database sorgente deve essere importato nel database chiamato `source`:

```bash
# Importa il dump del database D7 nel database 'source'
ddev import-db --target-db=source --src=path/to/drupal7-database-dump.sql.gz
```

### 4. Installare le dipendenze
```bash
ddev composer install
```

### 5. Verificare la configurazione
Assicurarsi che il file `docroot/sites/default/settings.php` contenga:

```php
// D7 Migration database configuration
$databases['migrate']['default'] = array (
  'database' => 'source',
  'username' => 'db',
  'password' => 'db',
  'prefix' => '',
  'host' => 'db',
  'port' => '3306',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
);

$settings['migrate_source_connection'] = 'migrate';
$settings['migrate_source_version'] = '7';
```

## 🔧 Accesso all'interfaccia di migrazione

### 1. Login come amministratore
```bash
# Genera un link di login one-time per l'utente admin
ddev drush uli
```

### 2. Accedere alla dashboard delle migrazioni
Una volta loggati, navigare a:
```
https://[nome-progetto].ddev.site/acquia-migrate-accelerate/migrations
```

### Percorsi principali di Acquia Migrate:
- **Dashboard migrazioni**: `/acquia-migrate-accelerate/migrations`
- **Pagina iniziale**: `/acquia-migrate-accelerate/get-started`
- **Messaggi**: `/acquia-migrate-accelerate/messages`
- **Moduli**: `/acquia-migrate-accelerate/modules`
- **Preselezione**: `/acquia-migrate-accelerate/preselect`

## 📊 Stato Attuale della Migrazione

La migrazione è attualmente al **67.8%** completata (~8,300 su 12,200+ elementi migrati).

### Content Types - Stato
| Content Type | Totale | Migrati | % | Note |
|-------------|---------|---------|---|------|
| aeroporto | 324 | 324 | 100% | ✅ Completato |
| banner | 9 | 9 | 100% | ✅ Completato |
| destinazione | 1,632 | 1,429 | 87.6% | ⚠️ 203 fallimenti |
| evento | 2,759 | 1,000 | 36.2% | 🔄 In corso |
| attrattore | 101,078 | 1,100 | 1.1% | 🚨 Bloccato |
| contatto | 545 | 1 | 0.2% | 🚨 Problema field_collection |
| informazione_utile | 1,424 | 1,424 | 100% | ✅ Completato |

### Taxonomy - Stato
- **Completate**: 100% (1,215/1,215 termini)
- **Field Collections → Paragraphs**: 968 elementi convertiti

## 🔨 Comandi Drush Utili

### Monitoraggio progresso
```bash
# Status generale delle migrazioni
ddev drush migrate:status

# Status di una migrazione specifica
ddev drush migrate:status d7_node_complete:evento

# Conteggio migrazioni completate
ddev drush migrate:status | grep "100%" | wc -l
```

### Esecuzione migrazioni
```bash
# Eseguire una migrazione specifica
ddev drush migrate:import d7_node_complete:evento --update

# Eseguire con limite di tempo (utile per content type grandi)
ddev drush migrate:import d7_node_complete:attrattore --limit=1000 --update

# Eseguire tutte le migrazioni di un gruppo
ddev drush migrate:import --tag=Drupal_7
```

### Rollback migrazioni
```bash
# Rollback di una migrazione specifica
ddev drush migrate:rollback d7_node_complete:evento

# Reset status se una migrazione è bloccata
ddev drush migrate:reset-status d7_node_complete:attrattore

# Pulire tracking tables (se necessario)
ddev drush sql:query "DELETE FROM migmag_rollbackable_new_targets WHERE target_id LIKE '%evento%'"
```

### Debug e troubleshooting
```bash
# Vedere i messaggi di errore di una migrazione
ddev drush migrate:messages d7_node_complete:evento

# Pulire la cache
ddev drush cr

# Controllare i log
ddev drush watchdog:show --count=50
```

## ⚠️ Problemi Comuni e Soluzioni

### 1. Errore: "The value you selected is not a valid choice" per body.format
**Problema**: I formati di testo di D7 non sono mappati correttamente in D9.

**Soluzione**:
```bash
# Importare prima i formati di testo
ddev drush migrate:import d7_filter_format

# Se persiste, creare manualmente i formati mancanti in D9
```

### 2. File mancanti (immagini, allegati)
**Problema**: Riferimenti a file che non esistono nel sistema.

**Soluzione**:
```bash
# Verificare il percorso dei file pubblici nel sito sorgente
# Aggiornare in settings.php:
$settings['migrate_file_public_path'] = '/path/to/drupal7/sites/default/files';

# Quindi eseguire la migrazione dei file
ddev drush migrate:import d7_file
```

### 3. Title NULL in alcuni nodi
**Problema**: Alcuni nodi nel database sorgente hanno il campo title NULL.

**Soluzione**:
```sql
-- Identificare i nodi problematici
SELECT nid, type FROM node WHERE title IS NULL;

-- Opzione 1: Aggiornare nel database sorgente
UPDATE node SET title = 'Titolo temporaneo' WHERE title IS NULL;

-- Opzione 2: Saltare questi nodi durante la migrazione
```

### 4. Migrazione bloccata su "Importing"
**Problema**: Una migrazione rimane bloccata in stato "Importing".

**Soluzione**:
```bash
# Reset dello stato
ddev drush migrate:reset-status MIGRATION_ID

# Se non funziona, pulire manualmente
ddev drush sql:query "UPDATE migrate_map_MIGRATION_ID SET source_row_status = 0 WHERE source_row_status = 1"
```

## 📁 Struttura del Progetto

```
[project-name]/
├── .ddev/                 # Configurazione DDEV
├── docroot/              # Root di Drupal 9
│   ├── modules/
│   │   └── contrib/
│   │       └── acquia_migrate/  # Modulo Acquia Migrate Accelerate
│   ├── sites/
│   │   └── default/
│   │       ├── settings.php     # Configurazione database
│   │       └── files/          # File pubblici
│   └── themes/
├── vendor/               # Dipendenze Composer
├── composer.json         # Definizione dipendenze
└── Status_migration.MD   # Dettagli stato migrazione
```

## 🚦 Workflow di Migrazione Consigliato

### 1. Pre-migrazione
```bash
# Backup del database attuale
ddev export-db --file=backup-pre-migration-$(date +%Y%m%d).sql.gz

# Verificare che Acquia Migrate sia abilitato
ddev drush pm:list | grep acquia_migrate

# Pulire la cache
ddev drush cr
```

### 2. Ordine di migrazione consigliato
1. **Utenti e ruoli**
   ```bash
   ddev drush migrate:import d7_user_role
   ddev drush migrate:import d7_user
   ```

2. **Tassonomie**
   ```bash
   ddev drush migrate:import --tag=Taxonomy
   ```

3. **File e media**
   ```bash
   ddev drush migrate:import d7_file
   ```

4. **Content types** (in ordine di dipendenza)
   ```bash
   # Prima i content type semplici
   ddev drush migrate:import d7_node_complete:banner
   ddev drush migrate:import d7_node_complete:informazione_utile
   
   # Poi quelli con riferimenti
   ddev drush migrate:import d7_node_complete:evento
   ddev drush migrate:import d7_node_complete:attrattore
   ```

### 3. Post-migrazione
```bash
# Ricostruire la cache
ddev drush cr

# Verificare integrità dei contenuti
ddev drush migrate:status

# Eseguire update del database se necessario
ddev drush updatedb
```

## 🔍 Verifica della Migrazione

### Controlli da effettuare:
1. **Conteggio contenuti**: Verificare che il numero di nodi migrati corrisponda
2. **Immagini e file**: Controllare che i file siano stati migrati correttamente
3. **URL alias**: Verificare che i percorsi URL siano mantenuti
4. **Traduzioni**: Se multilingua, verificare le traduzioni
5. **Campi personalizzati**: Controllare che tutti i campi siano mappati

### Query SQL utili:
```sql
-- Conteggio nodi per tipo
SELECT type, COUNT(*) FROM node_field_data GROUP BY type;

-- Verifica file mancanti
SELECT COUNT(*) FROM file_managed WHERE uri LIKE 'public://%' AND NOT EXISTS (SELECT 1 FROM file_managed WHERE uri = uri);

-- Controllo URL alias
SELECT COUNT(*) FROM path_alias;
```

## 📞 Supporto e Risorse

- **Documentazione Acquia Migrate**: [Link alla documentazione](https://docs.acquia.com/acquia-migrate)
- **Issue tracker**: Utilizzare il sistema di issue del repository
- **Log dettagliati**: Consultare `Status_migration.MD` per dettagli completi

## 🎯 Prossimi Passi

1. **Completare migrazioni in sospeso**:
   - Eventi: 1,759 nodi rimanenti
   - Attrattori: ~100,000 nodi bloccati (necessita fix formato testo)
   - Contatti: 544 nodi (problema field_collection)

2. **Risolvere problemi critici**:
   - Import formati testo D7
   - Configurare percorsi file pubblici/privati
   - Fix validazione campi body

3. **Testing e QA**:
   - Test funzionalità principali
   - Verifica performance
   - Controllo SEO (redirect, meta tag)

## 📝 Note Importanti

- Il modulo **Acquia Migrate Accelerate** è disponibile solo durante la fase di migrazione
- Mantenere sempre un backup prima di operazioni massive
- Le migrazioni possono essere eseguite incrementalmente
- Utilizzare `--update` per aggiornare contenuti già migrati

---

**Ultimo aggiornamento**: Giugno 2025  
**Versione Drupal target**: 9.5.11  
**Modulo migrazione**: Acquia Migrate Accelerate 1.8.1
