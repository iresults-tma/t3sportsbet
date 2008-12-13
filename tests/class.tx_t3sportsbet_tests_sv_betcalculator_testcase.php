<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');


tx_div::load('tx_rnbase_configurations');
tx_div::load('tx_rnbase_util_Spyc');
//tx_div::load('tx_cfcleaguefe_models_team');
tx_div::load('tx_cfcleaguefe_util_league_DefaultTableProvider');
tx_div::load('tx_cfcleaguefe_models_competition');
tx_div::load('tx_cfcleaguefe_util_LeagueTable');

class tx_t3sportsbet_tests_sv_betcalculator_testcase extends tx_phpunit_testcase {
	function test_betCalculation() {

		// betgame
		$betgame = $this->getBetgame(5,3,1);

		// bet
		$bet = $this->getBet(2,1);
		// srv
		$calculator = tx_div::makeInstance('tx_t3sportsbet_services_betcalculator');
		// Matches
		$matches = $this->getMatches();

		$bet->record['t3match'] = $matches['match_2_0']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);

		$this->assertEquals(1, $result, 'Match 2:0 Bet 2:1 Points: ' . $result);

		$bet->record['t3match'] = $matches['match_1_1']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(0, $result, 'Match 1:1 Bet 2:1 Points: ' . $result);

		$bet->record['t3match'] = $matches['match_1_0']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(3, $result, 'Match 1:0 Bet 2:1 Points: ' . $result);

		$bet->record['t3match'] = $matches['match_1_2']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(0, $result, 'Match 1:2 Bet 2:1 Points: ' . $result);

		$bet = $this->getBet(3,0);
		$bet->record['t3match'] = $matches['match_3_0']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(5, $result, 'Match 3:0 Bet 3:0 Points: ' . $result);
	}
	function test_betCalculationCup() {
		$betgame = $this->getBetgame(5,0,1,1);
		$betgame2 = $this->getBetgame(5,0,1,0);
		$calculator = tx_div::makeInstance('tx_t3sportsbet_services_betcalculator');
		$matches = $this->getMatches();
		
		$bet = $this->getBet(3,1);
		$bet->record['t3match'] = $matches['match_3_1_et']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(0, $result, 'Match 3:1 et (1:1) Bet 3:1 Points: ' . $result);
		$result = $calculator->calculatePoints($betgame2, $bet);
		$this->assertEquals(5, $result, 'Match 3:1 et (1:1) Bet 3:1 Points: ' . $result);
		
		$bet = $this->getBet(2,2);
		$bet->record['t3match'] = $matches['match_3_1_et']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(1, $result, 'Match 3:1 et (1:1) Bet 2:2 Points: ' . $result);
		$result = $calculator->calculatePoints($betgame2, $bet);
		$this->assertEquals(0, $result, 'Match 3:1 et (1:1) Bet 3:1 Points: ' . $result);
		
		$bet = $this->getBet(1,1);
		$bet->record['t3match'] = $matches['match_3_1_et']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(5, $result, 'Match 3:1 et (1:1) Bet 1:1 Points: ' . $result);
	}
	function test_betCalculationWoDiff() {
		// Ohne Tordiff testen
		$betgame = $this->getBetgame(5,0,1);
		$bet = $this->getBet(2,1);
		$calculator = tx_div::makeInstance('tx_t3sportsbet_services_betcalculator');
		$matches = $this->getMatches();

		$bet->record['t3match'] = $matches['match_1_0']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(1, $result, 'Match 1:0 Bet 2:1 Points: ' . $result);

		$bet->record['t3match'] = $matches['match_1_2']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(0, $result, 'Match 1:2 Bet 2:1 Points: ' . $result);

		$bet = $this->getBet(3,0);
		$bet->record['t3match'] = $matches['match_3_0']->uid;
		$result = $calculator->calculatePoints($betgame, $bet);
		$this->assertEquals(5, $result, 'Match 3:0 Bet 3:0 Points: ' . $result);
	}

	private function getBetgame($p1, $p2, $p3, $drawIfET=0, $drawIfPenalty=0) {
		$clazzname = tx_div::makeInstanceClassname('tx_t3sportsbet_models_betgame');
		$record = array();
		$record['uid'] = 1;
		$record['points_accurate'] = $p1;
		$record['points_goalsdiff'] = $p2;
		$record['points_tendency'] = $p3;
		$record['draw_if_extratime'] = $drawIfET;
		$record['draw_if_penalty'] = $drawIfPenalty;
		return new $clazzname($record);
	}
	private function getBet($home, $guest) {
		$clazzname = tx_div::makeInstanceClassname('tx_t3sportsbet_models_bet');
		$record = array();
		$record['uid'] = 1;
		$record['goals_home'] = $home;
		$record['goals_guest'] = $guest;
		return new $clazzname($record);
	}
	private function getMatches() {
		$data = tx_rnbase_util_Spyc::YAMLLoad($this->getFixturePath('util_Matches.yaml'));
		$data = $data['matches'];
		$matches = $this->makeInstances($data, $data['clazz']);
		foreach($matches As $match) {
			// Die Spiele in das Instance-Array legen
			tx_cfcleaguefe_models_match::addInstance($match);
		}
		reset($matches);
		return $matches;
	}
	private function makeInstances($yamlData, $clazzName) {
		// Sicherstellen, daß die Klasse geladen wurde
		tx_div::load($clazzName);
		foreach($yamlData As $key => $arr) {
			if(is_array($arr['record']))
				$ret[$key] = new $clazzName($arr['record']);
		}
		return $ret;
	}
	function getFixturePath($filename) {
		return t3lib_extMgm::extPath('t3sportsbet').'tests/fixtures/'.$filename;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/tests/class.tx_t3sportsbet_tests_sv_betcalculator_testcase.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/tests/class.tx_t3sportsbet_tests_sv_betcalculator_testcase.php']);
}

?>