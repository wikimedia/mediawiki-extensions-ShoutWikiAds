<?php
/**
 * ShoutWiki Ads -- display Google AdSense ads on skins
 *
 * @file
 * @ingroup Extensions
 * @version 0.3.3
 * @date 3 July 2013
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://en.wikipedia.org/wiki/Public_domain Public domain
 * @link http://www.mediawiki.org/wiki/Extension:ShoutWiki_Ads Documentation
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Go away.' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'ShoutWiki Ads',
	'namemsg' => 'extensionname-shoutwikiads',
	'version' => '0.3.3',
	'author' => 'Jack Phoenix',
	'descriptionmsg' => 'shoutwikiads-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:ShoutWiki_Ads',
);

$wgMessagesDirs['ShoutWikiAds'] = __DIR__ . '/i18n';

// Autoload the class so that we can actually use its functions
$wgAutoloadClasses['ShoutWikiAds'] = dirname( __FILE__ ) . '/ShoutWikiAds.class.php';

// BlueCloud was designed by StrategyWiki with ads in mind, so removing them
// from it will mess up the display, which is exactly why we don't handle
// BlueCloud ads here

// Games
$wgHooks['GamesSideBox'][] = 'ShoutWikiAds::onGamesSideBox';

// Monaco
$wgHooks['MonacoSetupSkinUserCss'][] = 'ShoutWikiAds::setupAdCSS';
$wgHooks['MonacoSidebar'][] = 'ShoutWikiAds::onMonacoSidebar';
$wgHooks['MonacoFooter'][] = 'ShoutWikiAds::onMonacoFooter';

// MonoBook
$wgHooks['BeforePageDisplay'][] = 'ShoutWikiAds::setupAdCSS';
$wgHooks['MonoBookAfterContent'][] = 'ShoutWikiAds::onMonoBookAfterContent';
$wgHooks['MonoBookAfterToolbox'][] = 'ShoutWikiAds::onMonoBookAfterToolbox';

// Nimbus
$wgHooks['NimbusLeftSide'][] = 'ShoutWikiAds::onNimbusLeftSide';

// Truglass
$wgHooks['TruglassInContent'][] = 'ShoutWikiAds::renderTruglassAd';

// Vector
$wgHooks['VectorAfterToolbox'][] = 'ShoutWikiAds::onVectorAfterToolbox';
$wgHooks['VectorBeforeFooter'][] = 'ShoutWikiAds::onVectorBeforeFooter';

// Generic (Cologne Blue, Modern, Monobook, Nimbus & Vector) leaderboard ad
$wgHooks['SiteNoticeAfter'][] = 'ShoutWikiAds::onSiteNoticeAfter';

// ResourceLoader support for MediaWiki 1.17+
$resourceTemplate = array(
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'ShoutWikiAds'
);

$wgResourceModules['ext.ShoutWikiAds.cologneblue.leaderboard'] = $resourceTemplate + array(
	'styles' => 'css/cologneblue-leaderboard-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.modern.button'] = $resourceTemplate + array(
	'styles' => 'css/modern-button-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.modern.leaderboard'] = $resourceTemplate + array(
	'styles' => 'css/modern-leaderboard-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.monaco'] = $resourceTemplate + array(
	'styles' => 'css/monaco-ads.css'
);

$wgResourceModules['ext.ShoutWikiAds.monobook.button'] = $resourceTemplate + array(
	'styles' => 'css/monobook-button-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.monobook.skyscraper'] = $resourceTemplate + array(
	'styles' => 'css/monobook-skyscraper-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.vector.button'] = $resourceTemplate + array(
	'styles' => 'css/vector-button-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.vector.skyscraper'] = $resourceTemplate + array(
	'styles' => 'css/vector-skyscraper-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.truglass'] = $resourceTemplate + array(
	'styles' => 'css/truglass-ads.css'
);

/* Configuration
$wgAdConfig = array(
	'enabled' => true, // enabled or not? :P
	'adsense-client' => '', // provider number w/o the "pub-" part
	'namespaces' => array( NS_MAIN, NS_TALK ), // array of enabled namespaces

	// set this to true when developing locally to serve ad *images* (as
	// opposed to JS code to render ads) from Google's servers
	'debug' => false,

	'monaco-small-square-ad-slot' => '', // small square (200x200) Monaco ad slot number [Potomac]
	'monaco-leaderboard-ad-slot' => '', // Monaco leaderboard ad slot number [Vidalia]

	'monobook-button-ad-slot' => '', // 125x125 Monobook ad slot number [Montecito]
	'vector-button-ad-slot' => '', // 125x125 Vector ad slot number [Montecito]

	'monobook-skyscraper-ad-slot' => '', [Dothan]
	'vector-skyscraper-ad-slot' => '', [Dothan]

	'truglass-leaderboard-ad-slot' => '', [Tabor]

	// Skin-specific ad configuration
	'cologneblue' => array(
		'leaderboard' => true, // leaderboard ad in the site notice area
	),
	'modern' => array(
		'leaderboard' => true, // leaderboard ad in the site notice area
	),
	'monobook' => array(
		'leaderboard' => true, // leaderboard ad in the site notice area
		'skyscraper' => true, // do we want a skyscraper ad column (Monobook)?
		'toolbox' => true, // or a "button" ad below the toolbox (Monobook)?
	),
	'monaco' => array(
		'sidebar' => true, // 200x200 sidebar ad in the sidebar on Monaco skin
		'leaderboard' => true, // leaderboard (728x90) ad in the footer on Monaco skin
	),
	'truglass' => array(
		'leaderboard' => true, // leaderboard ad for Truglass skin
	),
	'vector' => array(
		'leaderboard' => true, // leaderboard ad in the site notice area
		'skyscraper' => true, // skyscraper ad for the Vector skin
		'toolbox' => true, // button ad below the toolbox on the Vector skin
	),
);
*/
