# Risolvere l'errore "body.0.format" nella migrazione da Drupal 7 a Drupal 9

L'errore "body.0.format" durante la migrazione da Drupal 7 a Drupal 9 deriva dalle differenze architetturali fondamentali nel modo in cui i formati di testo vengono gestiti tra le versioni. Drupal 7 utilizza ID numerici per i formati (1, 2, 3), mentre Drupal 9 richiede nomi macchina ('basic_html', 'full_html'). Quando questi formati non vengono mappati correttamente, il contenuto migrato appare vuoto nonostante i dati siano presenti nel database.

## Cause principali dell'errore

Il problema nasce da **tre cambiamenti architetturali critici** tra Drupal 7 e Drupal 9. In Drupal 7, le impostazioni di elaborazione del testo sono definite a livello di istanza del campo, permettendo diverse configurazioni per lo stesso campo tra tipi di contenuto diversi. Drupal 9 invece definisce queste impostazioni a livello di storage del campo, richiedendo coerenza tra tutte le istanze.

La causa più comune è l'assenza di una mappatura esplicita tra i formati. Quando un formato D7 non trova corrispondenza in D9, viene migrato come `filter_null`, che restituisce una stringa vuota causando la visualizzazione di contenuti apparentemente vuoti. Questo accade frequentemente con formati personalizzati o quando si utilizzano ID numerici (come "2" per 'full_html') non riconosciuti in D9.

Un altro fattore critico è l'ordine delle dipendenze di migrazione. I formati di testo devono essere migrati prima del contenuto, altrimenti il processo di migrazione non può risolvere correttamente i riferimenti ai formati durante l'importazione dei nodi.

## Soluzione immediata con configurazione YAML

La soluzione più efficace utilizza il plugin `sub_process` con mappatura esplicita dei formati. Ecco la configurazione YAML corretta per gestire i campi body:

```yaml
process:
  body:
    plugin: sub_process
    source: body
    process:
      value: value
      summary: summary
      format:
        plugin: static_map
        source: format
        bypass: true
        map:
          1: plain_text
          2: full_html
          3: basic_html
          4: full_html
          filtered_html: basic_html
          full_html: full_html
          plain_text: plain_text
        default_value: basic_html
```

Questa configurazione gestisce sia gli ID numerici che i nomi macchina, fornendo un valore predefinito sicuro per i casi non mappati. Il parametro `bypass: true` è cruciale per permettere ai valori non mappati di passare al default invece di causare errori.

## Implementazione avanzata con hook personalizzati

Per migrazioni complesse o quando serve maggiore controllo, è possibile implementare `hook_migrate_migration_plugins_alter()` nel modulo personalizzato:

```php
function mymodule_migrate_migration_plugins_alter(array &$migrations) {
  foreach ($migrations as $migration_id => &$migration) {
    if (strpos($migration_id, 'd7_node') !== FALSE) {
      if (isset($migration['process']['body'])) {
        $migration['process']['body'] = [
          'plugin' => 'sub_process',
          'source' => 'body',
          'process' => [
            'value' => 'value',
            'summary' => 'summary',
            'format' => [
              'plugin' => 'static_map',
              'source' => 'format',
              'default_value' => 'basic_html',
              'bypass' => TRUE,
              'map' => [
                1 => 'plain_text',
                2 => 'full_html',
                3 => 'basic_html',
                'filtered_html' => 'basic_html',
                'full_html' => 'full_html',
                'plain_text' => 'plain_text',
              ],
            ],
          ],
        ];
      }
    }
  }
}
```

Questo approccio permette di applicare la stessa logica di mappatura a tutte le migrazioni di nodi senza modificare ogni file YAML individualmente.

## Risoluzione rapida post-migrazione

Se la migrazione è già stata eseguita e i contenuti appaiono vuoti, è possibile correggere direttamente nel database:

```sql
-- Aggiorna i formati nel campo body dei nodi
UPDATE node__body 
SET body_format = CASE 
  WHEN body_format = '1' THEN 'plain_text'
  WHEN body_format = '2' THEN 'full_html' 
  WHEN body_format = '3' THEN 'basic_html'
  ELSE 'basic_html'
END
WHERE body_format IN ('1', '2', '3') OR body_format IS NULL;

-- Ripeti per la tabella delle revisioni
UPDATE node_revision__body 
SET body_format = CASE 
  WHEN body_format = '1' THEN 'plain_text'
  WHEN body_format = '2' THEN 'full_html' 
  WHEN body_format = '3' THEN 'basic_html'
  ELSE 'basic_html'
END
WHERE body_format IN ('1', '2', '3') OR body_format IS NULL;
```

## Debugging e troubleshooting efficace

Per diagnosticare problemi specifici con "contenuti specifici di nomi attrattore", utilizzare questi comandi Drush:

```bash
# Visualizza messaggi di errore dettagliati
drush migrate:messages nome_migrazione --idlist=ID_CONTENUTO

# Debug interattivo di un singolo contenuto
drush migrate:import nome_migrazione --idlist=ID_CONTENUTO --migrate-debug

# Verifica i formati disponibili nel sito di destinazione
drush config:get filter.format.basic_html
```

Il modulo **Migrate Devel** fornisce strumenti di debug avanzati. Installarlo con `composer require drupal/migrate_devel` permette di utilizzare l'opzione `--migrate-debug` per vedere il flusso dei dati in tempo reale.

## Configurazione completa per contenuti specifici

Per i "contenuti specifici di nomi attrattore" menzionati, questa configurazione YAML gestisce correttamente tutti gli aspetti della migrazione:

```yaml
id: upgrade_d7_node_attrattore
label: 'Migrazione contenuti attrattore'
migration_tags:
  - Drupal 7
source:
  plugin: d7_node
  node_type: attrattore  # Adattare al nome macchina reale
process:
  nid: nid
  vid: vid
  type:
    plugin: default_value
    default_value: attrattore
  title: title
  uid: node_uid
  status: status
  created: created
  changed: changed
  body:
    plugin: sub_process
    source: body
    process:
      value: value
      summary: summary
      format:
        - plugin: skip_on_empty
          method: process
          source: format
        - plugin: static_map
          bypass: true
          map:
            1: plain_text
            2: full_html
            3: basic_html
            filtered_html: basic_html
            full_html: full_html
          default_value: basic_html
destination:
  plugin: 'entity:node'
  default_bundle: attrattore
migration_dependencies:
  required:
    - upgrade_d7_filter_format
    - upgrade_d7_user
```

## Best practices per prevenire l'errore

**Prima della migrazione**, eseguire sempre un'analisi dei formati utilizzati nel sito sorgente con questa query SQL sul database D7:

```sql
SELECT DISTINCT format, COUNT(*) as count 
FROM field_data_body 
GROUP BY format 
ORDER BY count DESC;
```

**Durante la configurazione**, assicurarsi che tutti i formati identificati abbiano una mappatura esplicita nella configurazione YAML. Creare i formati di testo necessari in D9 prima di eseguire la migrazione dei contenuti.

**L'ordine di esecuzione** è fondamentale: migrare prima i formati di testo (`d7_filter_format`), poi gli utenti (`d7_user`), e infine i contenuti. Utilizzare `--execute-dependencies` per garantire l'ordine corretto automaticamente.

## Conclusione

L'errore "body.0.format" non è un bug ma una conseguenza delle migliorie architetturali di Drupal 9. La soluzione richiede una mappatura esplicita dei formati attraverso il plugin `sub_process` e `static_map`, con valori di default appropriati. Per migrazioni complesse, l'approccio hook-based offre maggiore flessibilità, mentre le correzioni SQL post-migrazione forniscono una soluzione rapida per contenuti già migrati. La chiave del successo sta nella comprensione delle differenze tra le versioni e nella pianificazione accurata della mappatura dei formati prima dell'esecuzione della migrazione.