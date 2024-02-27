<?php

use MediaWiki\MediaWikiServices;

/**
 * ShoutWikiAds class -- contains the hooked functions and some other crap for
 * displaying the advertisements.
 *
 * Route all requests through loadAd( $type ) to ensure correct processing, such
 * as not showing ads on login pages etc.
 *
 * We allow wiki admins to configure some things because our "sane defaults"
 * may clash with certain CSS styles (dark wikis, for example).
 * The Message objects are called with ->inContentLanguage(), because CSS is
 * "global" (doesn't vary depending on the user's language).
 * NOTE: the above applies only to static ads; responsive ads can be customized
 * via CSS as usual.
 *
 * All class methods are public and static.
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix
 * @license https://en.wikipedia.org/wiki/Public_domain Public domain
 * @link https://www.mediawiki.org/wiki/Extension:ShoutWiki_Ads Documentation
 */

class ShoutWikiAds {

	/**
	 * Used to determine whether to load AdSense JS in the page footer or not
	 * when we're serving responsive ads; obviously it should only be loaded if
	 * the current page has at least one active ad slot.
	 *
	 * @var bool
	 */
	public static $PAGE_HAS_ADS = false;

	/**
	 * Can we show ads on the current page?
	 *
	 * @param User $user
	 * @return bool False if ads aren't enabled or the current page is
	 *   Special:UserLogin (login page) or if the user is autoconfirmed and the
	 *   forceads parameter is NOT in the URL, otherwise true.
	 */
	private static function canShowAds( User $user ) {
		// phpcs:ignore MediaWiki.Usage.DeprecatedGlobalVariables.Deprecated$wgTitle
		global $wgAdConfig, $wgTitle, $wgRequest;

		if ( !$wgAdConfig['enabled'] ) {
			return false;
		}

		$effectiveGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserEffectiveGroups( $user );
		if (
			$wgTitle instanceof Title && $wgTitle->isSpecial( 'Userlogin' ) ||
			in_array( 'staff', $effectiveGroups ) && !$wgRequest->getVal( 'forceads' )
		) {
			return false;
		}

		// No configuration for this skin? Bail out!
		if ( !isset( $wgAdConfig[self::determineSkin()] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the current wiki's language is supported by the ad provider
	 * (currently checks against Google's list).
	 *
	 * @return bool True if the language is supported, otherwise false
	 */
	public static function isSupportedLanguage() {
		global $wgLanguageCode;

		// "Publishers are also not permitted to place AdSense code on pages
		// with content primarily in an unsupported language"
		// @see https://www.google.com/adsense/support/bin/answer.py?answer=9727
		$supportedAdLanguages = [
			// Arabic -> Dutch (+some Chinese variants)
			'ar', 'bg', 'zh', 'zh-hans', 'zh-hant', 'hr', 'cs', 'da', 'nl',
			// English and its variants
			'en', 'en-gb', 'en-ca', 'en-x-piglatin',
			// Finnish -> Polish
			'fi', 'fr', 'de', 'el', 'he', 'hu', 'hu-formal', 'it', 'ja', 'ko', 'no', 'pl',
			// Portuguese -> Turkish
			'pt', 'pt-br', 'ro', 'ru', 'sr', 'sr-ec', 'sr-el', 'sk', 'es', 'sv', 'th', 'tr',
			// http://adsense.blogspot.com/2009/08/adsense-launched-in-lithuanian.html
			'lt', 'lv', 'uk',
			// Vietnamese http://adsense.blogspot.co.uk/2013/05/adsense-now-speaks-vietnamese.html
			'vi',
			// Slovenian & Estonian http://adsense.blogspot.co.uk/2012/06/adsense-now-available-for-websites-in.html
			'sl', 'et',
			// Indonesian http://adsense.blogspot.co.uk/2012/02/adsense-now-speaks-indonesian.html
			'id',
			// Languages added post 2013 -
			'bn', 'ca', 'tl', 'hi', 'ms', 'ml', 'mr', 'es-419', 'ta', 'te', 'ur',
			'gu', 'kn', 'ko-kp', 'pnb', 'pa'
		];

		if ( in_array( $wgLanguageCode, $supportedAdLanguages ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the current namespace is allowed to show ads.
	 *
	 * @return bool True if the namespace is supported, otherwise false
	 */
	public static function isEnabledNamespace() {
		global $wgAdConfig;

		$title = RequestContext::getMain()->getTitle(); // @todo FIXME filthy hack
		if ( !$title ) {
			return false;
		}
		$namespace = $title->getNamespace();

		if ( in_array( $namespace, $wgAdConfig['namespaces'] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Skin-agnostic way of getting the HTML for a Google AdSense banner ad (468x60px).
	 *
	 * @return string HTML code
	 */
	public static function getBannerHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-banner-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-banner-ad-slot'];
		}

		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
			return '<!-- Begin banner ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-banner-ad" class="' . $skinName . '-ad noprint">
			<img src="http://www.google.com/help/hc/images/adsense/adsense_185665_adformat-text_468x60_en.png" alt="" />
		</div>
<!-- End banner ad (ShoutWikiAds) -->' . "\n";
		}

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
				style="display:block"
				data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
				data-ad-slot="' . $adSlot . '"
				data-ad-format="horizontal"></ins>
			<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
			$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-banner-ad-color-border' )->inContentLanguage();
			$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-banner-ad-color-bg' )->inContentLanguage();
			$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-banner-ad-color-link' )->inContentLanguage();
			$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-banner-ad-color-text' )->inContentLanguage();
			$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-banner-ad-color-url' )->inContentLanguage();

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

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 468;
google_ad_height = 60;
google_ad_format = "468x60_as";
//google_ad_type = "";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? $colorBorderDefault : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? $colorBGDefault : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? $colorLinkDefault : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? $colorURLDefault : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return '<!-- Begin banner ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-banner-ad" class="' . $skinName . '-ad noprint">' .
			$adHTML . '</div>
		<!-- End banner ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Skin-agnostic way of getting the HTML for a Google AdSense sidebar
	 * ad (200x200px).
	 *
	 * @return string HTML code
	 */
	public static function getSidebarHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$id = "{$skinName}-sidebar-ad";
		$classes = "{$skinName}-ad noprint";

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-small-square-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-small-square-ad-slot'];
		}

		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
			return '<!-- Begin sidebar ad (ShoutWikiAds) -->
		<div id="' . $id . '" class="' . $classes . '">
			<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_200x200.png" alt="" />
		</div>
<!-- End sidebar ad (ShoutWikiAds) -->' . "\n";
		}

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
				style="display:block"
				data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
				data-ad-slot="' . $adSlot . '"
				data-ad-format="rectangle"></ins>
			<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
			$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-border' )->inContentLanguage();
			$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-bg' )->inContentLanguage();
			$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-link' )->inContentLanguage();
			$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-text' )->inContentLanguage();
			$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-sidebar-ad-color-url' )->inContentLanguage();

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 200;
google_ad_height = 200;
google_ad_format = "200x200_as";
google_ad_type = "text";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return '<!-- Begin sidebar ad (ShoutWikiAds) -->
		<div id="' . $id . '" class="' . $classes . '">' . $adHTML . '</div>
		<!-- End sidebar ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Skin-agnostic way of getting the HTML for a Google AdSense square ad (250x250px).
	 *
	 * @return string HTML code
	 */
	public static function getSquareHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-square-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-square-ad-slot'];
		}

		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
			return '<!-- Begin square ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-square-ad" class="' . $skinName . '-ad noprint">
			<img src="https://storage.googleapis.com/support-kms-prod/SNP_2922347_en_v0" alt="" />
		</div>
<!-- End banner ad (ShoutWikiAds) -->' . "\n";
		}

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
				style="display:block"
				data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
				data-ad-slot="' . $adSlot . '"
				data-ad-format="rectangle"></ins>
			<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
			$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-square-ad-color-border' )->inContentLanguage();
			$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-square-ad-color-bg' )->inContentLanguage();
			$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-square-ad-color-link' )->inContentLanguage();
			$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-square-ad-color-text' )->inContentLanguage();
			$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-square-ad-color-url' )->inContentLanguage();

			$colorBorderDefault = 'F6F4C4';
			$colorBGDefault = 'FFFFE0';
			$colorLinkDefault = '000000';
			$colorURLDefault = '002BB8';

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 250;
google_ad_height = 250;
google_ad_format = "250x250_as";
//google_ad_type = "";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? $colorBorderDefault : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? $colorBGDefault : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? $colorLinkDefault : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? $colorURLDefault : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return '<!-- Begin square ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-square-ad" class="' . $skinName . '-ad noprint">' .
			$adHTML . '</div>
		<!-- End banner ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Skin-agnostic way of getting the HTML for a Google AdSense leaderboard
	 * ad (728x90px).
	 *
	 * @return string HTML code
	 */
	public static function getLeaderboardHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-leaderboard-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-leaderboard-ad-slot'];
		}

		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
			return '<!-- Begin leaderboard ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-leaderboard-ad" class="' . $skinName . '-ad noprint">
			<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_728x90.png" alt="" />
		</div>
<!-- End leaderboard ad (ShoutWikiAds) -->' . "\n";
		}

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
				style="display:block"
				data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
				data-ad-slot="' . $adSlot . '"
				data-ad-format="horizontal"></ins>
			<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
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

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 728;
google_ad_height = 90;
google_ad_format = "728x90_as";
//google_ad_type = "";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? $colorBorderDefault : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? $colorBGDefault : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? $colorLinkDefault : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? $colorURLDefault : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return '<!-- Begin leaderboard ad (ShoutWikiAds) -->
		<div id="' . $skinName . '-leaderboard-ad" class="' . $skinName . '-ad noprint">' .
			$adHTML . '</div>
			<!-- End leaderboard ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Get the HTML for a toolbox ad (125x125).
	 *
	 * @return string HTML code
	 */
	public static function getToolboxHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-button-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-button-ad-slot'];
		}

		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
			return '<!-- Begin toolbox ad (ShoutWikiAds) -->
<div id="p-ads-left" class="noprint">
	<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_125x125_en.png" alt="" />
</div>
<!-- End toolbox ad (ShoutWikiAds) -->' . "\n";
		}

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
		style="display:block"
		data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
		data-ad-slot="' . $adSlot . '"
		data-ad-format="rectangle"></ins>
	<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
			$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-border' )->inContentLanguage();
			$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-bg' )->inContentLanguage();
			$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-link' )->inContentLanguage();
			$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-text' )->inContentLanguage();
			$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-toolbox-ad-color-url' )->inContentLanguage();

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 125;
google_ad_height = 125;
google_ad_format = "125x125_as";
google_ad_type = "text";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return '<!-- Begin toolbox ad (ShoutWikiAds) -->
<div id="p-ads-left" class="noprint">' . $adHTML . '</div>
<!-- End toolbox ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Get the HTML for a normal skyscraper ad (120x600).
	 *
	 * @return string HTML code
	 */
	public static function getSkyscraperHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-skyscraper-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-skyscraper-ad-slot'];
		}

		// Just output an image in debug mode
		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
			return "\n" . '<!-- Begin skyscraper ad (ShoutWikiAds) -->
<div id="column-google" class="' . $skinName . '-ad noprint">
	<img src="http://www.google.com/help/hc/images/adsense_185665_adformat-text_120x600.png" alt="" />
</div>
<!-- End skyscraper ad (ShoutWikiAds) -->' . "\n";
		}

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
		style="display:block"
		data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
		data-ad-slot="' . $adSlot . '"
		data-ad-format="vertical"></ins>
	<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
			$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-border' )->inContentLanguage();
			$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-bg' )->inContentLanguage();
			$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-link' )->inContentLanguage();
			$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-text' )->inContentLanguage();
			$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-rightcolumn-ad-color-url' )->inContentLanguage();

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 120;
google_ad_height = 600;
google_ad_format = "120x600_as";
//google_ad_type = "text";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return "\n" . '<!-- Begin skyscraper ad (ShoutWikiAds) -->
<div id="column-google" class="' . $skinName . '-ad noprint">' . $adHTML . '</div>
<!-- End skyscraper ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Get the HTML for a wide skyscraper ad (160x600).
	 *
	 * @return string HTML code
	 */
	public static function getWideSkyscraperHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-wide-skyscraper-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-wide-skyscraper-ad-slot'];
		}

		// Just output an image in debug mode
		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
			return "\n" . '<!-- Begin wide skyscraper ad (ShoutWikiAds) -->
<div id="column-google" class="' . $skinName . '-ad noprint">
	<img src="https://storage.googleapis.com/support-kms-prod/SNP_1DA7588EEB5450EE2A22C2B2F0A6458A61C0_2922342_en_v1" alt="" />
</div>
<!-- End wide skyscraper ad (ShoutWikiAds) -->' . "\n";
		}

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
		style="display:block"
		data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
		data-ad-slot="' . $adSlot . '"
		data-ad-format="vertical"></ins>
	<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
			$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-wide-skyscraper-ad-color-border' )->inContentLanguage();
			$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-wide-skyscraper-ad-color-bg' )->inContentLanguage();
			$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-wide-skyscraper-ad-color-link' )->inContentLanguage();
			$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-wide-skyscraper-ad-color-text' )->inContentLanguage();
			$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-wide-skyscraper-ad-color-url' )->inContentLanguage();

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 160;
google_ad_height = 600;
google_ad_format = "160x600_as";
//google_ad_type = "text";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return "\n" . '<!-- Begin wide skyscraper ad (ShoutWikiAds) -->
<div id="column-google" class="' . $skinName . '-ad noprint">' . $adHTML . '</div>
<!-- End wide skyscraper ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * Get the HTML for a small square ad (200x200).
	 *
	 * @return string HTML code
	 */
	public static function getSmallSquareHTML() {
		global $wgAdConfig;

		$skinName = self::determineSkin();

		$adSlot = '';
		if ( isset( $wgAdConfig[$skinName . '-small-square-ad-slot'] ) ) {
			$adSlot = $wgAdConfig[$skinName . '-small-square-ad-slot'];
		}

		// Just output an image in debug mode
		if ( isset( $wgAdConfig['debug'] ) && $wgAdConfig['debug'] === true ) {
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

		if ( isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] == 'responsive' ) {
			$adHTML = '<ins class="adsbygoogle"
		style="display:block"
		data-ad-client="ca-pub-' . $wgAdConfig['adsense-client'] . '"
		data-ad-slot="' . $adSlot . '"
		data-ad-format="rectangle"></ins>
	<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
		} else {
			$borderColorMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-border' )->inContentLanguage();
			$colorBGMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-bg' )->inContentLanguage();
			$colorLinkMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-link' )->inContentLanguage();
			$colorTextMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-text' )->inContentLanguage();
			$colorURLMsg = wfMessage( 'shoutwiki-' . $skinName . '-smallsquare-ad-color-url' )->inContentLanguage();

			$adHTML = '<script type="text/javascript">
google_ad_client = "pub-' . $wgAdConfig['adsense-client'] . '";
google_ad_slot = "' . $adSlot . '";
google_ad_width = 200;
google_ad_height = 200;
google_ad_format = "200x200_as";
//google_ad_type = "text";
google_ad_channel = "";
google_color_border = ' . Xml::encodeJsVar( $borderColorMsg->isDisabled() ? 'F6F4C4' : $borderColorMsg->text() ) . ';
google_color_bg = ' . Xml::encodeJsVar( $colorBGMsg->isDisabled() ? 'FFFFE0' : $colorBGMsg->text() ) . ';
google_color_link = ' . Xml::encodeJsVar( $colorLinkMsg->isDisabled() ? '000000' : $colorLinkMsg->text() ) . ';
google_color_text = ' . Xml::encodeJsVar( $colorTextMsg->isDisabled() ? '000000' : $colorTextMsg->text() ) . ';
google_color_url = ' . Xml::encodeJsVar( $colorURLMsg->isDisabled() ? '002BB8' : $colorURLMsg->text() ) . ';
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		}

		return "\n" . '<!-- Begin small square ad (ShoutWikiAds) -->
<div id="small-square-ad" class="' . $skinName . '-ad noprint">' . $adHTML . '</div>
<!-- End small square ad (ShoutWikiAds) -->' . "\n";
	}

	/**
	 * This just adds the relevant ad CSS file under certain conditions.
	 * The actual logic is elsewhere.
	 *
	 * @param OutputPage &$out
	 * @param Skin &$sk
	 * @return bool
	 */
	public static function setupAdCSS( &$out, &$sk ) {
		global $wgAdConfig;

		if ( !$wgAdConfig['enabled'] ) {
			return true;
		}

		// In order for us to load ad-related CSS, the user must either be
		// a mortal (=not staff) or have supplied the forceads parameter in
		// the URL
		$effectiveGroups = MediaWikiServices::getInstance()->getUserGroupManager()
			->getUserEffectiveGroups( $out->getUser() );
		if (
			!in_array( 'staff', $effectiveGroups ) ||
			$out->getRequest()->getVal( 'forceads' )
		) {
			$title = $out->getTitle();
			$namespace = $title->getNamespace();

			// Okay, the variable name sucks but anyway...normal page != not login page
			$isNormalPage = $title instanceof Title &&
				!$title->isSpecial( 'Userlogin' );

			// Load ad CSS file when ads are enabled
			if (
				$isNormalPage &&
				in_array( $namespace, $wgAdConfig['namespaces'] )
			) {
				// Use reflection, some skins are namespaced.
				$skinClass = str_replace( 'Skin', '', ( new ReflectionClass( $sk ) )->getShortName() );
				$skinClass = strtolower( $skinClass );

				// New Vector nonsense
				if ( $skinClass === 'vectorlegacy' ) {
					$skinClass = 'vector';
				}

				// Fixes the issue where $skinClass gets set to "mustache"
				// for MonoBook in MW 1.39
				// (the strtolower() is just additional paranoia, probably not
				// even really needed, eh)
				if ( strtolower( $sk->getSkinName() ) !== $skinClass ) {
					$skinClass = strtolower( $sk->getSkinName() );
				}

				if ( isset( $wgAdConfig[$skinClass] ) ) {
					$modules = [];

					$resourceLoader = $out->getResourceLoader();

					// Iterate over the enabled
					foreach ( $wgAdConfig[$skinClass] as $enabledAdType => $unused ) {
						$moduleName = "ext.ShoutWikiAds.{$skinClass}.{$enabledAdType}";

						// Aurora's sitenotice leaderboard doesn't need any additional CSS
						if ( $skinClass == 'aurora' && $enabledAdType == 'leaderboard-bottom' ) {
							continue;
						}

						// Special cases have an awful habit of becoming all too common around here...
						if ( $skinClass == 'refreshed' && isset( $wgAdConfig['mode'] ) && $wgAdConfig['mode'] === 'responsive' ) {
							$moduleName = 'ext.ShoutWikiAds.refreshed';
						}

						if ( $skinClass == 'monaco' ) {
							$moduleName = "ext.ShoutWikiAds.{$skinClass}";
						}

						if ( $enabledAdType == 'toolbox' ) {
							$moduleName = str_replace( 'toolbox', 'button', $moduleName );
						}

						// Sanity check -- is there such a module?
						if ( $resourceLoader->isModuleRegistered( $moduleName ) && $resourceLoader->getModule( $moduleName ) ) {
							$modules[] = $moduleName;
						}
					}

					if ( !empty( $modules ) ) {
						$out->addModuleStyles( $modules );
					}
				}
			}
		}

		return true;
	}

	/**
	 * Load toolbox ad for Monobook, Modern and Vector skins
	 *
	 * @param Skin $skin
	 * @param string $portlet
	 * @param string &$html
	 */
	public static function onSkinAfterPortlet( Skin $skin, $portlet, &$html ) {
		global $wgAdConfig;

		$skins = [ 'vector', 'modern', 'monobook' ];
		$user = $skin->getUser();
		$skin = $skin->getSkinName();

		if (
			in_array( $skin, $skins ) &&
			isset( $wgAdConfig[$skin]['toolbox'] ) &&
			$wgAdConfig[$skin]['toolbox'] &&
			$portlet === 'tb'
		) {
			$html .= self::loadAd( 'toolbox-button', $user );
		}
	}

	/**
	 * Load sidebar ad for Monaco skin.
	 *
	 * @return bool
	 */
	public static function onMonacoSidebar() {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['monaco']['sidebar'] ) &&
			$wgAdConfig['monaco']['sidebar']
		) {
			echo self::loadAd( 'sidebar' );
		}
		return true;
	}

	/**
	 * Load leaderboard ad in Monaco skin's footer.
	 *
	 * @return bool
	 */
	public static function onMonacoFooter() {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['monaco']['leaderboard'] ) &&
			$wgAdConfig['monaco']['leaderboard']
		) {
			echo self::loadAd( 'leaderboard' );
		}
		return true;
	}

	/**
	 * Load a skyscraper ad in Aurora's left-hand sidebar, below the "page history"
	 * link.
	 *
	 * @param AuroraTemplate $auroraTemplate
	 * @return bool
	 */
	public static function onAuroraLeftSidebar( $auroraTemplate ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['aurora']['skyscraper'] ) &&
			$wgAdConfig['aurora']['skyscraper']
		) {
			// Only show this ad on existing pages as it'd strech nonexistent ones
			// quite a bit
			if ( $auroraTemplate->getSkin()->getTitle()->exists() ) {
				// My naming conventions suck, I know.
				echo self::loadAd( 'right-column' );
			}
		}
		return true;
	}

	/**
	 * Load a leaderboard ad in Aurora's footer.
	 *
	 * @param string &$data
	 * @param Skin $skin
	 * @return bool
	 */
	public static function onSkinAfterContent( &$data, Skin $skin ) {
		global $wgAdConfig;

		$skinName = $skin->getSkinName();
		$user = $skin->getUser();

		if (
			$skinName === 'aurora' &&
			isset( $wgAdConfig['aurora']['leaderboard-bottom'] ) &&
			$wgAdConfig['aurora']['leaderboard-bottom']
		) {
			$adHTML = self::loadAd( 'leaderboard', $user );
			$data = str_replace(
				// Quick HTML validation fix
				'<div id="aurora-leaderboard-ad"',
				'<div id="aurora-leaderboard-ad-2"',
				$adHTML
			);
		}

		if (
			$skinName === 'vector' &&
			isset( $wgAdConfig['vector']['skyscraper'] ) &&
			$wgAdConfig['vector']['skyscraper']
		) {
			$data = self::loadAd( 'right-column', $user );
		}

		if (
			$skinName === 'home' &&
			isset( $wgAdConfig['home']['leaderboard-bottom'] ) &&
			$wgAdConfig['home']['leaderboard-bottom']
		) {
			$data = self::loadAd( 'leaderboard', $user );
		}

		if (
			$skinName === 'monobook' &&
			isset( $wgAdConfig['monobook']['skyscraper'] ) &&
			$wgAdConfig['monobook']['skyscraper']
		) {
			$data = self::loadAd( 'right-column', $user );
		}

		return true;
	}

	/**
	 * Load a small button ad in Dusk's toolbox (well, this code runs in the
	 * toolbox() function, but after the #p-tb div has been closed).
	 *
	 * @param DuskTemplate $dusk
	 * @return bool
	 */
	public static function onDuskAfterToolbox( $dusk ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['dusk']['toolbox'] ) &&
			$wgAdConfig['dusk']['toolbox']
		) {
			echo self::loadAd( 'toolbox-button', $dusk->getSkin()->getUser() );
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
	 * @return bool
	 */
	public static function onGamesSideBox() {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['games']['skyscraper'] ) &&
			$wgAdConfig['games']['skyscraper']
		) {
			$skyscraperHTML = self::loadAd( 'right-column' );
			if ( empty( $skyscraperHTML ) ) {
				return true;
			}
			$skyscraperHTML = str_replace(
				'<div id="column-google"',
				'<div id="sideads"',
				$skyscraperHTML
			);
			echo $skyscraperHTML;
		}
		return true;
	}

	/**
	 * Skyscraper ads -- two of 'em -- for the Home skin.
	 *
	 * @param HomeTemplate $homeTemplate
	 * @return bool
	 */
	public static function onHomeAfterEverything( $homeTemplate ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['home']['skyscraper'] ) &&
			$wgAdConfig['home']['skyscraper']
		) {
			$adHTML = self::loadAd( 'skyscraper' );
			if ( $adHTML ) {
				// Remove the IDs altogether
				$adHTML = str_replace(
					'id="column-google"',
					'',
					$adHTML
				);
				// And instead hackily inject the appropriate classes
				$adLeft = str_replace( 'class="', 'class="column-google-left ', $adHTML );
				$adRight = str_replace( 'class="', 'class="column-google-right ', $adHTML );
				$output = $adLeft . $adRight;
				// Finally display 'em, too!
				echo $output;
			}
		}
		return true;
	}

	/**
	 * Metrolook toolbox 125x125px ad.
	 *
	 * @param MetrolookTemplate $metrolookTemplate
	 * @return bool
	 */
	public static function onMetrolookAfterToolbox( $metrolookTemplate ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['metrolook']['toolbox'] ) &&
			$wgAdConfig['metrolook']['toolbox']
		) {
			echo self::loadAd( 'toolbox-button', $metrolookTemplate->getSkin()->getUser() );
		}
		return true;
	}

	/**
	 * Metrolook's right sidebar has a wider skyscraper ad than MonoBook & co.
	 *
	 * @param MetrolookTemplate $metrolookTemplate
	 * @return bool
	 */
	public static function onMetrolookRightPanel( $metrolookTemplate ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['metrolook']['wide-skyscraper'] ) &&
			$wgAdConfig['metrolook']['wide-skyscraper']
		) {
			// Oh gods why...
			$s = self::loadAd( 'wide-skyscraper', $metrolookTemplate->getSkin()->getUser() );
			$s = str_replace(
				[ '<div id="column-google" class="metrolook-ad noprint">', '</div>' ],
				'',
				$s
			);
			echo $s;
		}
		return true;
	}

	/**
	 * Gets a small square ad to display on Nimbus' left side, after the search
	 * box and above the "Did you know" bit.
	 *
	 * @return bool
	 */
	public static function onNimbusLeftSide() {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['nimbus']['sidebar'] ) &&
			$wgAdConfig['nimbus']['sidebar']
		) {
			$ad = self::loadAd( 'small-square' );
			$output = '<div class="bottom-left-nav-container">' . $ad . '</div>';
			echo $output;
		}
		return true;
	}

	/**
	 * Load a square ad on the Quartz skin, in the sidebar.
	 *
	 * @param QuartzTemplate $quartz
	 * @param string &$adBody
	 * @return bool
	 */
	public static function onQuartzSidebarWidgets( $quartz, &$adBody ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['quartz']['square'] ) &&
			$wgAdConfig['quartz']['square']
		) {
			$adBody = self::loadAd( 'square' );
		}
		return true;
	}

	/**
	 * Load a square ad on the Quartz skin, in the sidebar.
	 *
	 * @param QuartzTemplate $quartz
	 * @param string &$adBody
	 * @return bool
	 */
	public static function onQuartzSidebarWidgetAdvertiser( $quartz, &$adBody ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['quartz']['square'] ) &&
			$wgAdConfig['quartz']['square']
		) {
			$adBody = str_replace(
				'id="quartz-square-ad',
				'id="quartz-square-ad-2',
				self::loadAd( 'square' )
			);
		}
		return true;
	}

	/**
	 * Renders a leaderboard ad in the footer on the Refreshed skin.
	 * I18n message is provided by the Refreshed skin in /skins/Refreshed/i18n/<langCode>.json.
	 *
	 * @param string &$footerExtra
	 * @return bool
	 */
	public static function onRefreshedFooter( &$footerExtra ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['refreshed']['leaderboard-footer'] ) &&
			$wgAdConfig['refreshed']['leaderboard-footer']
		) {
			$adHTML = self::loadAd( 'leaderboard' );
			$footerExtra = str_replace(
				// <s>Quick HTML validation fix</s>
				// Just a simple renaming because Refreshed expects the ID to be #advert :|
				'<div id="refreshed-leaderboard-ad" class="refreshed-ad noprint">',
				'<div id="advert" class="refreshed-ad noprint">' .
					// Also inject the title here, as per the MW.org manual page
					wfMessage( 'refreshed-advert' )->parseAsBlock(),
				$adHTML
			);
		}
		return true;
	}

	/**
	 * Renders a 200x200 ad in the sidebar on the Refreshed skin.
	 *
	 * @param RefreshedTemplate $tpl
	 * @return bool
	 */
	public static function onRefreshedInSidebar( $tpl ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['refreshed']['sidebar'] ) &&
			$wgAdConfig['refreshed']['sidebar']
		) {
			// sic!
			// The *slot* is _in_ the sidebar, but what we call a sidebar ad
			// (200x200px) is too wide to be used here!
			echo self::loadAd( 'toolbox-button', $tpl->getSkin()->getUser() );
		}
		return true;
	}

	/**
	 * Called *only* by the Truglass skin.
	 *
	 * @return bool
	 */
	public static function renderTruglassAd() {
		global $wgAdConfig;

		if (
			isset( $wgAdConfig['truglass']['leaderboard'] ) &&
			$wgAdConfig['truglass']['leaderboard']
		) {
			// Use the universal loader method so that ads aren't shown to
			// privileged users or on login pages, etc.
			$leaderboardHTML = self::loadAd( 'leaderboard' );

			// If we hit an early return case, $leaderboardHTML (as returned
			// by the loadAd method) will be empty and no further processing
			// should occur.
			if ( empty( $leaderboardHTML ) ) {
				return true;
			}

			echo '<!-- Begin Truglass ad (ShoutWikiAds) -->
			<table class="fullwidth truglass-ad-container">
				<tbody>
					<tr>
						<td id="contentBox">
							<div id="contentCont">
								<table id="topsector" class="fullwidth">
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
	 * Render a leaderboard ad in the site notice area for almost all skins,
	 * provided that the leaderboard ad for the said skin(s) is enabled in
	 * advertising configuration (or a banner ad for Dusk).
	 *
	 * @param string &$siteNotice Existing site notice HTML (etc.), if any
	 * @param Skin $skin
	 * @return bool
	 */
	public static function onSiteNoticeAfter( &$siteNotice, Skin $skin ) {
		global $wgAdConfig;

		$skinName = self::determineSkin();
		$user = $skin->getUser();

		// Monaco and Truglass have a different leaderboard ad implementation
		// and AdSense's terms of use state that one page may have up to three
		// ads; Dusk & DuskToDawn are handled below, as you can see.
		$blacklist = [
			'dusk', 'dusktodawn', 'monaco', 'truglass'
		];
		if (
			!in_array( $skinName, $blacklist ) &&
			isset( $wgAdConfig[$skinName]['leaderboard'] ) &&
			$wgAdConfig[$skinName]['leaderboard'] === true
		) {
			$siteNotice .= self::loadAd( 'leaderboard', $user );
		} elseif (
			// Both Dusk* skins have a damn small content area; in fact, it's
			// so small it can't fit a normal leaderboard, so we display a banner
			// in the sitenotice area instead (when enabled in config, of course)
			( $skinName == 'dusk' || $skinName == 'dusktodawn' ) &&
			isset( $wgAdConfig[$skinName]['banner'] ) &&
			$wgAdConfig[$skinName]['banner'] === true
		) {
			$siteNotice .= self::loadAd( 'banner', $user );
		}

		return true;
	}

	/**
	 * If a page has ads, load the AdSense JS in the bottom of the page.
	 * This works because we don't need to fiddle with ResourceLoader 'cause
	 * AdSense JS is hosted by Google.
	 *
	 * We also don't need to load these <script> tags more than once per page,
	 * so this is the perfect place for that. Instead of calling the script up
	 * to three times per page, we can do it only once. Yay performance!
	 *
	 * @param Skin $skin
	 * @param string &$text
	 */
	public static function onSkinAfterBottomScripts( $skin, &$text ) {
		global $wgAdConfig;
		if (
			self::$PAGE_HAS_ADS &&
			// Development trick/tweak: check AdSense publisher ID's presence to _not_
			// load this on devboxes. Apparently calling self::canShowAds( $skin->getUser() )
			// doesn't work for some reason, and the ad debug mode check below evaluates to true
			// when a custom ad config has not been set up due to the presence of sane defaults
			// in extension.json.
			!empty( $wgAdConfig['adsense-client'] ) &&
			isset( $wgAdConfig['mode'] ) &&
			$wgAdConfig['mode'] == 'responsive' &&
			isset( $wgAdConfig['debug'] ) &&
			$wgAdConfig['debug'] === false
		) {
			$text .= '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>' . "\n";
		}
	}

	/**
	 * Render a right rail module ad for Mirage.
	 *
	 * @param IContextSource $context
	 * @param array &$modules
	 */
	public static function onMirageGetRightRailModules( $context, array &$modules ) {
		global $wgAdConfig;
		if (
			isset( $wgAdConfig['mirage']['square'] ) &&
			$wgAdConfig['mirage']['square']
		) {
			$ad = self::loadAd( 'square', $context->getUser() );

			// The ad should come first, to keep it into view.
			$modules = [
				'MirageAdModule' => [
					'factory' => static function ( $skin ) use ( $ad ) {
						return new MirageAdModule( $skin, $ad );
					}
				]
			] + $modules;
		}
	}

	/**
	 * Get the current skin's name for CSS ID & classes and system message names.
	 *
	 * @return string Skin name in lowercase
	 */
	public static function determineSkin() {
		global $wgOut;

		return strtolower( $wgOut->getSkin()->getSkinName() );
	}

	/**
	 * Load ads for a defined "slot"
	 * Ad code (div element + JS) is returned, and in case if we hit an early
	 * return/unrecognized slot, we return an empty string.
	 *
	 * @param string $type What kind of ads to load?
	 * @param User|null $user A user to pass, if available
	 * @return string HTML to output (if any)
	 */
	private static function loadAd( $type, $user = null ) {
		if ( $user === null ) {
			// Caller does not have a user
			$user = RequestContext::getMain()->getUser();
		}

		// Early return cases:
		// ** if we can't show ads on the current page (i.e. if it's the login
		// page or something)
		// ** if the wiki's language code isn't supported by Google AdSense
		// ** if ads aren't enabled for the current namespace
		if ( !self::canShowAds( $user ) ) {
			wfDebugLog( 'ShoutWikiAds', 'Early return case #1: can\'t show ads' );
			return '';
		}

		if ( !self::isSupportedLanguage() ) {
			wfDebugLog( 'ShoutWikiAds', 'Early return case #2: language code is not suppored by AdSense' );
			return '';
		}

		if ( !self::isEnabledNamespace() ) {
			wfDebugLog( 'ShoutWikiAds', 'Early return case #3: ads not enabled for this namespace' );
			return '';
		}

		// Main ad logic starts here
		$allowedAdTypes = [
			'banner', 'leaderboard', 'sidebar', 'toolbox-button',
			'right-column', 'skyscraper', 'small-square', 'square', 'wide-skyscraper'
		];
		if ( in_array( $type, $allowedAdTypes ) ) {
			self::$PAGE_HAS_ADS = true;
		}

		switch ( $type ) {
			case 'banner':
				return self::getBannerHTML();
			case 'leaderboard':
				return self::getLeaderboardHTML();
			case 'sidebar':
				return self::getSidebarHTML();
			case 'toolbox-button':
				return self::getToolboxHTML();
			case 'right-column': // old and ugly name
			case 'skyscraper': // standard name used by AdSense documentation, etc.
				return self::getSkyscraperHTML();
			case 'small-square':
				return self::getSmallSquareHTML();
			case 'square':
				return self::getSquareHTML();
			case 'wide-skyscraper':
				return self::getWideSkyscraperHTML();
			default: // invalid type/these ads not enabled in $wgAdConfig
				return '';
		}
	}

}
