# Contributing

## For developing AM:A and working on ANY migrations, including Drupal core's

1. Get the latest recommendations:
```shell script
cd ~ && git clone --branch recommendations --single-branch git@git.drupal.org:project/acquia_migrate.git acquia-migrate-recommendations
```
2. Determine the current recommended Drupal core version:
```shell script
REC_CORE_VERSION=$(jq -r '.data | map(select(.patches != null)) | map(select(.package == "drupal/core")) | .[].constraint' ~/acquia-migrate-recommendations/recommendations.json)
```

3. Get your Drupal 9 core git clone, if you don't have one already (it's fine to use the existing one), and clone the `acquia_migrate` repo to install the AM:A module:
```shell script
git clone https://git.drupalcode.org/project/drupal.git && cd drupal/modules && git clone git@git.drupal.org:project/acquia_migrate.git && cd ..
```
4. In the Drupal root (the previous command left you there), do  
```shell script
git checkout $REC_CORE_VERSION && sed -i '' "s/self.version/^9/" composer.json && git commit -am "Pinned to $REC_CORE_VERSION."
```  
_(git clones of Drupal core have `self.version` in `composer.json` for a few packages, but `self.version` is unspecified, causing composer to fail)_

5. Apply the relevant core patches (which will possibly not apply to any other version, this is why it's important to be on the right Drupal core version!). Optionally, create a branch:
```shell script
sh modules/acquia_migrate/scripts/dev/apply-patches.sh drupal/core ~/acquia-migrate-recommendations/recommendations.json
```
6. Install Drupal:
```shell script
composer install
```
7. Install the AM:A module's dependencies:
```shell script
jq -r '.require | to_entries | map(select(.key != "drupal/core")) | from_entries| keys[] as $k | "\($k) \(.[$k])"' modules/acquia_migrate/composer.json | while read dep; do composer require "$dep"; done; git commit -am 'AMA module dependencies added.'
```
8. Apply the core patches that `acquia_migrate` provides:  
```shell script
jq -r '.extra.patches["drupal/core"] | to_entries | .[].value' modules/acquia_migrate/composer.json | while read p; do echo "Applying AMA-required core patch $p ..."; curl -Ls $p | patch -p1; git commit -a -nm "AMA-required patch $p"; echo ""; done; git clean -f -- core
```
9. Install Drush:
```shell script
composer require drush/drush -W "*" && git commit -am 'Install Drush.'
```
10. Install whatever contrib modules you'd like to develop the migrations for using `git`, and apply any relevant patches for them that are listed in `recommendations.json`. **At a minimum, do this for the `media_migration` module!** Do this semi-automatically by exporting the module name:
```shell script
MODULE=media_migration
```
Now we can automatically figure out the recommended module version:
```shell script
REC_MODULE_VERSION=$(jq -r ".data | map(select(.patches != null)) | map(select(.package == \"drupal/$MODULE\")) | .[].constraint" ~/acquia-migrate-recommendations/recommendations.json)
```
In this case, `echo $REC_MODULE_VERSION` will return `1.0-alpha11`. Figure out what the corresponding `git tag` is, and then export that tag name:
```shell script
TAG_NAME=8.x-1.0-alpha11
```
Now you can blindly execute the following command from the Drupal root:
```shell script
cd modules && git clone https://git.drupalcode.org/project/$MODULE.git && cd $MODULE && git checkout $TAG_NAME && cd ../.. && cp modules/acquia_migrate/scripts/dev/apply-patches.sh modules/$MODULE && cd modules/$MODULE && sh ./apply-patches.sh drupal/$MODULE ~/acquia-migrate-recommendations/recommendations.json && rm apply-patches.sh && cd ../..
```
11. Install (or **re-install**) Drupal core + AM:A + whatever contrib modules (highly recommended to install at least `media_migration`!):
```shell script
vendor/bin/drush si standard -y --account-name="root" --account-pass=root && vendor/bin/drush  pm:enable -y acquia_migrate media_migration media_library
```
12. (optional) If you want to test or develop the Module Auditor UI, you need to import the "initial info" that `acli` generated for your particular site. Pick [one of the existing JSON blobs](https://github.com/acquia/cli/blob/9f165fe786a8d20b4dab4adc57ce6cbfa80bec37/tests/fixtures/drupal7/www.standard-profile.com/expected.json#L4) or generate one for your own test site, then import it like so:
```shell script
vendor/bin/drush sset --input-format=json acquia_migrate.initial_info - < blob_generated_by_acli.json
```


## Updating patches
Patches to be applied to either Drupal core (to improve core migrations and provide Acquia Migrate Accelerate-specific
infrastructure) or to a Drupal contrib module (to make it compatible with Drupal 9 and/or to provide a migration path)
must be specified in <https://github.com/acquia/acquia-migrate-recommendations>.

Only Drupal core patches that are necessary for the `acquia_migrate` module to function without errors are allowed to be
specified in this repository's `composer.json` file.

## Set up git hooks.
```
cp .config/git/hooks/* .git/hooks/
ls -1d .git/hooks/* | grep -v sample | xargs chmod u+x
```

## Nightwatch
```
# Run nightwatch tests locally (see installation steps!)
cd /path/to/drupal
cd docroot/core
yarn test:nightwatch --env local ../modules/acquia_migrate/tests/src/Nightwatch/Tests/migrate-ui-test.js
```

1. To run `nightwatch` tests locally, first look at the major version number that this command outputs:
```
/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version
```
2. Now verify that this matches the version that is listed in `core/package.json`. If it does not match, modify `package.json` to `^NN` (where `NN` is the major version). This change can be reverted after the next step.
3. From the Drupal `core` directory, run
```
yarn install
```

## React UI


The app is contained in the `ui` directory of `acquia_migrate`. This folder contains all the config for building the app and maintaining code style.

* `src` Contains all `js`, `jsx` and `scss` files.
* `dist` Contains all the built and minified files which are loaded by Drupal in `acquia_migrate.libraries.yml`

From that directory, run:
```
npm ci
```

To start the development server at localhost:8080
```
npm run start
```

Build minified production files
```
npm run build
```

### Building the dist files

The `scripts` section of `package.json` contains all the commands to test, build and lint the `src`.

* test - `npm test` to run jest unit tests. Optionally add a filename as an argument such as `npm test utils` to test only that file.
* build - `npm run build` shorthand for `build:prod`.
* build:dev - `npm run build:dev` build the unminified files including source maps for debugging and using the React plugin for chrome or firefox.
* build:watch - `npm run build:watch` starts a file watcher for any changes, automatically runs the dev build.
* build:prod - `npm run build:prod` build the production ready minified files.
* build:stats - `npm run build:stats` output a json file for use in the [Webpack Analyze tool](http://webpack.github.io/analyse)
* lint:js - `npm run lint:js` Lint the `js` and `jsx` files.
* lint:jest - `npm run lint:jest` Lint the jest tests.
* prettier - `npm run prettier` Automatically format all source files per the rules in `.prettierrc` and `.eslintrc`
