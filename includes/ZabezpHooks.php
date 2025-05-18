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
use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsExpensiveHook;
use RequestContext;
use Parser;
use Title;
use OutputPage;

class ZabezpHooks 
	implements 
		ParserFirstCallInitHook, 
		AlternateEditHook,
		GetUserPermissionsErrorsExpensiveHook {
	// Register any render callbacks with the parser
	
	const PROTECT_TAG = 'zabezp';
	const PARAM_ALLOW = 'pozw';
	const PARAM_DENY = 'zabr';

	/**
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) : void {
		// When the parser sees the <zabezp> tag, it executes ZabezpieczStrone::parserHook
		$parser->setHook( ZabezpHooks::PROTECT_TAG, ZabezpieczStrone::class . '::parserHook');
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
		if ($content != null) {
                	Parser::extractTagsAndParams([ZabezpHooks::PROTECT_TAG], $content->getText(), $tags );
		} else {
			$tags = [];
		}

		if ( ($ns == NS_USER || $ns == NS_USER_TALK) && !empty($tags) ) {
			$u_yes = array();
			$u_no = array();
			$this->canEdit($tags, $u_yes, $u_no);
			return $this->blockEdits($u_yes, $u_no, $user, $editPage);
		};
		return true;
	}

	/**
	 * Decides if the edit should be blocked and generates appropriate
	 * response page.
	 * @param $u_yes - array with user names that are allowed to edit
	 * @param $u_no - array with user names that are forbidden to edit
	 * @param $user - user name of the user that performs the edit
	 * @param $editPage - page that is under edit, may be changed
	 */
	private function blockEdits($u_yes, $u_no, $user, $editPage) {
		// The owner of the namespace is always allowed to edit
		$usertitle = $editPage->getTitle()->getBaseText();
		if ($user == $usertitle) return true;
		//return true;
		// Forbidden users are forbidden even if allowed
		if (in_array($user, $u_no)) {
			$this->showMessage($editPage, $user);
			return false;
		}
		// Allowed are only users that are explicite made allowed
		if (!empty($u_yes) && in_array($user, $u_yes)) {
			return true;
		}
		// All other users are forbidden
		$this->showMessage($editPage, $user);
		return false;
	}

	/**
	 * Display the warning that the page is protected.
	 * Note that the user is the first argument of the text
	 * @param $editPage - page for which the message is generated
	 * @param $user - the user for which the message is generated
	 */
	public function showMessage($editPage, $user) {
		$context = $editPage->getContext();
		$output = $context->getOutput();
		$methods = get_class_methods($context);
		$context->getWikiPage()->doPurge();
		$ptitletext = wfMessage( 'zabezp-warning-page-title', $user )->parse();
		$output->setPageTitle( $ptitletext );
		$pconttext = wfMessage( 'zabezp-warning-page-content', $user )->parse();
		$output->addHTML( $pconttext  );
	}

	/**
	 * Collets user names that are allowed and forbidden to edit
	 * @param $tags - array with parameters of zabezp tag
	 * @param $u_tak - storage for user names that are allowed to edit
	 * @param $u_no - storage for user names that are forbidden to edit
	 */
	public function canEdit($tags, &$u_yes, &$u_no ) : void {
		foreach ($tags as $elem) {
			$params = $elem[2];
			foreach ($params as $key => $val) {
				if ($key == ZabezpHooks::PARAM_ALLOW) {
					$u_yes = array_map('trim', explode(",", $val));
				};
				if ($key == ZabezpHooks::PARAM_DENY) {
					$u_no = array_map('trim', explode(",", $val));
				}
			}
		};
	}

	/**
	 * Hook to manage the new permission system with 'editall'.
	 * 
	 * @param Title  $title  Title being checked against
	 * @param User   $user   Current user
	 * @param string $action Action being checked
	 * @param array | string | MessageSpecifier &$result User
         *        permissions error to add. If none, return true. For
         *        consistency, error messages should be plain text with no
         *        special coloring, bolding, etc. to show that they're
         *        errors; presenting them properly to the user as errors is
         *        done by the caller.
	 
	 * @param $tags - array with parameters of zabezp tag
	 * @param $u_tak - storage for user names that are allowed to edit
	 * @param $u_no - storage for user names that are forbidden to edit
	 */
	public function onGetUserPermissionsErrorsExpensive(
	       		$title,
			$user,
			$action,
			& $result ) {
		    // Fragment prepared with help of ChatGPT
		    // Allow users with the editall right (e.g., sysops)
    		    if ( $user->isAllowed( 'editall' ) ) {
        	       return true;
    		    }

		    // Allow here read and view actions unconditionally
    		    $action = RequestContext::getMain()->getRequest()->getVal( 'action', 'view' );
    		    if ( in_array( $action, [ 'read', 'view', 'history', 'render' ] ) ) {
        	       return true;
    		    }

    		    // Only edit-like actions are affected
    		    if ( !in_array( $action, [ 'edit', 'submit' ] ) ) {
        	       return true;
    		    }

		    $namespace = $title->getNamespace();
		    // Allow edits in user talk pages for all users
		    if ( $user->isAllowed( 'edit' ) && $namespace === NS_USER_TALK ) {
		       return true;
		    }

    		    // Only allow editing if it's the user's own user or user talk page
    		    $namespace = $title->getNamespace();
		    if ( $namespace === NS_USER ) {
        	       $pageOwner = explode( '/', $title->getText() )[0];
        	       if ( $pageOwner === $user->getName() ) {
            	       	  return true;
        	       }
    		    }

    		    // Block edits otherwise
		    $result = [ 'badaccess-group0' ];
    		    return false;

	}
};
