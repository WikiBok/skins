<?php
/**
 * WikiBok
 *
 * @file
 * @ingroup Skins
 */
if( !defined( 'MEDIAWIKI' ) )
	die( -1 );
/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @ingroup Skins
 */
class SkinWikiBok extends SkinTemplate {
	var $skinname = 'wikibok', $stylename = 'wikibok',
		$template = 'WikiBokTemplate', $useHeadElement = true;

	/**
	 * Load skin and user CSS files in the correct order
	 * fixes bug 22916
	 * @param $out OutputPage object
	 */
	function setupSkinUserCss( OutputPage $out ){
		global $wgHandheldStyle;

		parent::setupSkinUserCss( $out );
		$out->addModuleStyles( 'skins.wikibok' );

		if( $wgHandheldStyle ) {
			$out->addStyle( $wgHandheldStyle, 'handheld' );
		}
		//Each Browser Type... (no use)
		//$out->addStyle( 'wikibok/IE60Fixes.css', 'screen', 'IE 6' );
		//$out->addStyle( 'wikibok/IE70Fixes.css', 'screen', 'IE 7' );

		//Write right-to-left CSS Change... (no use)
		//$out->addStyle( 'wikibok/rtl.css', 'screen', '', 'rtl' );
		
		$out->addStyle( 'wikibok/main.css', 'screen');
		$out->addStyle( 'wikibok/print.css', 'print');
	}
	/**
	 *
	 * @override
	 */
	function buildPersonalUrls() {
		global $wgUser,$wgTitle,$wgRequest;
		
		$pageurl = $wgTitle->getLocalURL();
		$personal_urls = array();
		//
		if( $wgUser->isLoggedIn() ) {
			$name = $wgUser->getRealName();
			$name = (empty($name)) ? $wgUser->getName() : $name;
			$urlClass = 'logout';
			$class = 'wikibok-logout';
			$text = wfMsg('userlogout')." ({$name})";
		}
		else {
			//新規ユーザ追加が許可あり/なし
			$name = $wgUser->isAllowed('createaccount') ? 'nav-login-createaccount' : 'login';
			$urlClass = parent::showIPinHeader() ? 'anonlogin' : 'login';
			$class = 'wikibok-login';
			$text = wfMsg($name);
		}
		$personal_urls[$urlClass] = array(
			'text' => $text,
			'href' => '#',
			'class' => $class,
			'active' => true
		);
		wfRunHooks( 'PersonalUrls', array( &$personal_urls, &$wgTitle ) );
		return $personal_urls;
	}
}
/**
 *
 * @ingroup Skins
 */
class WikiBokTemplate extends BaseTemplate {
	/**
	 * Template filter callback for WikiBok skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgRequest;
		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();

		// Build additional attributes for navigation urls
		$nav = $this->data['content_navigation'];

		if ( $wgVectorUseIconWatch ) {
			$mode = $this->getSkin()->getTitle()->userIsWatching() ? 'unwatch' : 'watch';
			if ( isset( $nav['actions'][$mode] ) ) {
				$nav['views'][$mode] = $nav['actions'][$mode];
				$nav['views'][$mode]['class'] = rtrim( 'icon ' . $nav['views'][$mode]['class'], ' ' );
				$nav['views'][$mode]['primary'] = true;
				unset( $nav['actions'][$mode] );
			}
		}

		$xmlID = '';
		foreach ( $nav as $section => $links ) {
			foreach ( $links as $key => $link ) {
				if ( $section == 'views' && !( isset( $link['primary'] ) && $link['primary'] ) ) {
					$link['class'] = rtrim( 'collapsible ' . $link['class'], ' ' );
				}

				$xmlID = isset( $link['id'] ) ? $link['id'] : 'ca-' . $xmlID;
				$nav[$section][$key]['attributes'] =
					' id="' . Sanitizer::escapeId( $xmlID ) . '"';
				if ( $link['class'] ) {
					$nav[$section][$key]['attributes'] .=
						' class="' . htmlspecialchars( $link['class'] ) . '"';
					unset( $nav[$section][$key]['class'] );
				}
				if ( isset( $link['tooltiponly'] ) && $link['tooltiponly'] ) {
					$nav[$section][$key]['key'] =
						Linker::tooltip( $xmlID );
				} else {
					$nav[$section][$key]['key'] =
						Xml::expandAttributes( Linker::tooltipAndAccesskeyAttribs( $xmlID ) );
				}
			}
		}
		$this->data['namespace_urls'] = $nav['namespaces'];
		$this->data['view_urls'] = $nav['views'];
		$this->data['action_urls'] = $nav['actions'];
		$this->data['variant_urls'] = $nav['variants'];

		// Reverse horizontally rendered navigation elements
		if ( $this->data['rtl'] ) {
			$this->data['view_urls'] =
				array_reverse( $this->data['view_urls'] );
			$this->data['namespace_urls'] =
				array_reverse( $this->data['namespace_urls'] );
			$this->data['personal_urls'] =
				array_reverse( $this->data['personal_urls'] );
		}
		$view = array_merge(
			$this->data['namespace_urls'],
			$this->data['variant_urls'],
			$this->data['view_urls'],
			$this->data['action_urls']
		);


		$this->html( 'headelement' );
?>
	<div id="wikibok-tooltip" class="noprint">
		<!-- 個人用ツール -->
		<div class="hover wikibok-link">
			<div><span class="ui-icon ui-icon-inline ui-icon-circle-arrow-e"></span><?php $this->msg('personaltools'); ?></div>
			<?php
			if(isset($this->data['personal_urls'])) {
				foreach($this->data['personal_urls'] as $key => $item) { ?>

			<div id="<?php echo Sanitizer::escapeId("pt-{$key}") ?>" class="hide hover linkitem<?php if($item['active']) {?> active <?php } ?>"/>
				<li href="<?php echo htmlspecialchars($item['href']) ?>"<?php echo Linker::tooltipAndAccesskeyAttribs("pt-{$key}") ?> class="<?php
					$class = array();
					if(isset($item['selected']) && $item['selected']){
						$class[] = 'selected';
					}
					if(isset($item['class']) && !empty($item['class'])){
						$class[] = htmlspecialchars($item['class']);
					}
					echo implode(' ',$class);
					?>"><?php echo htmlspecialchars($item['text']) ?></li>
			</div>
			<?php
				}
			} ?>
		</div>
		<?php
			$this->renderPortal('tb',$this->getToolbox(),'toolbox','SkinTemplateToolboxEnd');
			$this->renderPortal('lang',$this->data['language_urls'],'otherlanguages');
			$this->renderPortal('views',$view,'views');
		?>
	</div>
	<!-- Body -->
	<div id="globalWrapper">
		<div id="mainpage">
		<?php if ($wgRequest->getText('page') != "plain") { ?>
			<!-- wgOut内容出力 -->
			<?php $this->html('bodytext') ?>
		<?php } ?>
		</div>
	</div>
	<!-- /Body -->
	<!-- Trail -->
		<?php $this->printTrail(); ?>
	<!-- /Trail -->
	<?php
	}
	/**
	 *
	 */
	private function renderPortal( $name, $content, $msg = null, $hook = null ) {
		if( !$content ) {
			return;
		}
		if ( $msg === null ) {
			$msg = $name;
		}
	?>
	<!-- <?php echo "{$name}"; ?> -->
	<div class="hover wikibok-link" id='<?php echo Sanitizer::escapeId( "p-$name" ) ?>'<?php echo Linker::tooltip( 'p-' . $name ) ?>>
		<div<?php $this->html( 'userlangattributes' ) ?>>
			<span class="ui-icon ui-icon-inline ui-icon-circle-arrow-e"></span>
			<?php $msgObj = wfMessage( $msg ); echo htmlspecialchars( $msgObj->exists() ? $msgObj->text() : $msg ); ?>
		</div>
	<?php
		if ( is_array( $content ) ) { 
			foreach( $content as $key => $val ) {
				if(isset($val['attributes'])) {
					if(stripos($val['attributes'],'selected') === false) {
						if(isset($val['href']) && !empty($val['href'])) {
							$val['class'] = 'wikibok-linkcaution';
						}
						echo $this->makeListItem($key, $val);
					}
				}
				else {
					if(isset($val['href']) && !empty($val['href'])) {
						$val['class'] = 'wikibok-linkcaution';
					}
					echo $this->makeListItem($key, $val);
				}
			}
			if ( $hook !== null ) {
				wfRunHooks( $hook, array( &$this, true ) );
			}
		}
		else {
			echo $content; /* Allow raw HTML block to be defined by extensions */
		}
	?>
	</div>
	<!-- /<?php echo "{$name}"; ?> -->
	<?php
	}
}
