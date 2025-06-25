import { Server, Model, Factory, JSONAPISerializer, belongsTo } from 'miragejs';
import camelCase from 'lodash/camelCase';

export const makeServer = ({ environment = 'development' } = {}) => {
  const MessagesSerializer = JSONAPISerializer.extend({
    keyForAttribute(attr) {
      return camelCase(attr);
    },
    keyForRelationship(modelName) {
      return camelCase(modelName);
    },
    typeKeyForModel(model) {
      return camelCase(model.modelName);
    },
    include: ['sourceMigration', 'sourceMigrationPlugin'],
  });

  let server = new Server({
    environment,
    models: {
      migrationMessage: Model.extend({
        sourceMigration: belongsTo('migration'),
        sourceMigrationPlugin: belongsTo('migrationPlugin'),
      }),
      migration: Model,
      migrationPlugin: Model,
    },

    factories: {
      migrationMessage: Factory.extend({
        datetime() {
          return '2020-02-18 13:01:01';
        },
        severity(i) {
          const types = [1, 2, 3, 4];
          return types[i % types.length];
        },
        sourceId(i) {
          const ids = [
            'id=allow_insecure_uploads',
            `fid=${i}`,
            'format=php_code',
          ];
          return ids[i % ids.length];
        },
        message(i) {
          const messages = [
            "No static mapping found for 'NULL' and no default value provided for destination 'allow_insecure_uploads'.",
            'Private file does not exist',
            'Filter php_code could not be mapped to an existing filter plugin',
          ];
          return messages[i % messages.length];
        },
        messageCategory() {
          return 'other';
        },
        sourceMigrationId(i) {
          const migrations = ['site_config', 'private_files', 'format'];
          return migrations[i % migrations.length];
        },
        sourceMigrationPluginId(i) {
          const plugins = [
            'd7_system_file',
            'd7_file_private',
            'd7_filter_format',
          ];
          return plugins[i % plugins.length];
        },
      }),
    },

    seeds(server) {
      server.create('migration', {
        id: 'site_config',
        label: 'Site Configuration',
      });
      server.create('migration', {
        id: 'format',
        label: 'Format',
      });
      server.create('migration', {
        id: 'private_files',
        label: 'Private Files',
      });
      server.create('migrationPlugin', {
        id: 'd7_system_file',
      });
      server.create('migrationPlugin', {
        id: 'd7_file_private',
      });
      server.create('migrationPlugin', {
        id: 'd7_filter_format',
      });

      server.createList('migrationMessage', 10);
    },

    routes() {
      this.namespace = '/acquia-migrate-accelerate/api';
      this.get('/messages', (schema) => {
        return schema.migrationMessages.all();
      });
    },

    serializers: {
      application: MessagesSerializer,
    },
  });

  return server;
};
