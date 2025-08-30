<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class UserAgents extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * User Agent Class
     * --------------------------------------------------------------------------
     *
     * The user agent class contains functions that help identify information
     * about the browser, platform, robot, or mobile device of the user.
     */

    /**
     * Platform
     */
    public array $platforms = [
        'windows nt 10.0'    => 'Windows 10',
        'windows nt 6.3'     => 'Windows 8.1',
        'windows nt 6.2'     => 'Windows 8',
        'windows nt 6.1'     => 'Windows 7',
        'windows nt 6.0'     => 'Windows Vista',
        'windows nt 5.2'     => 'Windows 2003',
        'windows nt 5.1'     => 'Windows XP',
        'windows nt 5.0'     => 'Windows 2000',
        'windows nt 4.0'     => 'Windows NT 4.0',
        'winnt4.0'           => 'Windows NT 4.0',
        'winnt 4.0'          => 'Windows NT',
        'winnt'              => 'Windows NT',
        'windows 98'         => 'Windows 98',
        'win98'              => 'Windows 98',
        'windows 95'         => 'Windows 95',
        'win95'              => 'Windows 95',
        'windows phone'      => 'Windows Phone',
        'windows'            => 'Unknown Windows OS',
        'android'            => 'Android',
        'blackberry'         => 'BlackBerry',
        'iphone'             => 'iOS',
        'ipad'               => 'iOS',
        'ipod'               => 'iOS',
        'os x'               => 'Mac OS X',
        'ppc mac'            => 'Power PC Mac',
        'freebsd'            => 'FreeBSD',
        'ppc'                => 'Macintosh',
        'linux'              => 'Linux',
        'debian'             => 'Debian',
        'sunos'              => 'Sun Solaris',
        'beos'               => 'BeOS',
        'apachebench'        => 'ApacheBench',
        'aix'                => 'AIX',
        'irix'               => 'Irix',
        'osf'                => 'DEC OSF',
        'hp-ux'              => 'HP-UX',
        'netbsd'             => 'NetBSD',
        'bsdi'               => 'BSDi',
        'openbsd'            => 'OpenBSD',
        'gnu'                => 'GNU/Linux',
        'unix'               => 'Unknown Unix OS',
        'symbian'            => 'Symbian OS',
    ];

    /**
     * The order of this array should NOT be changed. Many browsers return
     * multiple browser types so we want to identify the sub-type first.
     */
    public array $browsers = [
        'OPR'             => 'Opera',
        'Flock'           => 'Flock',
        'Edge'            => 'Spartan',
        'Edg'             => 'Edge',
        'Chrome'          => 'Chrome',
        // Opera 10+ always reports Opera/9.80 and appends Version/<real version> to the user agent string
        'Opera.*?Version' => 'Opera',
        'Opera'           => 'Opera',
        'MSIE'            => 'Internet Explorer',
        'Internet Explorer' => 'Internet Explorer',
        'Trident.* rv'    => 'Internet Explorer',
        'Shiira'          => 'Shiira',
        'Firefox'         => 'Firefox',
        'Chimera'         => 'Chimera',
        'Phoenix'         => 'Phoenix',
        'Firebird'        => 'Firebird',
        'Camino'          => 'Camino',
        'Netscape'        => 'Netscape',
        'OmniWeb'         => 'OmniWeb',
        'Safari'          => 'Safari',
        'Mozilla'         => 'Mozilla',
        'Konqueror'       => 'Konqueror',
        'icab'            => 'iCab',
        'Lynx'            => 'Lynx',
        'Links'           => 'Links',
        'hotjava'         => 'HotJava',
        'amaya'           => 'Amaya',
        'IBrowse'         => 'IBrowse',
        'Maxthon'         => 'Maxthon',
        'Ubuntu'          => 'Ubuntu Web Browser',
    ];

    /**
     * Mobiles
     */
    public array $mobiles = [
        // legacy array, old values commented out
        'mobileexplorer' => 'Mobile Explorer',
        'palmsource'     => 'Palm',
        'palmscape'      => 'Palmscape',

        // Phones and Manufacturers
        'motorola'    => 'Motorola',
        'nokia'       => 'Nokia',
        'palm'        => 'Palm',
        'iphone'      => 'Apple iPhone',
        'ipad'        => 'iPad',
        'ipod'        => 'Apple iPod Touch',
        'sony'        => 'Sony Ericsson',
        'ericsson'    => 'Sony Ericsson',
        'blackberry'  => 'BlackBerry',
        'cocoon'      => 'O2 Cocoon',
        'blazer'      => 'Treo',
        'lg'          => 'LG',
        'amoi'        => 'Amoi',
        'xda'         => 'XDA',
        'mda'         => 'MDA',
        'vario'       => 'Vario',
        'htc'         => 'HTC',
        'samsung'     => 'Samsung',
        'sharp'       => 'Sharp',
        'sie-'        => 'Siemens',
        'alcatel'     => 'Alcatel',
        'benq'        => 'BenQ',
        'ipaq'        => 'HP iPaq',
        'mot-'        => 'Motorola',
        'playstation portable' => 'PlayStation Portable',
        'hiptop'      => 'Danger Hiptop',
        'nec-'        => 'NEC',
        'panasonic'   => 'Panasonic',
        'philips'     => 'Philips',
        'sagem'       => 'Sagem',
        'sanyo'       => 'Sanyo',
        'spv'         => 'SPV',
        'zte'         => 'ZTE',
        'sendo'       => 'Sendo',

        // Operating Systems
        'symbian'     => 'Symbian',
        'SymbianOS'   => 'SymbianOS',
        'elaine'      => 'Palm',
        'palm'        => 'Palm',
        'series60'    => 'Symbian S60',
        'windows ce'  => 'Windows CE',

        // Browsers
        'obigo'          => 'Obigo',
        'netfront'       => 'Netfront Browser',
        'openwave'       => 'Openwave Browser',
        'mobilexplorer'  => 'Mobile Explorer',
        'operamini'      => 'Opera Mini',
        'opera mini'     => 'Opera Mini',

        // Other
        'digital paths' => 'Digital Paths',
        'avantgo'       => 'AvantGo',
        'xiino'         => 'Xiino',
        'novarra'       => 'Novarra Transcoder',
        'vodafone'      => 'Vodafone',
        'docomo'        => 'NTT DoCoMo',
        'o2'            => 'O2',

        // Fallback
        'mobile'  => 'Generic Mobile',
        'wireless' => 'Generic Mobile',
        'j2me'    => 'Generic Mobile',
        'midp'    => 'Generic Mobile',
        'cldc'    => 'Generic Mobile',
        'up.link' => 'Generic Mobile',
        'up.browser' => 'Generic Mobile',
        'smartphone' => 'Generic Mobile',
        'cellphone'  => 'Generic Mobile',
    ];

    /**
     * There are hundreds of bots but these are the most common.
     */
    public array $robots = [
        'googlebot'        => 'Googlebot',
        'msnbot'           => 'MSNBot',
        'baiduspider'      => 'Baiduspider',
        'bingbot'          => 'Bing',
        'slurp'            => 'Inktomi Slurp',
        'yahoo'            => 'Yahoo',
        'askjeeves'        => 'AskJeeves',
        'fastcrawler'      => 'FastCrawler',
        'infoseek'         => 'InfoSeek Robot 1.0',
        'lycos'            => 'Lycos',
        'yandex'           => 'YandexBot',
        'mediapartners-google' => 'MediaPartners Google',
        'CRAZYWEBCRAWLER'  => 'Crazy Webcrawler',
        'adsbot-google'    => 'AdsBot Google',
        'feedfetcher-google' => 'Feedfetcher Google',
        'curious george'   => 'Curious George',
        'ia_archiver'      => 'Alexa Crawler',
        'MJ12bot'          => 'Majestic-12',
        'Uptimebot'        => 'Uptimebot',
    ];
}