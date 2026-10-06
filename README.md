# TYPO3 Extension `translatr`

[![Latest version](https://poser.pugx.org/sourcebroker/translatr/v/stable)](https://packagist.org/packages/sourcebroker/translatr)

[![License](https://poser.pugx.org/sourcebroker/translatr/license)](https://packagist.org/packages/sourcebroker/translatr)

- [How Translatr works](#how-translatr-works)
- [Installation](#installation)
- [Configuration](#configuration)
- [Editing translations](#editing-translations)
- [Source indexing](#source-indexing)
- [Deployment](#deployment)
- [Optional YAML import and JSON API](#optional-yaml-import-and-json-api)
- [Caches and upgrades](#caches-and-upgrades)
- [Functional checks](#functional-checks)
- [Changelog](#changelog)
- [Version compatibility and maintenance](#version-compatibility-and-maintenance)

## How Translatr works

Translatr lets editors translate extension labels from the TYPO3 backend. Translations and manual changes
are stored in the database. The extension connects three steps:

1. **Index source labels:** read label keys and default-language text from extension files into the database.
2. **Edit translations:** editors create or change database translations in the Translate module.
3. **Generate frontend overrides:** a frontend request builds XLF files from the database when the generated
   files are missing. Subsequent requests reuse those files.

Indexing runs automatically when the module displays a selected extension, or explicitly through a CLI command.
The optional YAML import additionally populates translations and assigns tags for selected keys.

Version 8 supports TYPO3 13.4 and TYPO3 14 from 14.3 onward.
Existing labels and translations are preserved when upgrading.

## Installation

Use Composer:

```bash
composer require sourcebroker/translatr
```

Apply TYPO3's database schema updates, then clear the system caches.

## Configuration

Add the extensions and languages you want to manage to the root page's Page TSconfig:

```typoscript
tx_translatr {
  extensions {
    10 = news
  }
  languages {
    pl = Polish
    de = Deutsch
    fr = French
  }
}
```

Replace `news` with your own extension key or add more entries. The extension list controls which loaded
extensions appear in the module and which ones the `translatr:labels:index` command accepts.
The language list controls the translation columns available to editors and the languages used by the YAML import.

Source files are discovered in each extension's `Resources/Private/Language/` directory:
`locallang.xlf`, `locallang_db.xlf`, and their `.xml` filename variants. These files must contain XLIFF
in version 1.x or 2.x. Translatr reads source labels directly, without TYPO3's internal parser classes.
Legacy `T3locallang` XML and XML with a DOCTYPE
are rejected with an explicit error; an `.xml` filename containing XLIFF remains supported.

Translatr uses global translation records. Grant access to the Translate module and the label table only
to backend users who should manage these translations. Configuration is read from the first root-level
page by UID; it is shared across the installation.

## Editing translations

1. Open **Content > Translate** in TYPO3 14, or **Web > Translate** in TYPO3 13.
2. Select an extension and the languages you want to display, then choose **Apply filters**.
3. Use the edit icon beside a label to add or update its translation, and save the record.

The module remembers your extension and language selection. A translation available only in a language file
can be displayed before it has a database record; saving it creates the editable database translation.
Manual edits are preserved during subsequent source indexing and YAML translation imports.

Saving a label invalidates the generated translation files. They are rebuilt on the next frontend request.
If a page still shows an older translation, clear the relevant frontend page cache as well.

## Source indexing

Every time the module displays a selected extension, including when applying filters, it reads that extension's
source files to calculate their SHA-256 hashes. A file is parsed and its default-language labels synchronized
only when its hash differs from the stored hash, or no hash has been stored yet.

An unchanged hash skips source parsing and label updates for that file. The module still reads the database
to display labels and may load language files to display translations that have no database record.

Source indexing creates missing default-language records and updates unmodified default-language text.
It preserves manual edits (`modify = 1`), existing translations, tags and descriptions. It also retains
database records whose keys have disappeared from the source files. Reading a source file for indexing
bypasses the localization cache so that changed source text is picked up.

Each file's hash and labels come from the same in-memory snapshot. All changed files are parsed before
database updates are queued. If deployment replaces a file after it has been read, the next indexing
operation detects its new contents.

If a source file is malformed or uses an unsupported XML format, the backend displays a warning identifying
the file and continues displaying existing database labels. No source files in that indexing operation are
updated. Correct the file and reopen the module to retry. The CLI still reports a failure for deployment checks.

### Explicit indexing and forced refresh

The CLI uses the same indexing logic. Without an extension argument, it processes every loaded extension
listed in `tx_translatr.extensions`:

```bash
vendor/bin/typo3 translatr:labels:index
vendor/bin/typo3 translatr:labels:index news
```

Use `--force` to process files even when their hashes have not changed. The same rules protecting manual
edits and existing translations still apply:

```bash
vendor/bin/typo3 translatr:labels:index --force
vendor/bin/typo3 translatr:labels:index news --force
```

The command reports the number of processed source files. An unknown or unconfigured extension, or an
indexing failure, returns a non-zero exit code. No configured extensions results in a successful no-op.

### Optional backend button

The **Synchronize labels from files** button forces indexing of the selected extension. It is hidden and
its backend action is disabled by default. To enable it, add this to the root page's Page TSconfig:

```typoscript
tx_translatr.showSyncButton = 1
```

Setting this option back to `0` disables only manual forced synchronization through the backend.
Automatic hash checks and CLI indexing remain active.

## Deployment

Run source indexing after deploying extension files and clearing TYPO3's system caches:

```bash
vendor/bin/typo3 translatr:labels:index
```

This prepares the database before an editor opens the module. **It does not switch Translatr to deployment-only
indexing:** backend visits still calculate source hashes, but skip indexing when those hashes match.

If the project uses the YAML workflow below, run its import after source indexing:

```bash
vendor/bin/typo3 translatr:import:configuration --fail-on-connection-error
```

The import also indexes its own target extensions, so a separate indexing command is needed only to cover
other configured extensions. Neither command generates the frontend XLF overrides immediately; they invalidate
generated output as needed, and the next frontend request rebuilds it from the database.

## Optional YAML import and JSON API

This optional workflow prepares groups of translation records for consumers such as a Vue application.
For ordinary label editing and frontend XLF overrides, the Translate module is sufficient; no YAML
configuration is required.

The companion extension [t3apitranslatr](https://github.com/sourcebroker/t3apitranslatr) exposes Translatr's
database labels as JSON through t3api. Its `/_api/translations` endpoint can filter labels by tags.
For example, a frontend component can request the labels tagged `vue`:

```text
/_api/translations?tags[]=vue
```

The YAML configuration lists label keys and metadata, not translation text. Running the import copies
available translations from language files into database records and assigns their tags, so editors can
manage the text in TYPO3 and an API consumer can retrieve the relevant group. Names such as `general`
and `vue` are project-defined tags; they have no special meaning inside Translatr.

The import also works without t3apitranslatr when you want to populate selected translations in the database
as part of deployment. Translatr itself does not provide the JSON endpoint. API integration requires a
t3apitranslatr release compatible with your Translatr and TYPO3 versions; releases requiring Translatr
`^7.0` do not accept Translatr 8.0.0.

Place `Configuration/Translation/Configuration.yaml` in an active extension. For example, in the
`local` extension:

```yaml
presets:
  local: &preset-local
    tags: ['general', 'vue']
ext:
  local:
    locallang.xlf:
      'search.submit': *preset-local
```

Here, `ext.local` identifies the target extension and `locallang.xlf` identifies the source file under
`Resources/Private/Language/`. Use `locallang.xml` instead if that is the actual source filename.
The key `search.submit` must exist in that source file. `presets` holds reusable YAML anchors;
it is optional, and you can put `tags: ['general', 'vue']` directly under the label key instead.

Run the import explicitly, for example during deployment:

```bash
vendor/bin/typo3 translatr:import:configuration --fail-on-connection-error
```

The shorter alias is `translatr:import:config`. The command discovers configuration files in all active
extensions; its targets come from the YAML `ext` entries rather than the Page TSconfig extension list.
It indexes those target extensions and imports available translations for the listed keys in
the languages configured in `tx_translatr.languages`. It creates missing translation records and updates
unmodified translations; manually modified translation text is preserved.

For the example above, the tags become `general,vue` on matching records in all languages. YAML tag
values replace existing database tags for that label, including tags on manually edited records.
Definitions from multiple configuration files are merged, with duplicate tag values removed.

Opening the module or running `translatr:labels:index` does not import this YAML configuration.
The `--fail-on-connection-error` option makes a missing database connection fail the deployment command;
without it, the import is skipped with a successful exit code.

## Caches and upgrades

Translatr keeps source hashes and generated frontend files separately:

| Stored data | Purpose | When it changes |
| --- | --- | --- |
| `translatr_index` cache | Remembers hashes of successfully indexed source files | Written after indexing succeeds; cleared with the TYPO3 system cache |
| Generated XLF files and loader | Apply database translations in frontend requests | Invalidated by label edits, indexing that processes files, YAML imports, and system/all cache clearing; rebuilt on the next frontend request |

By default, hashes use `SimpleFileBackend` under `var/cache/data/translatr_index`, with no expiry and no extra
database table. Generated files are stored under TYPO3's variable-data directory at `cache/data/tx_translatr`.
An ordinary label edit invalidates generated translations without clearing the source hashes.
Deleting a default-language label through TYPO3's DataHandler also invalidates that file's source hash.
The next indexing operation recreates the label if its key still exists in the source file.
Rendered frontend pages have their own cache; clear the relevant page cache if it still contains old text.

If generated files cannot be written or published, the production frontend logs the error and continues
with the previous generated loader if it is still available, or TYPO3's normal translation resources.
After a failure, generation is deferred for five minutes on that installation. Requests during this period
skip generation and duplicate error logging. The retry marker uses TYPO3's `hash` cache (database-backed by
default), independently of the generated-files directory. Translatr cache invalidation, including label edits,
resets the delay; otherwise the next request after expiry retries. Development contexts still raise the
exception and do not defer retries. Unexpected application errors are not suppressed.

After a system cache clear, the next indexing operation processes source files again because their hashes
are missing. A backend visit does this for the selected extension; the CLI can cover all configured extensions.
The frontend generator itself reads database records and does not index extension source files.

Upgrading to 8.0.0 preserves existing label records and translations. Missing hashes cause the indexer to
reuse those records rather than create duplicates. If a database backup is restored without matching hash
cache data, or unmodified default-language records are changed outside Translatr, run
`vendor/bin/typo3 translatr:labels:index --force` to reconcile them with source files.

## Functional checks

Run static analysis and all functional checks with one command:

```bash
ddev make check
```

Use `ddev make test` for functional checks alone, or run individual scripts below.

Run the indexing regression checks against both local DDEV test instances:

```bash
ddev exec --dir /var/www/html/v13 php /var/www/translatr/Tests/Functional/LabelIndexer.php
ddev exec --dir /var/www/html/v14 php /var/www/translatr/Tests/Functional/LabelIndexer.php
```

The checks use temporary source files, isolated cache keys and a database transaction that is rolled back.
They cover existing records, manual edits, translations, metadata, changed and unchanged files, forced refresh,
cache loss, failed indexing, DataHandler deletion, concurrent source replacement, XML validation, database
reads/writes and persisted filters. Run them on a development/test installation.

Check generated-file publication and recovery in both application contexts:

```bash
ddev exec --dir /var/www/html/v13 php /var/www/translatr/Tests/Functional/GenerateLanguageFiles.php Production
ddev exec --dir /var/www/html/v13 php /var/www/translatr/Tests/Functional/GenerateLanguageFiles.php Development
ddev exec --dir /var/www/html/v14 php /var/www/translatr/Tests/Functional/GenerateLanguageFiles.php Production
ddev exec --dir /var/www/html/v14 php /var/www/translatr/Tests/Functional/GenerateLanguageFiles.php Development
```

These checks use an isolated temporary directory. They cover failed directory creation, XLF and loader writes,
loader publication, restoration of previous output after a failed directory swap, cleanup and successful retries.
They also verify frontend fallback and logging in Production, visible failures in Development, and propagation
of unexpected errors. The generator always reports failures to its caller; the frontend middleware handles
known filesystem failures in Production as described above.
If restoring the previous directory also fails, it is retained at the backup path named in the exception.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Version compatibility and maintenance

Only **Translatr 8.0.0** is currently maintained. Older versions are no longer maintained and do not
receive bug fixes or security updates. Maintenance status below refers to Translatr, not to TYPO3 itself.

| Translatr version | Compatible TYPO3 versions | Currently maintained |
| --- | --- | --- |
| **8.0.0** | 13.4; 14.3 and later 14.x | **Yes** |
| 7.1.x | 13.4; 14.3 and later 14.x | No |
| 7.0.x | 13.4 | No |
| 6.0.x | 12.4 | No |
| 5.0.x | 9.5, 10.4, 11.5 | No |
| 4.0.x | 9.5, 10.4, 11.5 | No |
| 3.0.x | 9.5, 10.4 | No |
| 2.0.x | 9.5, 10.4 | No |
| 1.0.0 | 8.7, 9.5 | No |
| 0.9.5–0.9.8 | 8.7, 9.5 | No |
| 0.8.1–0.9.4 | 7.6, 8.7, 9.5 | No |
| 0.1.0–0.8.0 | 7.6, 8.7 | No |

Historical compatibility is based on the Composer requirements in the corresponding Git tags and does not
mean those releases are tested or maintained today. Some older releases have different constraints in
`ext_emconf.php`; consult the exact release metadata when using the Extension Manager.
The current Composer constraint is `^13.4 || ^14.3`.
