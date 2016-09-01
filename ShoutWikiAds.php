<?php
/**
 * ShoutWiki Ads -- display Google AdSense ads on skins
 *
 * @file
 * @ingroup Extensions
 * @version 0.4.3
 * @date 28 July 2016
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license https://en.wikipedia.org/wiki/Public_domain Public domain
 * @link https://www.mediawiki.org/wiki/Extension:ShoutWiki_Ads Documentation
 */

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'ShoutWiki Ads',
	'version' => '0.4.3',
	'author' => 'Jack Phoenix',
	'description' => 'Delicious advertisements for everyone!',
	'url' => 'https://www.mediawiki.org/wiki/Extension:ShoutWiki_Ads',
);

// Autoload the class so that we can actually use its functions
$wgAutoloadClasses['ShoutWikiAds'] = __DIR__ . '/ShoutWikiAds.class.php';

// CSS module loading for virtually all skins supported by this extension
$wgHooks['BeforePageDisplay'][] = 'ShoutWikiAds::setupAdCSS';

// Loads AdSense JS in the bottom of the page if a page has ads
$wgHooks['SkinAfterBottomScripts'][] = 'ShoutWikiAds::onSkinAfterBottomScripts';

// Aurora
$wgHooks['AuroraLeftSidebar'][] = 'ShoutWikiAds::onAuroraLeftSidebar';
$wgHooks['SkinAfterContent'][] = 'ShoutWikiAds::onSkinAfterContent';

// BlueCloud was designed by StrategyWiki with ads in mind, so removing them
// from it will mess up the display, which is exactly why we don't handle
// BlueCloud ads here

// Dusk
$wgHooks['DuskAfterToolbox'][] = 'ShoutWikiAds::onDuskAfterToolbox';

// Home
$wgHooks['HomeAfterEverything'][] = 'ShoutWikiAds::onHomeAfterEverything';

// Games
$wgHooks['GamesSideBox'][] = 'ShoutWikiAds::onGamesSideBox';

// Metrolook
$wgHooks['MetrolookRightPanel'][] = 'ShoutWikiAds::onMetrolookRightPanel';
$wgHooks['MetrolookAfterToolbox'][] = 'ShoutWikiAds::onMetrolookAfterToolbox';

// Monaco
$wgHooks['MonacoSetupSkinUserCss'][] = 'ShoutWikiAds::setupAdCSS';
$wgHooks['MonacoSidebar'][] = 'ShoutWikiAds::onMonacoSidebar';
$wgHooks['MonacoFooter'][] = 'ShoutWikiAds::onMonacoFooter';

// MonoBook & Modern
$wgHooks['MonoBookAfterContent'][] = 'ShoutWikiAds::onMonoBookAfterContent';
$wgHooks['MonoBookAfterToolbox'][] = 'ShoutWikiAds::onMonoBookAfterToolbox';

// Nimbus
$wgHooks['NimbusLeftSide'][] = 'ShoutWikiAds::onNimbusLeftSide';

// Quartz
$wgHooks['QuartzSidebarWidgets'][] = 'ShoutWikiAds::onQuartzSidebarWidgets';
$wgHooks['QuartzSidebarWidgetAdvertiser'][] = 'ShoutWikiAds::onQuartzSidebarWidgetAdvertiser';

// Refreshed
$wgHooks['RefreshedFooter'][] = 'ShoutWikiAds::onRefreshedFooter';
$wgHooks['RefreshedInSidebar'][] = 'ShoutWikiAds::onRefreshedInSidebar';

// Truglass
$wgHooks['TruglassInContent'][] = 'ShoutWikiAds::renderTruglassAd';

// Vector
$wgHooks['VectorAfterToolbox'][] = 'ShoutWikiAds::onVectorAfterToolbox';
$wgHooks['VectorBeforeFooter'][] = 'ShoutWikiAds::onVectorBeforeFooter';

// Generic leaderboard ad for most core skins and some custom skins
$wgHooks['SiteNoticeAfter'][] = 'ShoutWikiAds::onSiteNoticeAfter';

// ResourceLoader support for MediaWiki 1.17+
$resourceTemplate = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'ShoutWikiAds',
	'position' => 'top'
);

$wgResourceModules['ext.ShoutWikiAds.aurora.leaderboard'] = $resourceTemplate + array(
	'styles' => 'css/aurora-leaderboard-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.aurora.skyscraper'] = $resourceTemplate + array(
	'styles' => 'css/aurora-skyscraper-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.cologneblue.leaderboard'] = $resourceTemplate + array(
	'styles' => 'css/cologneblue-leaderboard-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.home.leaderboard-bottom'] = $resourceTemplate + array(
	'styles' => 'css/home-leaderboard-bottom-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.home.skyscraper'] = $resourceTemplate + array(
	'styles' => 'css/home-skyscraper-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.metrolook.button'] = $resourceTemplate + array(
	'styles' => 'css/metrolook-button-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.metrolook.leaderboard'] = $resourceTemplate + array(
	'styles' => 'css/metrolook-leaderboard-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.metrolook.wide-skyscraper'] = $resourceTemplate + array(
	'styles' => 'css/metrolook-wide-skyscraper-ad.less'
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

$wgResourceModules['ext.ShoutWikiAds.quartz.square'] = $resourceTemplate + array(
	'styles' => 'css/quartz-square-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.refreshed'] = $resourceTemplate + array(
	'styles' => 'css/refreshed.css'
);

$wgResourceModules['ext.ShoutWikiAds.refreshed.sidebar'] = $resourceTemplate + array( // sic!
	'styles' => 'css/refreshed-button-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.vector.button'] = $resourceTemplate + array(
	'styles' => 'css/vector-button-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.vector.skyscraper'] = $resourceTemplate + array(
	'styles' => 'css/vector-skyscraper-ad.css'
);

$wgResourceModules['ext.ShoutWikiAds.truglass.leaderboard'] = $resourceTemplate + array(
	'styles' => 'css/truglass-leaderboard-ad.css'
);

/* Configuration
$wgAdConfig = array(
	'enabled' => true, // enabled or not? :P
	'adsense-client' => '', // provider number w/o the "pub-" part
	'namespaces' => array( NS_MAIN, NS_TALK ), // array of enabled namespaces
	'mode' => 'static', // set to 'responsive' for responsive ads instead of fixed-width ads

	// set this to true when developing locally to serve ad *images* (as
	// opposed to JS code to render ads) from Google's servers
	'debug' => false,

	'monaco-small-square-ad-slot' => '', // small square (200x200) Monaco ad slot number [Potomac]
	'monaco-leaderboard-ad-slot' => '', // Monaco leaderboard ad slot number [Vidalia]

	'monobook-button-ad-slot' => '', // 125x125 Monobook ad slot number [Montecito]
	'vector-button-ad-slot' => '', // 125x125 Vector ad slot number [Montecito]

	'monobook-skyscraper-ad-slot' => '', // [Dothan]
	'vector-skyscraper-ad-slot' => '', // [Dothan]

	'truglass-leaderboard-ad-slot' => '', // [Tabor]

	// Skin-specific ad configuration
	'aurora' => array(
		'leaderboard' => true, // leaderboard ad in the site notice area
		'leaderboard-bottom' => true, // leaderboard ad after categories, in the bottom of the page
		'skyscraper' => true, // skyscraper ad in the left column, below "Page history" on longer pages
	),
	'cologneblue' => array(
		'leaderboard' => true, // leaderboard ad in the site notice area
	),
	'dusk' => array(
		'banner' => true, // banner in the site notice area
		'toolbox' => true, // small 125x125px ad below the toolbox in the right-hand sidebar
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
