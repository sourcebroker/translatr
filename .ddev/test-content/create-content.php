<?php

/**
 * Creates frontend test content for EXT:translatr in a TYPO3 test instance:
 * a news storage folder with news records and a news list plugin on the root page,
 * all localized to Polish (languageId 1) and German (languageId 2).
 *
 * The news list renders labels of EXT:news (e.g. "more-link" = "Read more"), which can be
 * overridden with the translatr backend module and checked in the frontend.
 *
 * Usage (from the TYPO3 instance directory): php /mnt/ddev_config/test-content/create-content.php
 */

use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$classLoader = require getcwd() . '/vendor/autoload.php';
SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
$container = Bootstrap::init($classLoader);
Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
$GLOBALS['BE_USER']->authenticate();
$GLOBALS['LANG'] = $container->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);

const ROOT_PAGE = 1;
const LANGUAGES = [1 => 'pl', 2 => 'de'];

$existingFolder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages')
    ->select(['uid'], 'pages', ['pid' => ROOT_PAGE, 'doktype' => 254, 'title' => 'News', 'deleted' => 0])
    ->fetchOne();
if ($existingFolder) {
    echo "Test content already exists (news folder uid $existingFolder) - skipping\n";
    exit(0);
}

function process(array $data, array $cmd = []): DataHandler
{
    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start($data, $cmd);
    $dataHandler->process_datamap();
    $dataHandler->process_cmdmap();
    if ($dataHandler->errorLog !== []) {
        fwrite(STDERR, implode(PHP_EOL, $dataHandler->errorLog) . PHP_EOL);
        exit(1);
    }
    return $dataHandler;
}

// Default language records
$news = [
    ['title' => 'First test news', 'teaser' => 'Teaser of the first test news.'],
    ['title' => 'Second test news', 'teaser' => 'Teaser of the second test news.'],
    ['title' => 'Third test news', 'teaser' => 'Teaser of the third test news.'],
];
$data = [
    'pages' => [
        ROOT_PAGE => ['title' => 'Home'],
        'NEW_folder' => ['pid' => ROOT_PAGE, 'title' => 'News', 'doktype' => 254],
    ],
    'tt_content' => [
        'NEW_text' => [
            'pid' => ROOT_PAGE,
            'CType' => 'text',
            'header' => 'EXT:translatr frontend test',
            'bodytext' => '<p>The news list below renders labels of EXT:news, e.g. the "Read more" link (news, locallang.xlf, key: more-link). Override them in the backend module "Translate" and reload this page.</p>',
        ],
        'NEW_plugin' => ['pid' => ROOT_PAGE, 'CType' => 'news_pi1', 'header' => 'News list', 'colPos' => 0],
    ],
];
foreach ($news as $i => $item) {
    $data['tx_news_domain_model_news']['NEW_news' . $i] = $item + [
        'pid' => 'NEW_folder',
        'datetime' => time() - $i * 86400,
        'bodytext' => '<p>Body text of the test news.</p>',
    ];
}
// Keep the order of the content elements (text first)
$data['tt_content']['NEW_plugin']['pid'] = '-NEW_text';
$dataHandler = process($data);
$folderUid = (int)$dataHandler->substNEWwithIDs['NEW_folder'];
$records = [
    'pages' => [ROOT_PAGE],
    'tt_content' => [(int)$dataHandler->substNEWwithIDs['NEW_text'], (int)$dataHandler->substNEWwithIDs['NEW_plugin']],
    'tx_news_domain_model_news' => array_map(
        static fn(int $i): int => (int)$dataHandler->substNEWwithIDs['NEW_news' . $i],
        array_keys($news)
    ),
];

// Translations: the page first, then its content and the news records
foreach (LANGUAGES as $languageId => $isoCode) {
    foreach ($records as $table => $uids) {
        $cmd = [];
        foreach ($uids as $uid) {
            $cmd[$table][$uid]['localize'] = $languageId;
        }
        $dataHandler = process([], $cmd);
        // Replace the "[Translate to ...]" prefixes with readable titles
        $update = [];
        foreach ($dataHandler->copyMappingArray_merged[$table] ?? [] as $uid => $localizedUid) {
            $field = $table === 'tt_content' ? 'header' : 'title';
            $original = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)
                ->select([$field], $table, ['uid' => $uid])->fetchOne();
            $update[$table][$localizedUid] = [$field => $original . ' (' . $isoCode . ')', 'hidden' => 0];
        }
        process($update);
    }
}

// News storage folder for the news list plugin
file_put_contents(
    getcwd() . '/config/sites/main/setup.typoscript',
    PHP_EOL . 'plugin.tx_news.settings.startingpoint = ' . $folderUid . PHP_EOL,
    FILE_APPEND
);

echo 'Test content created: news folder uid ' . $folderUid . ', ' . count($news) . " news, localized to "
    . implode(', ', LANGUAGES) . PHP_EOL;
