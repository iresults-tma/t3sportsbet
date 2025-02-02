Changelog
---------

v1.0.0 (??.06.2020)
 * Support for TYPO3 9.5 and 10.4
 * custom CSS removed
 * Static TS for plugin moved to resource folder

v0.6.0 (11.06.2019)
 * Support for TYPO3 7.6 and 8.7

v0.5.0 (??.04.2014)
 * Support for TYPO3 6.2

v0.4.6 (27.12.2010)
 * Teambet results will be included in highscore list. It is necessary to reanalyze all betgames!

v0.4.5 (11.12.2010)
 * Bugfix: Adding matches to betset fixed

v0.4.4 (23.11.2010)
 * It is possible to move a match from one betset to another

v0.4.3 (22.10.2010)
 * Dependency fixed to cfc_league_fe fixed.

v0.4.2 (21.10.2010)
 * Bugfix: Show submit button for teambets
 * Modifications for BE module refactoring in cfc_league

v0.4.1 (03.09.2010)
 * action_BetList: Missing include of betset added.
 * Trend for team bets with pie chart

v0.4.0 (23.07.2010)
 * New german language marker. Thanks to Christian Platt!
 * Team bets are possible (requires TYPO3 4.2 because of IRRE)


v0.3.2 (19.05.2010)
 * Bugfix of point calculator for matches with penalty shooting
 * All references to tx_div removed

v0.3.1 (08.08.2009)
 * Controller class removed
 * Wizicon for plugin added
 * BE module will now handle exceptions. This will keep the module useable even if a exception occurs.
 * Searching of betsets can be joined to table tx_cfcleague_games. This makes it possible to betsets with finished matches only.
 * Search matches of a betset with SearchBase. This enables a lot of options by Typoscript.
This will output finished matches ordered by date:
betlist.betset.match {
  fields.MATCH.STATUS.OP_EQ_INT = 2
  options.orderby.MATCH.DATE = asc
#  options.debug =1
}

v0.3.0 (13.12.2008)
 * Backend modified for TYPO3 4.2
 * Bugfix: Calculation for matches with extra time or penalty failed. Update of cfc_league_fe is required too!
 * Matches can be added only once within a betgame
 * Predefined fields (betgame and round) in betset if created from within BE module
 * New wizard added to create a betgame for a complete competition
 * New links to betlists of other users.

v0.2.10 (19.08.2008)
 * Average points are calculated now for finished bets only
 * Separate score for correct goals difference is possible now. New option in betgame record.
 * Some unit tests for score calculation

v0.2.9 (16.08.2008)
 * Important: Match lock time was wrong in FE! Bets were possible even after kickoff date!
 * New info in highscore list: size of userbets and average point per bet. See html template for markers.

v0.2.8 (12.08.2008)
 * It is now possible to output a bet trend in betlist. So you can show how many user bet for the home team, guest team or a draw match. Have a look at betlist HTML template for markers.

v0.2.7 (07.08.2008)
 * Betlist can be displayed even if no feuser is logged in.
 * It is now possible to add typoscript code in flexform. rn_base 0.2.7 is required for this!

v0.2.6 (31.07.2008)
* The HTML template of betlist was changed. There is now an example on how to add an agegroup-marker. And more important, the is an example to demonstrate the disableIfEmpty flag for links.
* User ranks in highscore list fixed.
* New database index for table tx_t3sportsbet_bets

v0.2.5 (27.07.2008)
* Another bug in information table fixed. It is really hot weather these days. ;)

v0.2.3 (27.07.2008)
* Bug in information table fixed.

v0.2.2 (26.07.2008)
* There is a new information table in backend to have a better overview about the current betset.

v0.2.1 (25.07.2008)
* Template key in flexform fixed for betlist template
* Some code enhancements in BE modules
* Info message for feuser when bets are saved. There is a new subpart in HTML template.

v0.2.0 (20.07.2008)
* GUI of backend module reorganized
* It is now possible to reset bet results for matches
* List of bets in backend
* New scope view in FE with select boxes for betsets

v0.1.0 (10.06.2008)
* Initial release
