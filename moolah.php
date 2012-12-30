<?php

/**
 * @Package Plugin Moolah E-Commerce Loader
 * @Author Moolah E-Commerce
 * @Copyright (C) 2012 Moolah E-Commerce
 * @license GNU/GPLv2
 **/

// No direct access.
defined('_JEXEC') or die;

class plgContentMoolah extends JPlugin
{
    protected   $shouldAddheader    = false;

	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
    	// simple performance check to determine whether bot should process further
    	if ( strpos( $article->text, 'moolah' ) === false ) {
    		return true;
    	}

        $regex = "#{moolah(.+)?}#s"; //(.*?)
    	
    	// Get plugin info
    	$plugin =& JPluginHelper::getPlugin('content', 'moolah');
    	$pluginParams = new JRegistry( $plugin->params );

    	// check whether plugin has been unpublished
    	if ( !$pluginParams->get( 'enabled', 1 ) ) {
    		$article->text = preg_replace( $regex, '', $article->text );
    		return true;
    	}
    	
    	// find all instances of plugin and put in $matches

    	preg_match_all( $regex, $article->text, $matches );

    	// Number of plugins
     	$count = count( $matches[0] );

     	// plugin only processes if there are any instances of the plugin in the text
     	if ( ! $count ) {
    		$article->text = preg_replace( '#{moolah.*?}#', '<!-- No Moolah Code Detected -->', $article->text );
    		return true;
    	}

     	return $this->plgContentProcessMoolahMatches( $article, $matches, $pluginParams );

	}

    public function onBeforeRender()
    {
        if ( $this->shouldAddHeader ) {
            $this->addHeader();
        }
    }
	
	protected function addHeader()
	{
        $params     = $this->params;
        $doc		= JFactory::getDocument();
        $uri        = JFactory::getUri();
        $head		=& $doc->getHeadData();
        $scripts	=& $head['scripts'];
        $debug		= $params->get('TESTING',true) ? '-debug' : '';
        $ssl        = $uri->isSSL();
        $proto      = $ssl ? 'https' : 'http';

        $local		= in_array($_SERVER['HTTP_HOST'], array('mec', 'mec-demo') );

        if ( $local ) {
            $site   = $debug ? 'mec-test' : 'mec-store';
            $cdn    = $site;
        } else {
            $site   = $debug ? 'test.moolah-ecommerce.com' : 'store.moolah-ecommerce.com';
            $cdn    = $ssl  ? '155505a11bc78ed47306-32388414bd35ec9b874e476acd7f793d.ssl.cf2.rackcdn.com'
                            : '38c04c6c581cc52efe28-32388414bd35ec9b874e476acd7f793d.r45.cf2.rackcdn.com';
        }

        $storeId	= $params->get('STORE_ID');
        $productId	= $params->get('PRODUCT_ID');
        $categoryId	= $params->get('CATEGORY_ID');
        $siteId     = $params->get('SITE_ID');
        $affiliateId= $params->get('AFFILIATE_ID');
        $divId		= $params->get('DIV_ID','moolah');
        $version	= $params->get('VERSION');
        $extjs		= $params->get('EXTJS_JS_LOCATION',"$proto://$cdn/extjs/411a/");
        $moolah		= $params->get('MOOLAH_JS_LOCATION',"$proto://$site/$storeId/js/");

		$args		= "?target=$divId&store=$storeId&category=$categoryId&product=$productId";
		
		if ( $version )		$args .= "&ver=$version";
        if ( $siteId )      $args .= "&site=$siteId";
        if ( $affiliateId)  $args .= "&affiliate=$affiliateId";

        if ( true ) {
            $doc->addScript( $moolah . 'load.js' . $args );
        } else {

            //echo "category is $categoryId, product is $productId, store is $storeId<br/>";
            // It helps if the ExtJS script is the first one in
            $tmp = $extjs . "ext-all$debug.js";
            $doc->setHeadData(
                array(
                    'scripts' => array(
                        $tmp => array(
                            'mime' => 'text/javascript',
                            'defer' => false,
                            'async' => false
                            )
                        )
                    )
                );

            // Add our Individual Scripts
            foreach ( array('order.js','init.js') as $script )
            {
                $doc->addScript( $moolah . $script . $args );
            }

            $doc->addStyleSheet( $extjs . 'themes/css/default.css' );
            $doc->addStyleSheet( $extjs . 'style/order.css' );

            // Now add in again the scripts that we wiped
            foreach($scripts as $s => $a)
            {
                $doc->addScript($s, $a['mime'], $a['defer'], $a['async']);
            }
        }

	}
	
	public function plgContentProcessMoolahMatches( &$row, &$matches, $params )
	{
		$parts = preg_split('#\s#', trim($matches[1][0]) );
		foreach ( $parts as $part )
		{
			if ( strpos($part,'='))
			{
				list($k,$v) = explode('=',$part);
				$k = strtoupper(trim($k)).'_ID';
				$v = trim($v);
				$params->set($k,$v);
			}
		}
		
		$divId		= $params->get('DIV_ID','moolah');
		$text		= '<div id="'.$divId.'">Moolah Store</div>';

        $this->shouldAddHeader = true;

		$this->params = $params;
		
		$row->text = str_replace( $matches[0][0], $text, $row->text );
		
        return true;
	}
}
