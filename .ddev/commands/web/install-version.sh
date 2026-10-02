#!/bin/bash
# Shared installer used by install-v13 / install-v14.
# Usage: install-version.sh <major>   e.g. install-version.sh 14

set -e

MAJOR=$1
VERSION=v${MAJOR}
DIR=/var/www/html/$VERSION
SITE_URL="https://${VERSION}.${DDEV_SITENAME}.ddev.site/"

rm -rf "${DIR:?}"/* "$DIR"/.[!.]* 2>/dev/null || true
mkdir -p "$DIR"
echo "{}" > "$DIR/composer.json"
composer config name sourcebroker/translatr-$VERSION -d "$DIR"
composer config minimum-stability dev -d "$DIR"
composer config prefer-stable true -d "$DIR"
composer config extra.typo3/cms.web-dir public -d "$DIR"
composer config repositories.$EXTENSION_KEY path ../../$EXTENSION_KEY -d "$DIR"
composer config --no-plugins allow-plugins.typo3/cms-composer-installers true -d "$DIR"
composer config --no-plugins allow-plugins.typo3/class-alias-loader true -d "$DIR"
composer req "typo3/minimal:^${MAJOR}" "typo3/cms-extensionmanager:^${MAJOR}" "typo3/cms-tstemplate:^${MAJOR}" \
    "typo3/cms-lowlevel:^${MAJOR}" "typo3/cms-fluid-styled-content:^${MAJOR}" "$PACKAGE_NAME:*@dev" --no-progress -n -d "$DIR"

# Optional third-party extension, used to test translating labels of a non-core extension
composer req georgringer/news --no-progress -n -d "$DIR" \
    || echo "⚠️  georgringer/news not installable for TYPO3 $MAJOR - skipping"

cd "$DIR"

mysql -h db -u root -proot -e "DROP DATABASE IF EXISTS ${VERSION}; CREATE DATABASE ${VERSION};"

vendor/bin/typo3 setup -n --force --dbname=$VERSION --password=$TYPO3_DB_PASSWORD \
    --create-site="$SITE_URL" --admin-user-password="$TYPO3_SETUP_ADMIN_PASSWORD"

# Core has no "configuration:set" command (that is typo3-console), so dev settings go to additional.php
mkdir -p config/system
cat > config/system/additional.php <<'PHP'
<?php
$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceReferrer'] = false;
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = 'localhost:1025';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'admin@example.com';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'ImageMagick';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = '/usr/bin/';
// Backend language packs matching the translatr test languages (v13 / v14 option paths)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] = ['pl', 'de', 'fr'];
$GLOBALS['TYPO3_CONF_VARS']['LANG']['availableLocales'] = ['pl', 'de', 'fr'];
PHP

# Site with en / pl / de and the frontend rendering of the test content (see .ddev/test-content/)
sed "s#\#\#\#BASE\#\#\##${SITE_URL}#" /mnt/ddev_config/test-content/config.yaml > config/sites/main/config.yaml
cp /mnt/ddev_config/test-content/setup.typoscript config/sites/main/setup.typoscript
# TYPO3 13 "setup --create-site" also creates a sys_template record ("Welcome to TYPO3" page, clear=3) which
# overrides the site TypoScript above; TYPO3 14 does not. Remove it so both versions render the same frontend.
mysql -h db -u root -proot $VERSION -e "DELETE FROM sys_template;"

# translatr configuration (read from page TSconfig of the root page)
EXTENSIONS="        10 = translatr"
[ -d vendor/georgringer/news ] && EXTENSIONS="$EXTENSIONS
        20 = news"
cat > config/sites/main/page.tsconfig <<TSCONFIG
tx_translatr {
    languages {
        pl = Polish
        de = Deutsch
        fr = French
    }
    extensions {
$EXTENSIONS
    }
}
TSCONFIG

vendor/bin/typo3 extension:setup
vendor/bin/typo3 language:update || echo "⚠️  Could not download language packs"
php /mnt/ddev_config/test-content/create-content.php
vendor/bin/typo3 cache:flush

echo ""
echo "✅ TYPO3 $VERSION installed with EXT:$EXTENSION_KEY"
echo "   Frontend: $SITE_URL"
echo "   Backend:  ${SITE_URL}typo3/  (admin / $TYPO3_SETUP_ADMIN_PASSWORD)"
