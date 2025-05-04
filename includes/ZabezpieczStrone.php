<?php
/**
 * An extension that implements <zabezp> PoeWiki tag
 * Mediawiki after version 1.35
 *
 * PHP Version 7
 *
 * @category  Extension
 * @package   ZabezpieczStrone
 * @author    Alx z Poewiki
 * @author    Miłosz Biedrzycki
 * @copyright Miłosz Biedrzycki, Alx z Poewiki
 * @license   GPL-3.0-or-later
 * @version   GIT: 0.3
 * @link      ??
 */


/**
 * The main file of the ZabezpieczStrone extension
 *
 * This file is part of the MediaWiki PoeWiki extension ZabezpieczStrone.
 * The ZabezpieczStrone extension is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The ZabezpieczStrone extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 */

declare( strict_types=1 );

namespace MediaWiki\Extension\ZabezpieczStrone;

use MediaWiki\Hook\ParserFirstCallInitHook;
use PPFrame;
use Parser;
use Sanitizer;
use MediaWiki\Extension\ZabezpieczStrone\ZabezpHooks;

class ZabezpieczStrone {


	// Register any render callbacks with the parser
	public static function parserHook( ?string $input, array $args, Parser $parser, PPFrame $frame ) {
		// Handle an invalid self-closing tag
		if ( $input === null ) {
			return '';
		}

		$result = '';

		// Sanitize to attributes that would be valid on a <div>
		$attrs = Sanitizer::safeEncodeTagAttributes( Sanitizer::validateTagAttributes( $args, 'div' ) );

		$result .= '<div class="' . ZabezpHooks::PROTECT_TAG . '" ' . $attrs . '>';

		$result .= $parser->recursiveTagParse( $input, $frame );
		$result .= '</div>';
		return $result;
	}
};
