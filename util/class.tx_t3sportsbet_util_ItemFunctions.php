<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2017 Rene Nitzsche (rene@system25.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Diese Klasse ist für die Erstellung von Auswahllisten in TCEforms verantwortlich
 */
class tx_t3sportsbet_util_ItemFunctions
{

    /**
     * Used in flexform to lookup betset for a betgame in highscore list
     *
     * @param array $config
     */
    public function getBetSet4BetGame($config)
    {
        if (! $config['row']['pi_flexform'])
            return;
        $flex = t3lib_div::xml2array($config['row']['pi_flexform']);
        $betgameUid = $flex['data']['sDEF']['lDEF']['scope.betgame']['vDEF'];
        if (! $betgameUid)
            return;
        
        $options['where'] = 'tx_t3sportsbet_betsets.betgame = ' . $betgameUid;
        tx_rnbase::load('tx_rnbase_util_Misc');
        tx_rnbase_util_Misc::prepareTSFE();
        $records = tx_rnbase_util_DB::doSelect('round_name, uid', 'tx_t3sportsbet_betsets', $options, 0);
        foreach ($records as $record) {
            $config['items'][] = array_values($record);
        }
    }

    /**
     * Used in TCA.
     * Return all teams of a betsets betgame.
     * 
     * @param array $PA
     * @param t3lib_TCEforms $fobj
     */
    public function getTeams4TeamBet($PA, $fobj)
    {
        if ($PA['row']['betset']) {
            $betset = $this->loadBetset($PA['row']['betset']);
            if (! $PA['row']['uid'])
                return;
            $teamQuestion = tx_rnbase::makeInstance('tx_t3sportsbet_models_teamquestion', $PA['row']['uid']);
            $betgame = $betset->getBetgame();
            $teams = tx_t3sportsbet_util_serviceRegistry::getTeamBetService()->getTeams4TeamQuestion($teamQuestion);
            foreach ($teams as $team) {
                $PA['items'][] = array(
                    $team->getName(),
                    $team->getUid()
                );
            }
        }
    }

    /**
     * Load a betset from database
     * 
     * @param int $uid
     * @return tx_t3sportsbet_models_betset
     */
    private function loadBetset($fieldString)
    {
        $arr = Tx_Rnbase_Utility_Strings::trimExplode('|', $fieldString);
        $arr = Tx_Rnbase_Utility_Strings::trimExplode('_', $arr[0]);
        $uid = intval($arr[count($arr) - 1]);
        return $uid ? tx_rnbase::makeInstance('tx_t3sportsbet_models_betset', $uid) : false;
    }
}
