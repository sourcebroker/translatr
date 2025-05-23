Changelog
---------

7.0.0
~~~~~

1) [BREAKING] Support for TYPO3 13

6.0.0
~~~~~

1) [BREAKING] Support only TYPO3 12

5.0.0
~~~~~

1) [BREAKING] Show edit ability for default language always.
2) [BREAKING] Move generated files path to Environment::getVarPath() . '/tx_translatr'. Breaking as this files
   can be shared in deploys.

4.0.3
~~~~~

1) [BUGFIX] Fix not validating composer.json.

4.0.2
~~~~~

1) [BUGFIX] In TYPO3 11 there is possibility to drop ``ext_emconf.php``. ext:translator was using ``ext_emconf.php`` to
   build some extended info about extensions, mainly to show ``[ext name] ([ext title])`` pair. With TYPO3 11 and
   possibility to drop ``ext_emconf.php`` there is no longer ``title`` so the pair ``[ext name] ([ext title])`` can not be
   guaranteed. This allows to drop ``ExtensionsUtility.php`` and move extension list get directly to ``LabelRepository.php``


4.0.1
~~~~~

1) [BUGFIX] Fix command not registered in TYPO3 11 because lack of Services.yaml config.

4.0.0
~~~~~

1) [BUGFIX] Fix sql strict error for grouping.
2) [BUGFIX] Check for languages node.
3) [BUGFIX] Remove not needed use declaration.
4) [FEATURE] Add ddev for testing extension with different TYPO3 versions.
5) [BUGFIX] Fix compatibility for TYPO3 9.5 for registering backend module.
6) [TASK] Remove support for database operation for TYPO3 before 8.7.
7) [TASK] Remove dependency to vhs. Remove dependency to php. Add dependency to TYPO3 11.
8) [BUGFIX] Fix loading JS for TYPO3 11.

3.0.7
~~~~~

1) [BUGFIX] Fix missing "extension-key" in composer.json.
2) [TASK] Add ddev.

3.0.6
~~~~~

1) [BUGFIX] Add support for using full label path (without "EXT:")


3.0.5
~~~~~

1) [BUGFIX] Change from Hook to Middleware to fix not working hook in TYPO3 10.

3.0.4
~~~~~

1) [BUGFIX] Fix path to TsConfig.

3.0.3
~~~~~

1) [BUGFIX] Fix version in ext_emconf.php

3.0.2
~~~~~

1) [BUGFIX] Bugfix not working editing on TYPO3 10.

3.0.1
~~~~~

1) [BUGFIX] Fix creating new records form cli level.

3.0.0
~~~~~

1) [TASK][BREAKING] Remove special toolbar menu item to remove cache of translations. Cache is clean now on every cache clean (pages, all, system).
2) [TASK] Add lock when creating files.

2.0.2
~~~~~

1) [BUGFIX] Fix deprecated TCA.

2.0.1
~~~~~

1) [BUGFIX] Fix missing class.

2.0.0
~~~~~

1) [TASK] Increase version to TYPO3 10.4.
2) [TASK][BREAKING] Disable language list by default in Configuration/TsConfig/Page/tx_translatr.tsconfig.
3) [TASK][BREAKING] Drop support for TYPO3 8.7.
4) [TASK][BREAKING] Rename task name from ``lang:import:config`` to ``translatr:import:config``.
5) [TASK] Refactor for TYPO3 10 / remove code support for TYPO3 8.7 and below.

1.0.0
~~~~~

1) [TASK] Fix composer.json validation.

0.9.8
~~~~~

1) [FEATURE] Override also labels in default language if flag modify doesn't exist

0.9.7
~~~~~

1) [FIX] Fix problem with rendering BE list when no language is selected.
2) [FEATURE] Create missing default labels from extension before each task execution.

0.9.6
~~~~~

1) [FIX] Fix problem with missing tags after create new translation from BE.
2) [FIX] Fix wrong extension name at creating new translation from BE.
3) [FIX] Fix not working button to remove from list in TYPO3 9.x.
4) [FEATURE] Implement flag to determine if record was edited from BE.
5) [FEATURE] Implement common cache cleaner service for whole ext.

0.9.5
~~~~~

1) [FEATURE] Add possibility to import configuration and use tags for labels
2) [TASK] Change dependency to TYPO3 and ext:vhs.

0.9.4
~~~~~
1) [TASK] Store records on zero pid.
2) [TASK] TCA optimisations.
3) [BUGFIX] Fix wrong table for ConnectionPool.
4) [TASK] Cleanup not needed fields and functionality.
5) [TASK] Add support for translation of backend files.
6) [TASK] Read lang overwrite files directly if exists.

0.9.3
~~~~~
1) [BUGFIX] Records should not be stored in pid 0 because regular users can not edit on pid 0.
   Make it the default pid for extbase "create record".

0.9.2
~~~~~
1) [TASK] Add scrutinizer config

0.9.1
~~~~~
1) [BUGFIX] Fix cache folder name.

0.9.0
~~~~~
1) [FEATURE] Store info about last edited file and language.
2) [BUGFIX] Fix error on cache clean.

0.8.5
~~~~~
1) [TASK] Change ext:vhs restriction.
2) [BUGFIX] Create database class for compatibility of db operations for TYPO3 9.5 and before.

0.8.4
~~~~~
1) [BUGFIX] Fix wrong TYPO3 restriction version for conditions.
2) [TASK] Optimize function usage.
3) [BREAKING] Move generated files from typo3temp folder to uploads folder.

0.8.3
~~~~~
1) [BUGFIX] Fix wrong TYPO3 restriction version for conditions.

0.8.2
~~~~~
1) [TASK] Update version restrictions.

0.8.1
~~~~~
1) [TASK] Update version restrictions.

0.8.0
~~~~~
1) [TASK] Compatibility with TYPO3 9.5

0.8.0
~~~~~
1) [TASK] Compatibility with TYPO3 9.5

0.7.0
~~~~~
1) [TASK] Extend compatibility to ext:vhs to version 4.4.
2) [TASK] Add .Build/Web for future tests and for IDE.

0.6.2
~~~~~
1) Add docs images.

0.6.1
~~~~~
1) Add docs / add changelog.
