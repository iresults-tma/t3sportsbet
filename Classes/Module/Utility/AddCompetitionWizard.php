<?php

namespace Sys25\T3sportsbet\Module\Utility;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2008-2019 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

/**
 * Die Klasse zeigt Objekte im Backend an.
 */
class AddCompetitionWizard
{
    /**
     * Handle the wizard.
     *
     * @param \tx_rnbase_mod_IModule $mod
     * @param \tx_t3sportsbet_models_betgame $betgame
     *
     * @return string
     */
    public function handleRequest(&$mod, $betgame)
    {
        $this->mod = $mod;
        $this->doc = $mod->getDoc();
        $this->formTool = $mod->getFormTool();
        $comp2set = strlen(\Tx_Rnbase_Utility_T3General::_GP('comp2betset')) > 0; // Wurde der Submit-Button gedrückt?
        $out = '';
        if ($comp2set) {
            $out .= $this->handleCompetition2Betgame($betgame);
        } else {
            $out .= $this->showInfoPage($betgame);
        }

        return $out;
    }

    /**
     * Zeigt die Infoseite mit den möglichen Optionen.
     *
     * @param \tx_t3sportsbet_models_betgame $betgame
     *
     * @return string
     */
    private function showInfoPage($betgame)
    {
        $out .= $this->doc->section('###LABEL_INFO###:', $GLOBALS['LANG']->getLL('msg_add_competition'), 0, 1, \tx_rnbase_mod_IModFunc::ICON_INFO);
        $out .= $this->doc->spacer(15);

        $comps = $betgame->getCompetitions();
        $options = [];

        if (!count($comps)) {
            $out .= $this->doc->section('###LABEL_INFO###:', $GLOBALS['LANG']->getLL('msg_no_competition_in_betgame'), 0, 1, \tx_rnbase_mod_IModFunc::ICON_WARN);
            $options['title'] = '###LABEL_EDITBETGAME###';
            $out .= $this->formTool->createEditButton('tx_t3sportsbet_betgames', $betgame->getUid(), $options);
        } else {
            $menu = $this->getCompMenu($comps);
            $out .= '<div><span class="selector col-md-4">'.$menu['menu'].'</span></div><div style="clear:both;"></div>';
            $out .= $this->doc->spacer(15);
            $out .= $this->formTool->createSubmit('comp2betset', '###LABEL_JOIN_COMPETITION###', $GLOBALS['LANG']->getLL('msg_join_competition'));
        }
        $out .= $this->doc->spacer(15);
        // t3lib_div::debug($betgame->getCompetitions(), 'tx_t3sportsbet_mod1_addCompetitionWizard'); // TODO: remove me

        // $out .= $this->handleCompetition2Betgame($searcher->getCompetition());
        $params = [];
        $params['params'] = '&betgame='.$betgame->getUid();
        $params['params'] .= '&round='.($betgame->getBetSetSize() + 1);
        $params['title'] = '###LABEL_CREATE_BETSET###';
        $out .= $this->formTool->createNewButton('tx_t3sportsbet_betsets', $this->mod->getPid(), $params);

        return $out;
    }

    /**
     * Erstellt aus dem aktuellen Wettbewerb die notwendigen Tiprunden.
     *
     * @param \tx_t3sportsbet_models_betgame $betgame
     *
     * @return string
     */
    private function handleCompetition2Betgame($betgame)
    {
        $menu = $this->getCompMenu($betgame->getCompetitions());
        $compId = $menu['value'];
        $matches = $this->loadMatches($compId);
        if (!count($matches)) {
            return $this->doc->section('###LABEL_INFO###:', $GLOBALS['LANG']->getLL('msg_no_matchs_found'), 0, 1, \tx_rnbase_mod_IModFunc::ICON_WARN);
        }

        $lastRound = -1;
        $rounds = [];
        foreach ($matches as $match) {
            $round = intval($match->getProperty('round'));
            if ($lastRound != $round) {
                $lastRound = $round;
            }
            // Alle UIDs einer Runde sammeln
            $rounds[$round][] = $match->getUid();
        }
        // Jetzt das Datenarray anlegen
        $data = array();
        foreach ($rounds as $key => $matchUids) {
            $data['tx_t3sportsbet_betsets']['NEW'.$key]['pid'] = $betgame->getPid();
            $data['tx_t3sportsbet_betsets']['NEW'.$key]['betgame'] = $betgame->getUid();
            $data['tx_t3sportsbet_betsets']['NEW'.$key]['t3matches'] = 'tx_cfcleague_games_'.implode(',tx_cfcleague_games_', $matchUids);
            $data['tx_t3sportsbet_betsets']['NEW'.$key]['status'] = 0;
            $data['tx_t3sportsbet_betsets']['NEW'.$key]['round'] = $key;
            $data['tx_t3sportsbet_betsets']['NEW'.$key]['round_name'] = $key.' ###LABEL_ROUNDNAMEDEFAULT###';
        }
        $tce = \Tx_Rnbase_Database_Connection::getInstance()->getTCEmain($data);
        $tce->process_datamap();
        $out .= $GLOBALS['LANG']->getLL('msg_add_competition_finished');

        return (strlen($out)) ? $this->mod->doc->section('###LABEL_INFO###:', $out, 0, 1, \tx_rnbase_mod_IModFunc::ICON_INFO) : '';
    }

    private function loadMatches($compId)
    {
        $matchTable = \tx_rnbase::makeInstance('tx_cfcleaguefe_util_MatchTable');
        $matchTable->setCompetitions($compId);
        $matchTable->setIgnoreDummy();
        $fields = $options = [];
        $options['orderby']['MATCH.ROUND'] = 'ASC';
        $options['orderby']['MATCH.DATE'] = 'ASC';
        $matchTable->getFields($fields, $options);
        $service = \tx_cfcleaguefe_util_ServiceRegistry::getMatchService();

        return $service->search($fields, $options);
    }

    private function getCompMenu($comps)
    {
        $menuData = [];
        foreach ($comps as $comp) {
            $menuData[$comp->getUid()] = $comp->getName();
        }

        return $this->formTool->showMenu($this->mod->getPid(), 'bettools', $this->mod->getName(), $menuData);
    }
}
