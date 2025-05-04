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
 * @author    MiﾅＰsz Biedrzycki
 * @copyright MiﾅＰsz Biedrzycki, Alx z Poewiki
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
use MediaWiki\Hook\AlternateEditHook;
use Parser;
use Title;
use OutputPage;

class Hooks implements ParserFirstCallInitHook, AlternateEditHook {
	  // Register any render callbacks with the parser

//	define("PROTECT_TAG", "zabezp");
//        define("PARAM_ALLOW", "pozw");
//	define("PARAM_DENY", "zabr");


	/**
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) : void {
		// When the parser sees the <zabezp> tag, it executes ZabezpieczStrone::parserHook
		$parser->setHook( 'zabezp', ZabezpieczStrone::class . '::parserHook');
	}

	private function blockEdits($u_yes, $u_no, $user, $editPage) {
		wfDebugLog( 'mzab', "blockEdits" );
		wfDebugLog( 'mzab', $user );
		// Forbidden users are forbidden even if allowed
		if (in_array($user, $u_no)) {
			wfDebugLog( 'mzab', "Forbidden " . $user );
			$context = $editPage->getContext();
			$output = $context->getOutput();
			// TODO: allow for the owner
			$output->setPageTitle( "Strona zabezpieczona przez użytkownika"  );
			$output->addWikiTextAsContent( "'''[[Pomoc:Strona zabezpieczona|Strona zabezpieczona]] .'''" );
    			#$wgOut->addWikiText( pokazKomunikat($u_tak, $u_nie) );
			return false;
		}
		// Allowed are only users that are explicite made allowed
		if (!empty($u_yes) && in_array($user, $u_yes)) {
			return true;
		}
		// All other users are forbidden
		return false;
	}

	/**
	 * AlternateEdit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/AlternateEdit
	 * @param EditPage $editpage
	 */
	public function onAlternateEdit( $editPage ) {
		$context = $editPage->getContext();
		$user = $context->getUser();
		$title = $editPage->getTitle();
		$ns = $title->getNamespace();
		$content = $editPage->getArticle()->getPage()->getContent();

    	        $tags = array();
                Parser::extractTagsAndParams(['zabezp'], $content->getText(), $tags );

		if ( ($ns == NS_USER || $ns == NS_USER_TALK) && !empty($tags) ) {
			$u_yes = array();
			$u_no = array();
			foreach ($tags as $elem) {
				$params = $elem[2];
				foreach ($params as $key => $val) {
					if ($key == "pozw") {
						array_push($u_yes, $val);
					};
					if ($key == "zabr") {
						array_push($u_no, $val);
					}
				}
			};
			return $this->blockEdits($u_yes, $u_no, $user, $editPage);
		};

		return true;
	}



};
