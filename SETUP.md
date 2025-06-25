# Setup Iniziale - Migrazione Sardegna Turismo D7 → D9

## 1. Clone e Setup Directory
```bash
cd ~/Developer
git clone [URL_REPOSITORY] st_migrate_d9
cd st_migrate_d9
```

## 2. Avvio Ambiente DDEV
```bash
# Avvia i container Docker
ddev start

# Verifica che il progetto sia attivo
ddev describe
```

## 3. Import Database

### 3.1 Import Database Drupal 9 (principale)
```bash
# Se hai un dump esistente del database D9
ddev import-db --file=path/to/drupal9-database.sql.gz

# Oppure se il database è già presente, verifica la connessione
ddev mysql -e "SHOW TABLES;"
```

### 3.2 Import Database Drupal 7 (sorgente per migrazione)
```bash
# IMPORTANTE: Il database D7 deve essere importato nel database chiamato 'source'
ddev mysql -e "CREATE DATABASE IF NOT EXISTS source;"

# Importa il dump D7 nel database source
ddev import-db --target-db=source --file=path/to/drupal7-database.sql.gz

# Verifica che il database source sia stato importato
ddev mysql -D source -e "SHOW TABLES;" | head -20
```

## 4. Installazione Dipendenze
```bash
# Installa tutte le dipendenze PHP via Composer
ddev composer install

# Se necessario, aggiorna le dipendenze
ddev composer update --with-dependencies
```

## 5. Configurazione Database Connection

### 5.1 Verifica settings.php
```bash
# Controlla che il file settings.php esista
ls -la docroot/sites/default/settings.php

# Se non esiste, copialo dal template
cp docroot/sites/default/default.settings.php docroot/sites/default/settings.php
chmod 644 docroot/sites/default/settings.php
```

### 5.2 Configura la connessione al database di migrazione
```bash
# Aggiungi la configurazione del database sorgente a settings.php
cat >> docroot/sites/default/settings.php << 'EOF'

// D7 Migration source database configuration
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

// Migration settings
$settings['migrate_source_connection'] = 'migrate';
$settings['migrate_source_version'] = '7';
$settings['migrate_file_public_path'] = '';
$settings['migrate_file_private_path'] = '';
EOF
```

## 6. Verifica Moduli e Permessi

### 6.1 Verifica che Acquia Migrate sia installato
```bash
# Lista moduli installati
ddev drush pm:list | grep -i migrate

# Se non è abilitato, abilitalo
ddev drush en acquia_migrate -y
```

### 6.2 Assegna ruolo administrator all'utente admin
```bash
# Assicurati che l'utente admin abbia tutti i permessi necessari
ddev drush user:role:add administrator admin

# Genera un link di login one-time
ddev drush uli
```

## 7. Pulizia Cache e Preparazione
```bash
# Pulisci tutta la cache di Drupal
ddev drush cr

# Esegui eventuali update del database
ddev drush updatedb -y

# Verifica lo stato del sito
ddev drush status
```

## 8. Test Connessione Database Migrazione
```bash
# Verifica che Drupal possa connettersi al database sorgente
ddev drush sqlq --database=migrate "SELECT COUNT(*) FROM node;" 

# Controlla le tabelle del database sorgente
ddev drush sqlq --database=migrate "SHOW TABLES;" | head -20

# Verifica versione Drupal del sorgente
ddev drush sqlq --database=migrate "SELECT schema_version FROM system WHERE name = 'system';"
```

## 9. Comandi DDEV Utili

### 9.1 Gestione Database
```bash
# Accesso diretto a MySQL
ddev mysql                  # Database principale
ddev mysql -D source       # Database sorgente D7

# Export database
ddev export-db --file=backup-$(date +%Y%m%d-%H%M).sql.gz              # Database principale
ddev export-db --target-db=source --file=source-backup.sql.gz         # Database sorgente

# Query dirette
ddev mysql -e "SHOW DATABASES;"
ddev mysql -D source -e "SELECT COUNT(*) as total_nodes FROM node;"
```

### 9.2 Gestione File e Log
```bash
# Accesso SSH al container web
ddev ssh

# Visualizza log in tempo reale
ddev logs -f                # Tutti i log
ddev logs -f --tail=100    # Ultimi 100 righe

# Accesso ai file
ddev exec ls -la docroot/sites/default/files/

# Copia file dal/al container
ddev cp source.txt web:/var/www/html/           # Copia nel container
ddev cp web:/var/www/html/file.txt ./           # Copia dal container
```

### 9.3 Performance e Debug
```bash
# Abilita/disabilita xdebug
ddev xdebug on
ddev xdebug off

# Restart servizi
ddev restart

# Pulisci tutto e ricomincia
ddev poweroff
ddev start
```

## 10. Verifica Finale Setup

### 10.1 Checklist di verifica
```bash
# 1. DDEV attivo
ddev status

# 2. Database principale accessibile
ddev drush sqlq "SELECT COUNT(*) FROM users;"

# 3. Database sorgente accessibile
ddev drush sqlq --database=migrate "SELECT COUNT(*) FROM users;"

# 4. Acquia Migrate abilitato
ddev drush pm:list --status=enabled | grep acquia_migrate

# 5. URL di accesso
echo "Dashboard: https://$(ddev describe -j | jq -r '.raw.hostname')/acquia-migrate-accelerate/migrations"

# 6. Login link
ddev drush uli --uri=$(ddev describe -j | jq -r '.raw.httpurl')
```

### 10.2 Accesso alla Dashboard Migrazione
```bash
# Dopo il login, naviga a:
# https://[nome-progetto].ddev.site/acquia-migrate-accelerate/migrations
```

## Troubleshooting Setup

### Problema: "Connection refused" al database source
```bash
# Ricrea il database source
ddev mysql -e "DROP DATABASE IF EXISTS source; CREATE DATABASE source;"
ddev import-db --target-db=source --file=path/to/d7-dump.sql.gz
```

### Problema: Permessi file
```bash
# Fix permessi directory files
ddev exec chmod -R 755 docroot/sites/default/files
ddev exec chown -R www-data:www-data docroot/sites/default/files
```

### Problema: Memory limit
```bash
# Aumenta memory limit PHP
ddev config --php-memory-limit 512M
ddev restart
```
