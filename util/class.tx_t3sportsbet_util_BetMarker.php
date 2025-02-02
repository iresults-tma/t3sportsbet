<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2017 Rene Nitzsche (rene@system25.de)
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
 * Diese Klasse ist für die Erstellung von Markerarrays der Tips verantwortlich.
 */
class tx_t3sportsbet_util_BetMarker extends tx_rnbase_util_BaseMarker
{
    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * @param string $template
     *            das HTML-Template
     * @param tx_t3sportsbet_models_bet $bet
     *            der Tip
     * @param tx_rnbase_util_FormatUtil $formatter
     *            der zu verwendente Formatter
     * @param string $confId
     *            Pfad der TS-Config des Vereins, z.B. 'listView.round.'
     * @param string $marker
     *            Name des Markers für die Tipprunde, z.B. ROUND
     *
     * @return string das geparste Template
     */
    public function parseTemplate($template, &$bet, &$formatter, $confId, $marker = 'BET')
    {
        if (!is_object($bet)) {
            // Ist kein Verein vorhanden wird ein leeres Objekt verwendet.
            $bet = self::getEmptyInstance('tx_t3sportsbet_models_bet');
        }
        // Es wird das MarkerArray mit den Daten des Tips gefüllt.
        $markerArray = $formatter->getItemMarkerArrayWrapped($bet->getProperty(), $confId, 0, $marker.'_', $bet->getColumnNames());
        $out = \tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

        return $out;
    }
}
