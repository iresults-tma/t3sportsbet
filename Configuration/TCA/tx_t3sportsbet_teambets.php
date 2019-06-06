<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$tx_t3sportsbet_tb = Array (
    'ctrl' => Array (
        'title' => 'LLL:EXT:t3sportsbet/locallang_db.xml:tx_t3sportsbet_teambets',
        'label' => 'question',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => Array (
            ),
        'iconfile' => tx_rnbase_util_Extensions::extRelPath('t3sportsbet').'icon_table.gif',
     ),
    'interface' => Array (
        'showRecordFieldList' => 't3match'
        ),
    'feInterface' => Array (
        'fe_admin_fieldList' => '',
    ),
    'columns' => Array (
        'question' => Array (
            'exclude' => 0,
            'label' => 'LLL:EXT:t3sportsbet/locallang_db.xml:tx_t3sportsbet_teamquestions',
            'config' => Array (
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_t3sportsbet_teamquestions',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                )
            ),
        'feuser' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:cms/locallang_tca.php:fe_users',
            'config' => Array (
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                )
            ),
        
        'finished' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:t3sportsbet/locallang_db.xml:tx_t3sportsbet_bets.finished',
            'config' => Array (
                'type' => 'check',
                'default' => '0'
                )
            ),
        'team' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:t3sportsbet/locallang_db.xml:tx_t3sportsbet_bets.goals_home',
            'config' => Array (
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_cfcleague_teams',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                )
            ),
        'points' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:t3sportsbet/locallang_db.xml:tx_t3sportsbet_bets.points',
            'config' => Array (
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'eval' => 'int',
                'range' => Array (
                    'upper' => '1000',
                    'lower' => '0'
                    ),
                'default' => 0
                )
            ),
        'possiblepoints' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:t3sportsbet/locallang_db.xml:tx_t3sportsbet_teambets_possiblepoints',
            'config' => Array (
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'eval' => 'int',
                'range' => Array (
                    'upper' => '1000',
                    'lower' => '0'
                    ),
                'default' => 0
                )
            ),
        ),
    'types' => Array (
        '0' => Array('showitem' => 'question,feuser,team,finished,possiblepoints,points')
    ),
    'palettes' => Array (
        '1' => Array('showitem' => '')
    )
);

return $tx_t3sportsbet_tb;