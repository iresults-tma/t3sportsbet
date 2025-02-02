<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2014 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_t3sportsbet_util_library');
tx_rnbase::load('tx_rnbase_util_Misc');

define('T3SPORTSBET_OPEN', 1);
define('T3SPORTSBET_CLOSED', 2);

interface tx_t3sportsbet_DataProvider
{
    public function setBetGame($game);

    public function getRounds($status);

    public function getMatchesByRound($round);
}

/**
 * @author Rene Nitzsche
 */
class tx_t3sportsbet_services_bet extends Tx_Rnbase_Service_Base
{
    /**
     * Returns all rounds with open matches.
     *
     * @param tx_t3sportsbet_models_betgame $betgame
     *
     * @return array[tx_t3sportsbet_models_betset]
     */
    public function getOpenRounds(&$betgame)
    {
        return $this->getRounds($betgame, T3SPORTSBET_OPEN);
    }

    /**
     * Returns all rounds with closed matches.
     *
     * @param tx_t3sportsbet_models_betgame $betgame
     *
     * @return array[tx_t3sportsbet_models_betset]
     */
    public function getClosedRounds($betgame)
    {
        return $this->getRounds($betgame, T3SPORTSBET_CLOSED);
    }

    /**
     * Returns an array with all uids of matches within a $betgame.
     *
     * @param tx_t3sportsbet_models_betgame $betgame
     */
    public function findMatchUids($betgame)
    {
        $from = array(
            'tx_t3sportsbet_betsets JOIN tx_t3sportsbet_betsets_mm ON tx_t3sportsbet_betsets_mm.uid_local = tx_t3sportsbet_betsets.uid',
            'tx_t3sportsbet_betsets',
        );
        $options = [];
        $options['where'] = 'tx_t3sportsbet_betsets.betgame = '.intval($betgame->getUid());

        return Tx_Rnbase_Database_Connection::getInstance()->doSelect('tx_t3sportsbet_betsets_mm.uid_foreign as uid', $from, $options, 0);
    }

    /**
     * Fill table betsetresults with values for all bets of a betgame.
     *
     * @param tx_t3sportsbet_betgame $betGame
     */
    public function updateBetsetResultsByGame(tx_t3sportsbet_models_betgame $betGame)
    {
        // Die Spalte hasresults ist für den Update von älteren Daten vor Einführung der Tabelle
        // tx_t3sportsbet_betsetresults notwendig.
        $betgameWhere = 'tx_t3sportsbet_betsets.betgame='.$betGame->getUid().' AND (tx_t3sportsbet_betsets.status = 1 OR (tx_t3sportsbet_betsets.status = 2 AND tx_t3sportsbet_betsets.hasresults = 0))';

        // Remove old data
        $delWhere = 'tx_t3sportsbet_betsetresults.betset IN('.'SELECT uid FROM tx_t3sportsbet_betsets WHERE '.$betgameWhere.' )';

        Tx_Rnbase_Database_Connection::getInstance()->doDelete('tx_t3sportsbet_betsetresults', $delWhere);

        $sqlQuery = '
INSERT INTO tx_t3sportsbet_betsetresults (feuser,points,betset,bets,pid,tstamp,crdate)
SELECT feuser, sum(points), betset, count(bets),'.$betGame->getProperty('pid').', UNIX_TIMESTAMP(),UNIX_TIMESTAMP() FROM (
  SELECT b.uid, b.fe_user AS feuser, b.points, b.betset, b.uid AS bets
   FROM `tx_t3sportsbet_bets` As b
     JOIN tx_t3sportsbet_betsets ON tx_t3sportsbet_betsets.UID = b.betset
   WHERE '.$betgameWhere.'
  UNION
  SELECT tb.uid, tb.feuser, tb.points, tq.betset, tb.uid AS bets
   FROM `tx_t3sportsbet_teambets` tb
     JOIN tx_t3sportsbet_teamquestions tq ON tb.question = tq.uid
     JOIN tx_t3sportsbet_betsets ON tx_t3sportsbet_betsets.UID = tq.betset
   WHERE '.$betgameWhere.' '.Tx_Rnbase_Database_Connection::getInstance()->enableFields('tx_t3sportsbet_teamquestions', 0, 'tq').'
) AS dt
GROUP BY feuser, betset
';
        $ok = Tx_Rnbase_Database_Connection::getInstance()->doQuery($sqlQuery);

        // Jetzt die Spalte hasresults füllen
        $values = array(
            'hasresults' => 1,
        );
        Tx_Rnbase_Database_Connection::getInstance()->doUpdate('tx_t3sportsbet_betsets', $betgameWhere, $values);

        /*
         *
         *
         * SELECT feuser, sum(points), betset FROM (
         * SELECT b.fe_user AS feuser, b.points, b.betset
         * FROM `tx_t3sportsbet_bets` As b
         * UNION
         * SELECT tb.feuser, tb.points, tq.betset
         * FROM `tx_t3sportsbet_teambets` tb JOIN tx_t3sportsbet_teamquestions tq ON tb.question = tq.uid
         * ) AS dt
         *
         * GROUP BY feuser, betset
         *
         */
    }

    /**
     * Analyze bets of a betgame.
     *
     * @param tx_t3sportsbet_models_betgame $betGame
     *
     * @return int number of finished bets
     */
    public function analyzeBets($betGame)
    {
        // Ablauf
        // Tips ohne Auswertung, deren Spiele beendet sind
        $fields = $options = [];
        $fields['BETSET.BETGAME'][OP_EQ_INT] = $betGame->getUid();
        $fields['BET.FINISHED'][OP_EQ_INT] = 0;
        $fields['MATCH.STATUS'][OP_EQ_INT] = 2;
        // $options['debug'] = 1;
        // This could be memory consuming...
        $bets = $this->searchBet($fields, $options);
        $ret = 0;
        $service = tx_t3sportsbet_util_serviceRegistry::getCalculatorService();
        for ($i = 0, $cnt = count($bets); $i < $cnt; ++$i) {
            $bet = $bets[$i];
            $values = [
                'finished' => 1,
                'points' => intval($service->calculatePoints($betGame, $bet)),
            ];
            $where = 'uid='.$bet->getUid();
            Tx_Rnbase_Database_Connection::getInstance()->doUpdate('tx_t3sportsbet_bets', $where, $values, 0);
            ++$ret;
        }
        // Hook to inform about updated bets
        tx_rnbase_util_Misc::callHook('t3sportsbet', 'srv_Bet_analysebets_finished_hook', array(
            'calculatedBets' => $ret,
            'betgame' => $betGame,
        ), $this);

        return $ret;
    }

    /**
     * Liefert das höchste und niedrigste Datum von Spielen in einem Tipspiel.
     *
     * @param tx_t3sportsbet_models_betset $betset
     *
     * @return array keys: high and low, values are timestamps or false if no match is set
     */
    public function getBetsetDateRange(&$betset)
    {
        $matches = $betset->getMatches();
        if (!count($matches)) {
            return false;
        }
        $today = time();
        $high = array(
            $matches[0]->getDate(),
            $matches[0],
        );
        $low = array(
            $matches[0]->getDate(),
            $matches[0],
        );
        $next = $matches[0]->getDate() > $today ? array(
            $matches[0]->getDate(),
            $matches[0],
        ) : array(
            0,
            0,
        );
        for ($i = 1, $cnt = count($matches); $i < $cnt; ++$i) {
            $match = $matches[$i];
            if ($match->getDate() < $low[0]) {
                $low = array(
                    $match->getDate(),
                    $match,
                );
            }
            if ($match->getDate() >= $high[0]) {
                $high = array(
                    $match->getDate(),
                    $match,
                );
            }
            if ((0 == $next[0] || $match->getDate() < $next[0]) && $match->getDate() > $today) {
                $next = array(
                    $match->getDate(),
                    $match,
                );
            }
        }
        $next = $next[0] > 0 ? $next : 0;

        return array(
            'high' => $high,
            'low' => $low,
            'next' => $next,
        );
    }

    /**
     * Move a match and all bets from one betset to another.
     *
     * @param int $newBetsetUid
     * @param int $oldBetsetUid
     * @param int $matchUid
     *
     * @return int number of bets moved
     */
    public function moveMatch($newBetsetUid, $oldBetsetUid, $matchUid)
    {
        // Zuordnung Spiel im neuen Betset anlegen -> Exception, wenn schon vorhanden
        $newBetSet = tx_rnbase::makeInstance('tx_t3sportsbet_models_betset', $newBetsetUid);
        $matchesInNewBetSet = $this->findMatchUidsByBetSet($newBetSet);
        if (in_array($matchUid, $matchesInNewBetSet)) {
            throw new Exception('Match is already in betset');
        }
        // Zuordnung Spiel im alten Betset entfernen
        $where = 'uid_local='.$oldBetsetUid.' AND uid_foreign='.$matchUid.' AND tablenames=\'tx_cfcleague_games\'';
        $rows = tx_rnbase_util_DB::doUpdate('tx_t3sportsbet_betsets_mm', $where, array(
            'uid_local' => $newBetsetUid,
        ));
        if (0 == $rows) {
            throw new Exception('Match ('.$matchUid.') not found in old betset ('.$oldBetsetUid.')!');
        }
        // Alle Bets auf das neue Betset umstellen
        $where = 'betset='.$oldBetsetUid.' AND t3match='.$matchUid;
        $rows = Tx_Rnbase_Database_Connection::getInstance()->doUpdate('tx_t3sportsbet_bets', $where, array(
            'betset' => $newBetsetUid,
        ));

        return $rows;
    }

    /**
     * Return an array with all match uids of a betset.
     *
     * @param tx_t3sportsbet_models_betset $betset
     */
    public function findMatchUidsByBetSet($betset)
    {
        $fields = $options = [];
        $betsetUid = is_object($betset) ? $betset->getUid() : intval($betset);
        $service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
        $fields['BETSETMM.UID_LOCAL'][OP_EQ_INT] = $betsetUid;
        $options['orderby']['BETSETMM.SORTING'] = 'asc';
        $options['what'] = 'uid';
        $result = $service->search($fields, $options);
        $ret = array();
        for ($i = 0, $cnt = count($result); $i < $cnt; ++$i) {
            $ret[] = $result[$i]['uid'];
        }

        return $ret;
    }

    /**
     * Reset bets for a given match on a given betset.
     *
     * @param tx_t3sportsbet_models_betset $betset
     * @param int $matchUid
     */
    public function resetBets($betset, $matchUid)
    {
        // UPDATE tx_t3sportsbet_bets SET finished=0, points=0 WHERE betset = 123 AND t3match=12
        $values = [
            'finished' => 0,
            'points' => 0,
        ];
        $where = 'betset='.$betset->getUid().' AND t3match='.intval($matchUid);
        Tx_Rnbase_Database_Connection::getInstance()->doUpdate('tx_t3sportsbet_bets', $where, $values, 0);
    }

    /**
     * Returns the number of bets for a betset.
     *
     * @param tx_t3sportsbet_models_betset $betset
     */
    public function getBetSize($betset)
    {
        $fields = $options = [];
        $fields['BET.BETSET'][OP_EQ_INT] = $betset->getUid();
        $options['count'] = 1;

        return $this->searchBet($fields, $options);
    }

    /**
     * Returns the bet for a user on a single match
     * If no bet is found this method return a dummy instance of tx_t3sportsbet_models_bet
     * with uid=0.
     *
     * @param tx_t3sportsbet_models_betset $betset
     * @param tx_cfcleaguefe_models_match $match
     * @param tx_t3users_models_feuser $feuser
     *
     * @return tx_t3sportsbet_models_bet
     */
    public function getBet($betset, $match, $feuser)
    {
        $fields = $options = [];
        $ret = array();
        if ($feuser) {
            // Ohne FE-User kann die DB-Abfragen gespart werden
            $fields['BET.BETSET'][OP_EQ_INT] = $betset->uid;
            $fields['BET.T3MATCH'][OP_EQ_INT] = $match->uid;
            $fields['BET.FE_USER'][OP_EQ_INT] = $feuser->uid;
            // $options['debug'] = 1;
            $ret = $this->searchBet($fields, $options);
        }

        $bet = count($ret) ? $ret[0] : null;
        if (!$bet) {
            // No bet in database found. Create dummy instance
            $bet = tx_rnbase::makeInstance('tx_t3sportsbet_models_bet', array(
                'uid' => 0,
                'betset' => $betset->uid,
                'fe_user' => $feuser->uid,
                't3match' => $match->uid,
            ));
        }

        return $bet;
    }

    /**
     * Returns all bets on a single match.
     *
     * @param tx_t3sportsbet_models_betset $betset
     * @param tx_cfcleaguefe_models_match $match
     *
     * @return array[tx_t3sportsbet_models_bet]
     */
    public function getBets($betset, $match)
    {
        $fields = $options = [];
        $fields['BET.BETSET'][OP_EQ_INT] = $betset->uid;
        $fields['BET.T3MATCH'][OP_EQ_INT] = $match->uid;

        return $this->searchBet($fields, $options);
    }

    /**
     * Returns the bet trend for a single match.
     *
     * @param tx_t3sportsbet_models_betset $betset
     * @param tx_cfcleaguefe_models_match $match
     *
     * @return array
     */
    public function getBetTrend($betset, $match)
    {
        $fields = $options = [];
        // Wir suchen jeweils die Anzahl der Tips
        $options['count'] = 1;
        $fields['BET.BETSET'][OP_EQ_INT] = $betset->getUid();
        $fields['BET.T3MATCH'][OP_EQ_INT] = $match->getUid();
        $fields[SEARCH_FIELD_CUSTOM] = 'tx_t3sportsbet_bets.goals_home > tx_t3sportsbet_bets.goals_guest';

        $ret = [];
        $ret['trendhome'] = $this->searchBet($fields, $options);
        $fields[SEARCH_FIELD_CUSTOM] = 'tx_t3sportsbet_bets.goals_home = tx_t3sportsbet_bets.goals_guest';
        $ret['trenddraw'] = $this->searchBet($fields, $options);
        $fields[SEARCH_FIELD_CUSTOM] = 'tx_t3sportsbet_bets.goals_home < tx_t3sportsbet_bets.goals_guest';
        $ret['trendguest'] = $this->searchBet($fields, $options);
        $sum = $ret['trendhome'] + $ret['trenddraw'] + $ret['trendguest'];
        $sum = $sum ? $sum : 1;
        $ret['trendhomep'] = round($ret['trendhome'] / $sum * 100);
        $ret['trenddrawp'] = round($ret['trenddraw'] / $sum * 100);
        $ret['trendguestp'] = round($ret['trendguest'] / $sum * 100);

        return $ret;
    }

    /**
     * Returns the bet statistics for a single match.
     *
     * @param tx_t3sportsbet_models_betset $betset
     * @param tx_cfcleaguefe_models_match $match
     *
     * @return array
     */
    public function getBetStats($betset, $match)
    {
        $fields = $options = [];
        // Wieviele Tipper haben das Ergebnis richtig
        $calcSrv = tx_t3sportsbet_util_serviceRegistry::getCalculatorService();
        list($goalsHome, $goalsGuest) = $calcSrv->getGoals($betset->getBetgame(), $match);
        // $goalsHome = $match->
        $options['count'] = 1;
        $fields['BET.BETSET'][OP_EQ_INT] = $betset->getUid();
        $fields['BET.T3MATCH'][OP_EQ_INT] = $match->getUid();
        $fields[SEARCH_FIELD_CUSTOM] = 'tx_t3sportsbet_bets.goals_home = '.$goalsHome;
        $fields[SEARCH_FIELD_CUSTOM] .= ' AND tx_t3sportsbet_bets.goals_guest = '.$goalsGuest;
        $ret = [];
        $ret['stats_accurate'] = $this->searchBet($fields, $options);

        return $ret;
    }

    /**
     * Returns the number of users with bets for given bet rounds.
     *
     * @param string $betsetUids
     *            comma separated uids of betsets
     *
     * @return int
     */
    public function getResultSize($betsetUids)
    {
        $fields = $options = [];
        $userSrv = tx_t3users_util_ServiceRegistry::getFeUserService();
        if ($betsetUids) {
            $fields['BET.BETSET'][OP_IN_INT] = $betsetUids;
        }
        $fields['BET.FE_USER'][OP_GT_INT] = 0;
        $options['count'] = 1;
        $options['distinct'] = 1;
        // $options['debug'] = 1;
        return $userSrv->search($fields, $options);
    }

    /**
     * Returns the points and standing for a single user.
     *
     * @param string $betsetUids
     *            comma separated uids of betsets
     * @param string $feuserUids
     *            comma separated uids of feuserUids to mark
     */
    public function getResults($betsetUids, $feuserUids = '0')
    {
        $fields = $options = [];
        if ('old' == $_GET['resultmode']) {
            if ($betsetUids) {
                $fields['BET.BETSET'][OP_IN_INT] = $betsetUids;
            }
            $fields['BET.FE_USER'][OP_GT_INT] = 0;
            $options['what'] = '
				fe_users.uid, sum(tx_t3sportsbet_bets.points) AS betpoints,
				sum(tx_t3sportsbet_bets.finished) AS betcount
				';
        } else {
            if ($betsetUids) {
                $fields['BETSETRESULT.BETSET'][OP_IN_INT] = $betsetUids;
            }
            $fields['BETSETRESULT.FEUSER'][OP_GT_INT] = 0;
            $options['what'] = '
			fe_users.uid, sum(tx_t3sportsbet_betsetresults.points) AS betpoints,
			sum(tx_t3sportsbet_betsetresults.bets) AS betcount
			';
        }

        $options['distinct'] = 1;
        $options['orderby']['betpoints'] = 'desc';
        $options['groupby'] = 'fe_users.uid';
        // $options['debug'] = '1';
        $userSrv = tx_t3users_util_ServiceRegistry::getFeUserService();
        $rows = $userSrv->search($fields, $options);

        $userIds = Tx_Rnbase_Utility_Strings::intExplode(',', $feuserUids);
        $userIds = array_flip($userIds);
        $userIdx = array();
        $rank = 0;
        $lastPoints = 0;
        for ($i = 0, $cnt = count($rows); $i < $cnt; ++$i) {
            $row = &$rows[$i];
            // Check rank
            if ($lastPoints != $row['betpoints']) {
                $rank = $i + 1;
                $lastPoints = $row['betpoints'];
            }
            $row['rank'] = $rank;

            if (array_key_exists($row['uid'], $userIds)) {
                $row['mark'] = 'mark';
                $userIdx[$row['uid']] = $i;
            }
        }

        return array(
            0 => $rows,
            1 => $userIdx,
        );
    }

    public function searchBet($fields, $options)
    {
        tx_rnbase::load('tx_rnbase_util_SearchBase');
        $searcher = tx_rnbase_util_SearchBase::getInstance('tx_t3sportsbet_search_Bet');

        return $searcher->search($fields, $options);
    }

    public function searchBetSet($fields, $options)
    {
        tx_rnbase::load('tx_rnbase_util_SearchBase');
        $searcher = tx_rnbase_util_SearchBase::getInstance('tx_t3sportsbet_search_BetSet');

        return $searcher->search($fields, $options);
    }

    public function searchBetSetResult($fields, $options)
    {
        tx_rnbase::load('tx_rnbase_util_SearchBase');
        $searcher = tx_rnbase_util_SearchBase::getInstance('tx_t3sportsbet_search_BetSetResult');

        return $searcher->search($fields, $options);
    }

    /**
     * Adds new matches to an existing betset.
     *
     * @param tx_t3sportsbet_models_betset $betset
     * @param string $matchUids
     *            commaseparated uids
     *
     * @return string
     */
    public function addMatchesTCE($betset, $matchUids)
    {
        $data = array();
        tx_rnbase::load('tx_cfcleaguefe_models_match');
        $cnt = count($matchUids);
        $existingUids = $this->getMatchUids($betset);
        $matchUids = array_merge($existingUids, $matchUids);
        $data['tx_t3sportsbet_betsets'][$betset->uid]['t3matches'] = 'tx_cfcleague_games_'.implode(',tx_cfcleague_games_', $matchUids);
        $tce = &Tx_Rnbase_Database_Connection::getInstance()->getTCEmain($data);
        $tce->process_datamap();

        return $cnt;
    }

    private function getMatchUids($betset)
    {
        $matches = $betset->getMatches();
        $ret = array();
        foreach ($matches as $match) {
            $ret[] = $match->uid;
        }

        return $ret;
    }

    /**
     * @param tx_t3sportsbet_models_betgame $betgame
     * @param int $status
     * @param string $betsetUids
     *            commaseperated uids
     *
     * @return array[tx_t3sportsbet_models_betset]
     */
    public function getRounds(&$betgame, $status, $betsetUids = '')
    {
        $fields = array();
        $fields['BETSET.BETGAME'][OP_EQ_INT] = $betgame->uid;
        if (trim($status)) {
            $fields['BETSET.STATUS'][OP_IN_INT] = $status;
        }
        if (trim($betsetUids)) {
            $fields['BETSET.UID'][OP_IN_INT] = $betsetUids;
        }

        $options = array();
        // $options['debug'] = 1;
        $options['orderby']['BETSET.ROUND'] = 'asc';

        return $this->searchBetSet($fields, $options);
    }

    /**
     * Save or update a bet from fe request.
     *
     * @param tx_t3sportsbet_models_betset $betset
     * @param tx_cfcleaguefe_models_match $match
     * @param tx_t3users_models_feuser $feuser
     * @param int $betUid
     * @param array $betData
     *
     * @return int 0/1 whether the bet was saved or not
     */
    public function saveOrUpdateBet($betset, $match, $feuser, $betUid, $betData)
    {
        if ($match->isRunning() || $match->isFinished()) {
            return 0;
        }
        if ('OPEN' != $betset->getMatchState($match)) {
            return 0;
        }

        if (!(strlen(trim($betData['home'])) || strlen(trim($betData['guest'])))) {
            return 0;
        } // No values given
        // Der Tip muss vom selben User stammen
        $values = array();
        $values['tstamp'] = time();
        $values['goals_home'] = intval($betData['home']);
        $values['goals_guest'] = intval($betData['guest']);
        $betUid = intval($betUid);
        if ($betUid) {
            // Update bet
            $bet = tx_rnbase::makeInstance('tx_t3sportsbet_models_bet', $betUid);
            if ($bet->record['fe_user'] != $feuser->uid) {
                return 0;
            }
            if ($bet->record['goals_home'] == $values['goals_home'] && $bet->record['goals_guest'] == $values['goals_guest']) {
                return 0;
            }
            $where = 'uid='.$betUid;
            Tx_Rnbase_Database_Connection::getInstance()->doUpdate('tx_t3sportsbet_bets', $where, $values, 0);
        } else {
            // Create new bet instance
            // Ein User darf pro Spiel nur einen Tip abgeben
            $bet = $this->getBet($betset, $match, $feuser);
            if ($bet->isPersisted()) {
                return 0;
            } // There is already a bet for this match!

            $values['pid'] = $betset->record['pid'];
            $values['crdate'] = $values['tstamp'];
            $values['fe_user'] = $feuser->uid;
            $values['t3match'] = $match->uid;
            $values['betset'] = $betset->uid;
            Tx_Rnbase_Database_Connection::getInstance()->doInsert('tx_t3sportsbet_bets', $values, 0);
        }

        return 1;
    }
}
