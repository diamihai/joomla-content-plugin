<?php

/**
 * @Package Plugin Moolah E-Commerce Loader
 * @Author Moolah E-Commerce
 * @Copyright (C) 2012-2013 Moolah E-Commerce
 * @license GNU/GPLv2
 **/

// No direct access.
defined('_JEXEC') or die;

class plgContentMoolah extends JPlugin
{
    protected   $shouldAddheader    = false;

    /**
     * @param $context
     * @param $article
     * @param $params
     * @param int $page
     * @return bool
     */
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

    /**
     * Add a header to the response
     */
    public function onBeforeRender()
    {
        if ( $this->shouldAddHeader ) {
            $this->addHeader();
        }
    }

    /**
     * Add a header to the response
     */
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
        } else {
            $site   = $debug ? 'test.moolah-ecommerce.com' : 'store.moolah-ecommerce.com';
        }

        $storeId	= $params->get('STORE_ID');
        $productId	= $params->get('PRODUCT_ID');
        $categoryId	= $params->get('CATEGORY_ID');
        $siteId     = $params->get('SITE_ID');
        $affiliateId= $params->get('AFFILIATE_ID');
        $divId		= $params->get('DIV_ID','moolah');
        $version	= $params->get('VERSION');
        $moolah		= $params->get('MOOLAH_JS_LOCATION',"$proto://$site/$storeId/js/");

		$args		= "?target=$divId&store=$storeId&category=$categoryId&product=$productId";
		
		if ( $version )		$args .= "&ver=$version";
        if ( $siteId )      $args .= "&site=$siteId";
        if ( $affiliateId)  $args .= "&affiliate=$affiliateId";

        $doc->addScript( $moolah . 'load.js' . $args );

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
