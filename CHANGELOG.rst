Changelog
---------

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
