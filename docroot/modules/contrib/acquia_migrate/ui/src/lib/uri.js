const URI = require('urijs');
const URITemplate = require('urijs/src/URITemplate');

/**
 * Parse query string into object.
 *
 * @param {string} href
 *   The URL with a querystring.
 * @return {object}
 *  { key: operator,field,value }
 */
export const parseQuery = (href) => {
  const uri = new URI(href);
  return URI.parseQuery(uri.query());
};

/**
 * Extract filter template and fields, apply default values.
 *
 * @param {{href, rel, uri-template:href, uri-template:suggestions}} query
 *  The query link from the messages collection response.
 * @param {array} defaults
 *   Any default values to be applied to the filter fields.
 *
 * @return {{template: string, fields: array}}
 *   URI template and filter fields.
 */
export const parseFilters = (query, defaults) => {
  return {
    template: query['uri-template:href'],
    fields: query['uri-template:suggestions'].map((suggestion) => {
      const preset = defaults.find((param) => param.field === suggestion.field);
      const value = preset ? preset.value : '';
      return { ...suggestion, value };
    }),
  };
};

/**
 * Get the search string from a URL.
 *
 * @param {string} href
 *   The URL.
 * @return {string}
 *   The query including the ? e.g. ?filter=%3Aeq%2Cmigration%2Cmigration-id
 */
export const getQuery = (href) => {
  const uri = new URI(href);
  return uri.search();
};

/**
 * Parse query object.
 *
 * @param {object} params
 *   Return value of parseQuery.
 *
 * @return {*[]|{field: *, value: *, operator: *}[]}
 *   Object of query properties and values.
 */
export const parseQueryValue = (params) => {
  if (!params.hasOwnProperty('filter')) {
    return [];
  }
  const values = Array.isArray(params.filter) ? params.filter : [params.filter];
  return values.map((val) => {
    const [operator, field, value] = val.split(',');
    return {
      field,
      operator,
      value,
    };
  });
};

/**
 * Convert field properties into comma-separated string.
 *
 * @param {array} fields
 *   Array of objects {field:String, operator:String, value:String}
 * @return {[]}
 *   Array of strings, e.g. [':eq,migration,migration-id']
 */
export const stringifyQueryObj = (fields) =>
  fields
    .filter((filter) => filter.value !== '')
    .map((filter) => `${filter.operator},${filter.field},${filter.value}`);

/**
 * Generate a URL from a template.
 *
 * @param {object} params
 *   Return value of parseQuery.
 * @param {string} tpl
 *   URITemplate
 *
 * @return {string}
 *   URL generated from template.
 */
export const formatURL = (params, tpl) => {
  const template = new URITemplate(tpl);
  return template.expand(params);
};

/**
 * Parse the values needed to create an action button for a mapping override.
 *
 * @param {object} value
 *   Field mapping data.
 * @param {object} links
 *   Links with rel #link-rel-migration-mapping-override-field.
 * @param {function} action
 *   The button onClick.
 *
 * @return {{label, rel, title, action}}
 *   Object formatted for ActionButton, with url composed.
 */
export const parseOverrides = (value, links, action) => {
  return links
    .map((link) => {
      const { title, rel } = link;
      const { options, variable } = link['uri-template:suggestions'];
      const valid =
        value.hasOwnProperty(variable) &&
        options.find((option) => option.value === value[variable]);
      return valid
        ? {
            title,
            rel,
            label: valid.label,
            action: () => {
              const href = formatURL(
                { [variable]: valid.value },
                link['uri-template:href'],
              );
              action({ ...link, href, params: { data: {} } });
            },
          }
        : null;
    })
    .filter((link) => link !== null);
};
