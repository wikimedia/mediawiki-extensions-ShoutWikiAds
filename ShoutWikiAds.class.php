<?php
/**
 * ShoutWikiAds class -- contains the hooked functions and some other crap for
 * displaying the advertisements.
 * Route all requests through loadAd( $type ) to ensure correct processing.
 *
 * We allow wiki admins to configure some things because our "sane defaults"
 * may clash with certain CSS styles (dark wikis, for example).
 * The Message objects are called with ->inContentLanguage(), because CSS is
 * "global" (doesn't vary depending on the user's language).
 *
 * All class methods are public and static.
 *
 * @file
 * @ingroup Extensions
 */

class ShoutWikiAds {

	/**
	 * Can we show ads on the current page?
	 *
	 * @return Boolean: false if ads aren't enabled or the current page is
	 *                  Special:UserLogin (login page) or if the user is
	 *                  autoconfirmed and the forceads parameter is NOT in the
	 *                  URL, otherwise true
	 */
	public static function canShowAds() {
		global $wgAdConfig, $wgTitle, $wgUser, $wgRequest;

		if( !$wgAdConfig['enabled'] ) {
			return false;
		}

		if( $wgTitle instanceof Title &&
				SpecialPage::resolveAlias( $wgTitle->getDBkey() ) == 'Userlogin' ||
			in_array( 'staff', $wgUser->getEffectiveGroups() ) && !$wgRequest->getVal( 'forceads' )
		)
		{
			return false;
		}

		// No configuration for this skin? Bail out!
		if ( !$wgAdConfig[self::determineSkin()] ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the current wiki's language is supported by the ad provider
	 * (currently checks against Google's list).
	 *
	 * @return Boolean: true if the language is supported, otherwise false
	 */
	public static function isSupportedLanguage() {
		global $wgLanguageCode;

		// "Publishers are also not permitted to place AdSense code on pages
		// with content primarily in an unsupported language"
		// @see https://www.google.com/adsense/support/bin/answer.py?answer=9727
		$supportedAdLanguages = array(
			// Arabic -> Dutch (+some Chinese variants)
			'ar', 'bg', 'zh', 'zh-hans', 'zh-hant', 'hr', 'cs', 'da', 'nl',
			// English and its variants
			'en', 'en-gb', 'en-lolcat', 'en-piglatin',
			// Finnish -> Polish
			'fi', 'fr', 'de', 'el', 'he', 'hu', 'it', 'ja', 'ko', 'no', 'pl',
			// Portuguese -> Turkish
			'pt', 'ro', 'ru', 'sr', 'sr-ec', 'sk', 'es', 'sv', 'th', 'tr',
			// http://adsense.blogspot.com/2009/08/adsense-launched-in-lithuanian.html
			'lt', 'lv', 'uk',
			// Vietnamese http://adsense.blogspot.co.uk/2013/05/adsense-now-speaks-vietnamese.html
			'vi',
			// Slovenian & Estonian http://adsense.blogspot.co.uk/2012/06/adsense-now-available-for-websites-in.html
			'sl', 'et',
			// Indonesian http://adsense.blogspot.co.uk/2012/02/adsense-now-speaks-indonesian.html
			'id',
		);

		if( in_array( $wgLanguageCode, $supportedAdLanguages ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the current namespace is allowed to show ads.
	 *
	 * @return Boolean: true if the namespace is supported, otherwise false
	 */
	public static function isEnabledNamespace() {
		global $wgAdConfig, $wgTitle;
		$namespace = $wgTitle->getNamespace();
		if( in_array( $namespace, $wgAdConfig['namespaces'] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Skin-agnostic way of getting the HTML for a Google AdSense sidebar
	 * ad.
	 *
	 * @return String: HTML code
	 */
	public static function getSidebarHTML() {
		global $wgAdConfig;

		$skinName = 'monaco';
		$id = "{$skinName}-sidebar-ad";
		$classes = "{$skinName}-ad noprint";
		// The code below might be useful, but it's not necessary currently
		// as Monobook cannot support this type of ad (Monobook has right
		// column and toolbox ads only)
		/*
		$skinName = self::determineSkin();

		$id = "{$skinName}-sidebar-ad";
		$classes = "{$skinName}-ad noprint";
		// Different IDs and classes for Monaco and Monobook
		if ( $skinName == 'monobook' ) {
			$id = 'column-google';
			$classes = 'noprint';
		} elseif ( $skinName == 'monaco' ) {
			$id = "{$skinName}-sidebar-ad";
			$classes = "{$skinName}-ad noprint";
		}
		*/

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-small-square-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-small-square-ad-slot'];
		}

		$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-border' )->inContentLanguage();
		$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-bg' )->inContentLanguage();
		$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-link' )->inContentLanguage();
		$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-text' )->inContentLanguage();
		$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-url' )->inContentLanguage();

		if ( $wgAdConfig['debug'] === true ) {
			return '<!-- Begin sidebar ad (ShoutWikiAds) -->
		<div id="' . $id . '" class="' . $classes . '">
			<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_200x200.png" alt="" />
		</div>
<!-- End sidebar ad (ShoutWikiAds) -->' . "\n";
		}

		return '<!-- Begin sidebar ad (ShoutWikiAds) -->
		<div id="' . $id . '" class="' . $classes . '">
<script type="text/javascript"><!--
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 200;
google_ad_height = 200;
google_ad_format = "200x200_as";
google_ad_type = "text";
google_ad_channel = "";
google_color_border = "' . ( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . '";
google_color_bg = "' . ( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . '";
google_color_link = "' . ( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . '";
google_color_text = "' . ( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . '";
google_color_url = "' . ( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . '";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

</div>
<!-- End sidebar ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Skin-agnostic way of getting the HTML for a Google AdSense leaderboard
	 * ad.
	 *
	 * @return String: HTML code
	 */
	public static function getLeaderboardHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-leaderboard-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-leaderboard-ad-slot'];
		}

		$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-leaderboard-ad-color-border' )->inContentLanguage();
		$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-leaderboard-ad-color-bg' )->inContentLanguage();
		$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-leaderboard-ad-color-link' )->inContentLanguage();
		$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-leaderboard-ad-color-text' )->inContentLanguage();
		$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-leaderboard-ad-color-url' )->inContentLanguage();

		$colorBorderDefault = 'F6F4C4';
		$colorBGDefault = 'FFFFE0';
		$colorLinkDefault = '000000';
		$colorURLDefault = '002BB8';

		// different defaults for Truglass from old Truglass ad code
		if ( $skinName == 'truglass' ) {
			$colorBorderDefault = 'CDCDCD';
			$colorBGDefault = 'FFFFFF';
			$colorLinkDefault = '0066FF';
			$colorURLDefault = '00A000';
		}

		if ( $wgAdConfig['debug'] === true ) {
			return '<!-- Begin leaderboard ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-leaderboard-ad" class="' . $skinName . '-ad noprint">
			<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_728x90.png" alt="" />
		</div>
<!-- End leaderboard ad (ShoutWikiAds) -->' . "\n";
		}

		return '<!-- Begin leaderboard ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-leaderboard-ad" class="' . $skinName . '-ad noprint">
<script type="text/javascript"><!--
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 728;
google_ad_height = 90;
google_ad_format = "728x90_as";
//google_ad_type = "";
google_ad_channel = "";
google_color_border = "' . ( $borderColorMsg->isDisabled() ? $colorBorderDefault : $borderColorMsg->text() ) . '";
google_color_bg = "' . ( $colorBGMsg->isDisabled() ? $colorBGDefault : $colorBGMsg->text() ) . '";
google_color_link = "' . ( $colorLinkMsg->isDisabled() ? $colorLinkDefault : $colorLinkMsg->text() ) . '";
google_color_text = "' . ( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . '";
google_color_url = "' . ( $colorURLMsg->isDisabled() ? $colorURLDefault : $colorURLMsg->text() ) . '";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

</div>
<!-- End leaderboard ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Get the HTML for a toolbox ad (125x125).
	 * @return HTML
	 */
	public static function getToolboxHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-button-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-button-ad-slot'];
		}

		$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-border' )->inContentLanguage();
		$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-bg' )->inContentLanguage();
		$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-link' )->inContentLanguage();
		$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-text' )->inContentLanguage();
		$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-url' )->inContentLanguage();

		if ( $wgAdConfig['debug'] === true ) {
			return '<!-- Begin toolbox ad (ShoutWikiAds) -->
<div id="p-ads-left" class="noprint">
	<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_125x125_en.png" alt="" />
</div>
<!-- End toolbox ad (ShoutWikiAds) -->' . "\n";
		}

		return '<!-- Begin toolbox ad (ShoutWikiAds) -->
<div id="p-ads-left" class="noprint">
<script type="text/javascript"><!--
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 125;
google_ad_height = 125;
google_ad_format = "125x125_as";
google_ad_type = "text";
google_ad_channel = "";
google_color_border = "' . ( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . '";
google_color_bg = "' . ( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . '";
google_color_link = "' . ( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . '";
google_color_text = "' . ( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . '";
google_color_url = "' . ( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . '";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

</div>
<!-- End toolbox ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Get the HTML for a normal skyscraper ad (120x600).
	 *
	 * @return String: HTML
	 */
	public static function getSkyscraperHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-skyscraper-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-skyscraper-ad-slot'];
		}

		$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-border' )->inContentLanguage();
		$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-bg' )->inContentLanguage();
		$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-link' )->inContentLanguage();
		$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-text' )->inContentLanguage();
		$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-url' )->inContentLanguage();

		// Just output an image in debug mode
		if ( $wgAdConfig['debug'] === true ) {
			return "\n" . '<!-- Begin skyscraper ad (ShoutWikiAds) -->
<div id="column-google" class="' . $skinName . '-ad noprint">
	<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_120x600.png" alt="" />
</div>
<!-- End skyscraper ad (ShoutWikiAds) -->' . "\n";
		}

		return "\n" . '<!-- Begin skyscraper ad (ShoutWikiAds) -->
<div id="column-google" class="' . $skinName . '-ad noprint">
<script type="text/javascript"><!--
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 120;
google_ad_height = 600;
google_ad_format = "120x600_as";
//google_ad_type = "text";
google_ad_channel = "";
google_color_border = "' . ( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . '";
google_color_bg = "' . ( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . '";
google_color_link = "' . ( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . '";
google_color_text = "' . ( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . '";
google_color_url = "' . ( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . '";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

</div>
<!-- End skyscraper ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Get the HTML for a small square ad (200x200).
	 *
	 * @return String: HTML
	 */
	public static function getSmallSquareHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-small-square-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-small-square-ad-slot'];
		}

		$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-border' )->inContentLanguage();
		$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-bg' )->inContentLanguage();
		$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-link' )->inContentLanguage();
		$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-text' )->inContentLanguage();
		$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-url' )->inContentLanguage();

		// Just output an image in debug mode
		if ( $wgAdConfig['debug'] === true ) {
			/*
			Strangely enough when trying to display the text-only ad (located
			at http://www.google.com/help/hc/images/adsense_185665_adformat-text_200x200.png),
			a few pixels are cut off from its right side or at least that's
			what it looks like to me on Internet Explorer 10 on Windows 7 Ultimate.
			The image ad, which is what's used below, appears to render correctly.
			--Jack Phoenix, 3 July 2013
			*/
			return "\n" . '<!-- Begin small square (ShoutWikiAds) -->
<div id="small-square-ad" class="' . $skinName . '-ad noprint">
	<img src="https://storage.googleapis.com/support-kms-prod/SNP_2922332_en_v0" alt="" />
</div>
<!-- End small square (ShoutWikiAds) -->' . "\n";
		}

		return "\n" . '<!-- Begin small square ad (ShoutWikiAds) -->
<div id="small-square-ad" class="' . $skinName . '-ad noprint">
<script type="text/javascript"><!--
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 200;
google_ad_height = 200;
google_ad_format = "200x200_as";
//google_ad_type = "text";
google_ad_channel = "";
google_color_border = "' . ( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . '";
google_color_bg = "' . ( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . '";
google_color_link = "' . ( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . '";
google_color_text = "' . ( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . '";
google_color_url = "' . ( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . '";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

</div>
<!-- End small square ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * This just adds the relevant ad CSS file under certain conditions.
	 * The actual logic is elsewhere.
	 *
	 * @param $out Object: OutputPage instance
	 * @param $sk Object: instance of Skin or one of its child classes
	 * @return Boolean: true
	 */
	public static function setupAdCSS( &$out, &$sk ) {
		global $wgAdConfig, $wgRequest, $wgUser;

		if( !$wgAdConfig['enabled'] ) {
			return true;
		}

		// In order for us to load ad-related CSS, the user must either be
		// a mortal (=not staff) or have supplied the forceads parameter in
		// the URL
		if(
			!in_array( 'staff', $wgUser->getEffectiveGroups() ) ||
			$wgRequest->getVal( 'forceads' )
		)
		{
			$title = $out->getTitle();
			$namespace = $title->getNamespace();

			// Okay, the variable name sucks but anyway...normal page != not login page
			$isNormalPage = $title instanceof Title &&
				SpecialPage::resolveAlias( $title->getDBkey() ) !== 'Userlogin';

			// Load ad CSS file when ads are enabled
			if(
				$isNormalPage &&
				in_array( $namespace, $wgAdConfig['namespaces'] )
			)
			{
				if ( get_class( $sk ) == 'SkinMonaco' ) { // Monaco
					$out->addModuleStyles( 'ext.ShoutWikiAds.monaco' );
				} elseif( get_class( $sk ) == 'SkinMonoBook' ) { // Monobook
					if ( $wgAdConfig['monobook']['skyscraper'] ) {
						$out->addModuleStyles( 'ext.ShoutWikiAds.monobook.skyscraper' );
					}
					if ( $wgAdConfig['monobook']['toolbox'] ) {
						$out->addModuleStyles( 'ext.ShoutWikiAds.monobook.button' );
					}
				} elseif ( get_class( $sk ) == 'SkinVector' ) { // Vector
					if ( $wgAdConfig['vector']['skyscraper'] ) {
						$out->addModuleStyles( 'ext.ShoutWikiAds.vector.skyscraper' );
					}
					if ( $wgAdConfig['vector']['toolbox'] ) {
						$out->addModuleStyles( 'ext.ShoutWikiAds.vector.button' );
					}
				} elseif ( get_class( $sk ) == 'SkinModern' ) { // Modern
					if ( $wgAdConfig['modern']['leaderboard'] ) {
						$out->addModuleStyles( 'ext.ShoutWikiAds.modern.leaderboard' );
					}
					// No, the "monobook" here isn't a typo. SkinModern extends
					// Monobook's base class so there's no specific toggle to
					// turn off the button ads for Modern...
					if ( $wgAdConfig['monobook']['toolbox'] ) {
						$out->addModuleStyles( 'ext.ShoutWikiAds.modern.button' );
					}
				} elseif ( get_class( $sk ) == 'SkinCologneBlue' ) { // Cologne Blue
					if ( $wgAdConfig['cologneblue']['leaderboard'] ) {
						$out->addModuleStyles( 'ext.ShoutWikiAds.cologneblue.leaderboard' );
					}
				} elseif ( get_class( $sk ) == 'SkinTruglass' ) { // Truglass
					$out->addModuleStyles( 'ext.ShoutWikiAds.truglass' );
				}
			}
		}

		return true;
	}

	/**
	 * Load toolbox ad for Monobook skin.
	 * @return Boolean: true
	 */
	public static function onMonoBookAfterToolbox() {
		global $wgAdConfig;
		if ( $wgAdConfig['monobook']['toolbox'] ) {
			echo self::loadAd( 'toolbox-button' );
		}
		return true;
	}

	/**
	 * Load skyscraper ad for Monobook skin.
	 * @return Boolean: true
	 */
	public static function onMonoBookAfterContent() {
		global $wgAdConfig;
		if ( $wgAdConfig['monobook']['skyscraper'] ) {
			echo self::loadAd( 'right-column' );
		}
		return true;
	}

	/**
	 * Load skyscraper ad for the Vector skin.
	 *
	 * @return Boolean: true
	 */
	public static function onVectorBeforeFooter() {
		global $wgAdConfig;
		if ( $wgAdConfig['vector']['skyscraper'] ) {
			echo self::loadAd( 'right-column' );
		}
		return true;
	}

	/**
	 * Load the ad box after the toolbox on the Vector skin.
	 *
	 * @return Boolean: true
	 */
	public static function onVectorAfterToolbox() {
		global $wgAdConfig;
		if ( $wgAdConfig['vector']['toolbox'] ) {
			echo self::loadAd( 'toolbox-button' );
		}
		return true;
	}

	/**
	 * Load sidebar ad for Monaco skin.
	 * @return Boolean: true
	 */
	public static function onMonacoSidebar() {
		global $wgAdConfig;
		if ( $wgAdConfig['monaco']['sidebar'] ) {
			echo self::loadAd( 'sidebar' );
		}
		return true;
	}

	/**
	 * Load leaderboard ad in Monaco skin's footer.
	 * @return Boolean: true
	 */
	public static function onMonacoFooter() {
		global $wgAdConfig;
		if ( $wgAdConfig['monaco']['leaderboard'] ) {
			echo self::loadAd( 'leaderboard' );
		}
		return true;
	}

	/**
	 * Load a skyscraper ad in Games skin's "sidebox" (the left-hand box which
	 * contains social sharing links, recent editors, interlanguage links, etc.
	 * Look for "$wantSideBox" in /skins/Games/Games.skin.php).
	 *
	 * The fugly logic here is pretty much the same as in renderTruglassAd()
	 * below.
	 *
	 * @return Boolean: true
	 */
	public static function onGamesSideBox() {
		global $wgAdConfig;
		if ( $wgAdConfig['games']['skyscraper'] ) {
			$skyscraperHTML = self::loadAd( 'right-column' );
			if ( empty( $skyscraperHTML ) ) {
				return true;
			}
			$leaderboardHTML = str_replace(
				'<div id="column-google"',
				'<div id="sideads"',
				$leaderboardHTML
			);
			echo $skyscraperHTML;
		}
		return true;
	}

	/**
	 * Gets a small square ad to display on Nimbus' left side, after the search
	 * box and above the "Did you know" bit.
	 *
	 * @return Boolean: true
	 */
	public static function onNimbusLeftSide() {
		global $wgAdConfig;
		if ( $wgAdConfig['nimbus']['sidebar'] ) {
			$ad = self::loadAd( 'small-square' );
			$output = '<div class="bottom-left-nav-container">' . $ad . '</div>';
			echo $output;
		}
		return true;
	}

	/**
	 * Called *only* by Truglass skin.
	 *
	 * @return Boolean: true
	 */
	public static function renderTruglassAd() {
		global $wgAdConfig;

		if ( $wgAdConfig['truglass']['leaderboard'] ) {
			// Use the universal loader method so that ads aren't shown to
			// privileged users or on login pages, etc.
			$leaderboardHTML = self::loadAd( 'leaderboard' );

			// If we hit an early return case, $leaderboardHTML (as returned
			// by the loadAd method) will be empty and no further processing
			// should occur.
			if ( empty( $leaderboardHTML ) ) {
				return true;
			}

			// Hack to replace the ad div ID with the "correct" one because
			// I'm too lazy to edit Truglass' CSS file(s)
			$leaderboardHTML = str_replace(
				'<div id="truglass-leaderboard-ad"',
				'<div id="topadsleft"',
				$leaderboardHTML
			);

			echo '<!-- Begin Truglass ad (ShoutWikiAds) -->
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td valign="top" id="contentBox">
							<div id="contentCont">
								<table id="topsector" width="100%">
									<tr>
										<td>' . $leaderboardHTML . '</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- End Truglass ad (ShoutWikiAds) -->' . "\n";
		}

		return true;
	}

	/**
	 * Render a leaderboard ad in the site notice area for certain skins.
	 *
	 * @param $siteNotice String: existing site notice HTML (etc.), if any
	 * @return Boolean: true
	 */
	public static function onSiteNoticeAfter( &$siteNotice ) {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$allowedSkins = array( 'cologneblue', 'modern', 'monobook', 'nimbus', 'vector' );
		// Monaco and Truglass have a different leaderboard ad implementation
		// and AdSense's terms of use state that one page may have up to three
		// ads
		if (
			in_array( $skinName, $allowedSkins ) &&
			isset( $wgAdConfig[$skinName]['leaderboard'] ) &&
			$wgAdConfig[$skinName]['leaderboard'] === true
		)
		{
			$siteNotice .= self::loadAd( 'leaderboard' );
		}

		return true;
	}

	/**
	 * Get the current skin's name for CSS ID & classes and system message names.
	 *
	 * @return String: skin name in lowercase
	 */
	public static function determineSkin() {
		global $wgOut;

		return strtolower( $wgOut->getSkin()->getSkinName() );
	}

	/**
	 * Load ads for a defined "slot"
	 * Ad code (div element + JS) is echoed back and no value is returned
	 * except in special cases we return true (early return cases/unrecognized slot)
	 *
	 * @param $type String: what kind of ads to load? Valid values are:
	 *                      'leaderboard', 'sidebar', 'toolbox-button' and
	 *                      'right-column' (=skyscraper) so far
	 * @return String: HTML to output (if any)
	 */
	public static function loadAd( $type ) {
		// Early return cases:
		// ** if we can't show ads on the current page (i.e. if it's the login
		// page or something)
		// ** if the wiki's language code isn't supported by Google AdSense
		// ** if ads aren't enabled for the current namespace
		if ( !self::canShowAds() ) {
			return '';
		}

		if ( !self::isSupportedLanguage() ) {
			return '';
		}

		if ( !self::isEnabledNamespace() ) {
			return '';
		}

		// Main ad logic starts here
		if( $type === 'leaderboard' ) {
			return self::getLeaderboardHTML();
		} elseif( $type === 'sidebar' ) {
			return self::getSidebarHTML();
		} elseif ( $type === 'toolbox-button' ) {
			return self::getToolboxHTML();
		} elseif ( $type === 'right-column' ) {
			return self::getSkyscraperHTML();
		} elseif ( $type === 'small-square' ) {
			return self::getSmallSquareHTML();
		} else { // invalid type/these ads not enabled in $wgAdConfig
			return '';
		}
	}

}
