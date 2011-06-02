<?php
/**
 * Slideshare API for CodeIgniter
 * 
 * Adapted by Julian Magnone
 * based on PHP Library created by Aadhar Mittal
 * https://github.com/slideshare/SlideshareAPIExamples/tree/master/PHPKit/SSUtil
 * 
 * Uses RSS generation class created by Marijo Galesic
 * 
 * @package presentacion
 * @author 
 * @copyright 2011
 * @version $Id$
 * @access public
 */
class slideshare {
	
    private $key;
	private $secret;
	private $user;
	private $password;
	private $apiurl;
    
    private $_context;

    public function __construct() {
	   
        $this->_obj =& get_instance();
		$this->_obj->load->config('slideshare');

		$this->key = $this->_obj->config->item('slideshare_api_key');
		$this->secret = $this->_obj->config->item('slideshare_shared_secret');
		$this->apiurl = $this->_obj->config->item('slideshare_api_url');
        
        // prepare context for file_get_content
        $opts = array('http' => array('header' => 'Accept-Charset: UTF-8, *;q=0'));
        $this->_context = stream_context_create($opts);

	}
        
	private function XMLtoArray($data)
	{
	   $finarr = array();
//var_dump($data);
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $data, $values, $tags);
		xml_parser_free($parser);
		foreach ($tags as $key=>$val) {
			if(strtoupper($key) == "SLIDESHARESERVICEERROR") {
				$finarr[0]["Error"]="true";
				$finarr[0]["Message"]=$values[$tags["MESSAGE"][0]]["value"];
				return $finarr;
			}     
			if ((strtolower($key) != "slideshow") &&  (strtolower($key) != "slideshows") && (strtolower($key) != "slideshowdeleted") && (strtolower($key) != "slideshowuploaded") && (strtolower($key) != "tags")  && (strtolower($key) != "group") && (strtolower($key) != "name") && (strtolower($key) != "count") && (strtolower($key) != "user")) {
                for($i = 0;$i < count($val);$i++) {
                      @$finarr[$i][$key]=$values[$val[$i]]["value"];
                }
			}
			else {
				continue;
			}
		}
	   return $finarr;
	}
	private function RSStoArray($feed) {
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $feed, $values, $tags);
		xml_parser_free($parser);
		$count=1;
		foreach($tags as $key=>$val) {
			if((strtolower($key)=='title')&&(strtolower($key)=='link')&&(strtolower($key)=='pubDate')&&(strtolower($key)=='description')) {
				for($i = 1;$i < count($val);$i++) {
                      $data[$i-1][$key]=$values[$val[$i-1]]["value"];
                }
			} else if((strtolower($key)!='rss')&&(strtolower($key)!='channel')&&(strtolower($key)!='item')&&(strtolower($key)!='slideshare:meta')) {
				if(strtolower(substr($key,0,10))=="slideshare")
					$key=substr($key,11);
				for($i = 0;$i < count($val);$i++) {
                      $data[$i][$key]=$values[$val[$i]]["value"];
                }
			}
		}
		return $data;
	}
	
	private function get_data($call,$params) {
		$ts=time();
		$hash=sha1($this->secret.$ts);
		try {
			//$res=file_get_contents($this->apiurl.$call."?api_key=$this->key&ts=$ts&hash=$hash".$params, false, $this->_context);
            $res=$this->file_get_contents_utf8($this->apiurl.$call."?api_key=$this->key&ts=$ts&hash=$hash".$params);
		} catch (Exception $e) {
		// Log the exception and return $res as blank
		}
		return utf8_encode($res);
	}
    
    function file_get_contents_utf8($fn)
    {
        $content = file_get_contents($fn); 
        return mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true)); 
    }
    
    private function prepare_url($assoc_array)
    {
        $url = array_map(create_function('$key, $value', 'return $key."=".$value."";'), array_keys($assoc_array), array_values($assoc_array));
        $url = implode('&', $url);
        return $url;
    }
	/* Get all the slide information in a simple array */
    
    /**
     * slideshare::search_slideshows()
     * 
     * Search slideshows based on params. Refer to API to learn more about params
     * 
     * Example 'language'=>'es' to get slides in Spanish language
     * 
     * @return
     */
    public function search_slideshows($params)
    {
        $strparams = $this->prepare_url($params);
        //var_dump($strparams);
		$data=$this->XMLtoArray($this->get_data("search_slideshows","&".$strparams));
		return $data;
	}
    
    /**
     * slideshare::get_slideshow()
     * 
     * Get the basic slideshow information
     * 
     * @return
     */
    public function get_slideshow($id, $exclude_tags = TRUE)
    {
        $exclude_tags = ($exclude_tags?1:0);
        $data=$this->XMLtoArray($this->get_data("get_slideshow","&slideshow_id=$id&exclude_tags={$exclude_tags}"));
		return $data[0];
    }
    
    /**
     * slideshare::get_slideshow_detailed()
     * 
     * Get the slideshow with detailed information 
     * 
     * @return
     */
    public function get_slideshow_detailed($id, $exclude_tags = TRUE)
    {
        $exclude_tags = ($exclude_tags?1:0);
        $data=$this->XMLtoArray($this->get_data("get_slideshow","&slideshow_id=$id&detailed=1&exclude_tags=1"));
		return $data[0];
    }
    
    public function get_slideshows_by_username($user,$offset=0,$limit=0) {
		return $this->XMLtoArray($this->get_data("get_slideshows_by_user","&username_for=$user&offset=$offset&limit=$limit"));
	}
    
    
    /** Original functions - Some of these weren't working '**/
    
	public function get_slideInfo($id) {
		$data=$this->XMLtoArray($this->get_data("get_slideshow","&slideshow_id=$id"));
		return $data[0];
	}
	public function count_slideUser($user) {
		$xml=new SimpleXMLElement($this->get_data("get_slideshow_by_user","&username_for=$user&offset=0&limit=1"));
		return $xml->count;
	}
	/* Get all the user's slide information  in a simple multi-dimensional array */
	public function get_slideUser($user,$offset=0,$limit=0) {
		return $this->XMLtoArray($this->get_data("get_slideshows_by_user","&username_for=$user&offset=$offset&limit=$limit"));
	}
	public function count_slideTag($tag) {
		$xml=new SimpleXMLElement($this->get_data("get_slideshows_by_tag","&tag=$tag&offset=0&limit=1"));
		return $xml->count;
	}
	/* Get all the tags's slide information  in a simple multi-dimensional array */
	public function get_slideTag($tag,$offset=0,$limit=0) {
		return $this->XMLtoArray($this->get_data("get_slideshows_by_tag","&tag=$tag&offset=$offset&limit=$limit"));
	}
	public function count_slideGroup($group) {
		$xml=new SimpleXMLElement($this->get_data("get_slideshow_from_group","&group_name=$group&offset=0&limit=1"));
		return $xml->count;
	}
	/* Get all the group's slide information  in a simple multi-dimensional array */
	public function get_slideGroup($group,$offset=0,$limit=0) {
		return $this->XMLtoArray($this->get_data("get_slideshow_from_group","&group_name=$group&offset=$offset&limit=$limit"));
	}
	/* pull any slideshare feed and retrieve that in  a multi-dimensional array */
	public function get_RSS($feed) {
		try {
			$res=file_get_contents($feed);
		} catch (Exception $e) {
		// Log the exception and return $res as blank
		}
		$feedxml=utf8_encode($res);
		return $this->RSStoArray($feedxml);
	}
	/* Generate your own slideshow RSS enter a multi-dimensional slide */
	public function make_RSS($title,$description,$date,$slides,$location='.',$filename='rss') {
		$rss = new slideshare_rss('utf-8');
		$rss->channel($title, 'http://www.slideshare.net', $description);
		$rss->language('en-us');
		$rss->copyright('Copyright by SlideShare 2006');
		$rss->managingEditor('support.slideshare@gmail.com');
		$rss->startRSS($location,$filename);

		for($i = 0; $i < count($slides); $i++){
			$rss->itemTitle($slides[$i]['TITLE']);
			$rss->itemLink($slides[$i]['PERMALINK']);
			$rss->itemDescription(
			'<![CDATA[
				<img style="border: 1px solid rgb(195, 230, 216);" src="'.$slides[$i]['THUMBNAIL'].'" align="right" border="0" width="120" height="90" vspace="4" hspace="4" />
				<p>
				'.$slides[$i]['DESCRIPTION'].'
				</p>
			]]>'
			);
			$rss->itemGuid($slides[$i]['PERMALINK'],true);
			$rss->itemComments($slides[$i]['PERMALINK']);
			$rss->itemSource('Slideshare', 'http://www.slideshare.net');
			$rss->addItem();
		}
		$rss->RSSdone();
	}
}





/***************************************************************************
 *                         RSS 2.0 generation class
 *                         ------------------------
 *
 *   copyright            : (C) 2006 Marijo Galesic
 *   email                : mgalesic@gmail.com
 *
 *   Id: class.rss.php, v 1.1 2006/08/25
 *
 *   www.starmont.net
 *
 * Redistribution and use in source and binary forms,
 * with or without modification must retain the above copyright notice
 *
 ***************************************************************************/

class slideshare_rss {

    var $rss;
    var $encoding;

    var $title;
    var $link;
    var $description;
    var $language;
    var $copyright;
    var $managingEditor;
    var $webMaster;
    var $pubDate;
    var $lastBuildDate;
    var $category;
    var $generator;
    var $docs;
    var $cloud;
    var $ttl;
    var $image;
    var $textinput;
    var $skipHours = array();
    var $skipDays = array();

    var $itemTitle;
    var $itemLink;
    var $itemDescription;
    var $itemAuthor;
    var $itemCategory;
    var $itemComments;
    var $itemEnclosure;
    var $itemGuid;
    var $itemPubDate;
    var $itemSource;

    var $path;
    var $filename;

    function slideshare_rss($encoding = ''){
        $this->generator = 'SlideShare API library';
        $this->docs = 'http://developer.slideshare.net';
        if(!empty($encoding)){ $this->encoding = $encoding; }
    }

    function channel($title, $link, $description){
        $this->title = $title;
        $this->link = $link;
        $this->description = $description;
    }

    function language($language){ $this->language = $language; }

    function copyright($copyright){ $this->copyright = $copyright; }

    function managingEditor($managingEditor){ $this->managingEditor = $managingEditor; }

    function webMaster($webMaster){ $this->webMaster = $webMaster; }

    function pubDate($pubDate){ $this->pubDate = $pubDate; }

    function lastBuildDate($lastBuildDate){ $this->lastBuildDate = $lastBuildDate; }

    function category($category, $domain = ''){
        $this->category .= $this->s(2) . '<category';
        if(!empty($domain)){ $this->category .= ' domain="' . $domain . '"'; }
        $this->category .= '>' . $category . '</category>' . "\n";
    }

    function cloud($domain, $port, $path, $registerProcedure, $protocol){
        $this->cloud .= $this->s(2) . '<cloud domain="' . $domain . '" port="' . $port . '" registerProcedure="' . $registerProcedure . '" protocol="' . $protocol . '" />';
    }

    function ttl($ttl){ $this->ttl = $ttl; }

    function image($url, $title, $link, $width = '', $height = '', $description = ''){
        $this->image = $this->s(2) . '<image>' . "\n";
        $this->image .= $this->s(3) . '<url>' . $url . '</url>' . "\n";
        $this->image .= $this->s(3) . '<title>' . $title . '</title>' . "\n";
        $this->image .= $this->s(3) . '<link>' . $link . '</link>' . "\n";
        if($width != ''){ $this->s(3) . '<width>' . $width . '</width>' . "\n"; }
        if($height != ''){ $this->s(3) . '<height>' . $height . '</height>' . "\n"; }
        if($description != ''){ $this->s(3) . '<description>' . $description . '</description>' . "\n"; }
        $this->image .= $this->s(2) . '</image>' . "\n";
    }

    function textInput($title, $description, $name, $link){
        $this->textInput = $this->s(2) . '<textInput>' . "\n";
        $this->textInput .= $this->s(3) . '<title>' . $title . '</title>' . "\n";
        $this->textInput .= $this->s(3) . '<description>' . $description . '</description>' . "\n";
        $this->textInput .= $this->s(3) . '<name>' . $name . '</name>' . "\n";
        $this->textInput .= $this->s(3) . '<link>' . $link . '</link>' . "\n";
        $this->textInput .= $this->s(2) . '</textInput>' . "\n";
    }

    function skipHours(){
        $this->skipHours = array();
        $args = func_get_args();
        $this->skipHours = array_values($args);
    }

    function skipDays(){
        $this->skipDays = array();
        $args = func_get_args();
        $this->skipDays = array_values($args);
    }

    function startRSS($path = '.', $filename = 'rss'){
        $this->path = $path;
        $this->filename = $filename;
        $this->rss = '<?xml version="1.0"';
        if(!empty($this->encoding)){ $this->rss .= ' encoding="' . $this->encoding . '"'; }
        $this->rss .= '?>' . "\n";
        $this->rss .= '<rss version="2.0">' . "\n";
        $this->rss .= $this->s(1) . '<channel>' . "\n";
        $this->rss .= $this->s(2) . '<title>' . $this->title . '</title>' . "\n";
        $this->rss .= $this->s(2) . '<link>' . $this->link . '</link>' . "\n";
        $this->rss .= $this->s(2) . '<description>' . $this->description . '</description>' . "\n";
        if(!empty($this->language)){ $this->rss .= $this->s(2) . '<language>' . $this->language . '</language>' . "\n"; }
        if(!empty($this->copyright)){ $this->rss .= $this->s(2) . '<copyright>' . $this->copyright . '</copyright>' . "\n"; }
        if(!empty($this->managingEditor)){ $this->rss .= $this->s(2) . '<managingEditor>' . $this->managingEditor . '</managingEditor>' . "\n"; }
        if(!empty($this->webMaster)){ $this->rss .= $this->s(2) . '<webMaster>' . $this->webMaster . '</webMaster>' . "\n"; }
        if(!empty($this->pubDate)){ $this->rss .= $this->s(2) . '<pubDate>' . $this->pubDate . '</pubDate>' . "\n"; }
        if(!empty($this->lastBuildDate)){ $this->rss .= $this->s(2) . '<lastBuildDate>' . $this->lastBuildDate . '</lastBuildDate>' . "\n"; }
        if(!empty($this->category)){ $this->rss .= $this->category; }
        $this->rss .= $this->s(2) . '<generator>' . $this->generator . '</generator>' . "\n";
        $this->rss .= $this->s(2) . '<docs>' . $this->docs . '</docs>' . "\n";
        if(!empty($this->cloud)){ $this->rss .= $this->cloud; }
        if(!empty($this->ttl)){ $this->rss .= $this->s(2) . '<ttl>' . $this->ttl . '</ttl>' . "\n"; }
        if(!empty($this->image)){ $this->rss .= $this->image; }
        if(!empty($this->textInput)){ $this->rss .= $this->textInput; }
        if(count($this->skipHours) > 0){
            $this->rss .= $this->s(2) . '<skipHours>' . "\n";
            for($i = 0; $i < count($this->skipHours); $i++){
                $this->rss .= $this->s(3) . '<hour>' . $this->skipHours[$i] . '</hour>' . "\n";
            }
            $this->rss .= $this->s(2) . '</skipHours>' . "\n";
        }
        if(count($this->skipDays) > 0){
            $this->rss .= $this->s(2) . '<skipDays>' . "\n";
            for($i = 0; $i < count($this->skipDays); $i++){
                $this->rss .= $this->s(3) . '<day>' . $this->skipHours[$i] . '</day>' . "\n";
            }
            $this->rss .= $this->s(2) . '</skipDays>' . "\n";
        }
    }

    function itemTitle($title){ $this->itemTitle = $title; }

    function itemLink($link){ $this->itemLink = $link; }

    function itemDescription($description){ $this->itemDescription = $description; }

    function itemAuthor($author){ $this->itemAuthor = $author; }

    function itemCategory($category, $domain = ''){
        $this->itemCategory .= $this->s(3) . '<category';
        if(!empty($domain)){ $this->itemCategory .= ' domain="' . $domain . '"'; }
        $this->itemCategory .= '>' . $category . '</category>' . "\n";
    }

    function itemComments($comments){ $this->itemComments = $comments; }

    function itemEnclosure($enclosure){ $this->itemEnclosure = $enclosure; }

    function itemGuid($guid, $isPermaLink = ''){
        $this->itemGuid = $this->s(3) . '<guid';
        if(!empty($isPermaLink)){ $this->itemGuid .= ' isPermaLink="' . $isPermaLink . '"'; }
        $this->itemGuid .= '>' . $guid . '</guid>' . "\n";
    }

    function itemPubDate($pubDate){ $this->itemPubDate = $pubDate; }

    function itemSource($source, $url){
        $this->itemSource = $this->s(3) . '<source url="' . $url . '">' . $source . '</source>' . "\n";
    }

    function addItem(){
        $this->rss .= $this->s(2) . '<item>' . "\n";
        if(!empty($this->itemTitle)){ $this->rss .= $this->s(3) . '<title>' . $this->itemTitle . '</title>' . "\n"; }
        if(!empty($this->itemLink)){ $this->rss .= $this->s(3) . '<link>' . $this->itemLink . '</link>' . "\n"; }
        if(!empty($this->itemDescription)){ $this->rss .= $this->s(3) . '<description>' . $this->itemDescription . '</description>' . "\n"; }
        if(!empty($this->itemAuthor)){ $this->rss .= $this->s(3) . '<author>' . $this->itemAuthor . '</author>' . "\n"; }
        if(!empty($this->itemCategory)){ $this->rss .= $this->itemCategory; }
        if(!empty($this->itemComments)){ $this->rss .= $this->s(3) . '<comments>' . $this->itemComments . '</comments>' . "\n"; }
        if(!empty($this->itemEnclosure)){ $this->rss .= $this->s(3) . '<enclosure>' . $this->itemEnclosure . '</enclosure>' . "\n"; }
        if(!empty($this->itemGuid)){ $this->rss .= $this->itemGuid; }
        if(!empty($this->itemPubDate)){ $this->rss .= $this->s(3) . '<pubDate>' . $this->itemPubDate . '</pubDate>' . "\n"; }
        if(!empty($this->itemSource)){ $this->rss .= $this->itemSource; }
        $this->rss .= $this->s(2) . '</item>' . "\n";

        $this->itemTitle = '';
        $this->itemLink = '';
        $this->itemDescription = '';
        $this->itemAuthor = '';
        $this->itemCategory = '';
        $this->itemComments = '';
        $this->itemEnclosure = '';
        $this->itemGuid = '';
        $this->itemPubDate = '';
        $this->itemSource = '';
    }

    function RSSdone(){
        $this->rss .= $this->s(1) . '</channel>' . "\n";
        $this->rss .= '</rss>';

        $handle = fopen($this->path . '/'. $this->filename . '.xml', "w");
        fwrite($handle, $this->rss);
        fclose($handle);
    }

    function clearRSS(){
        $this->title = '';
        $this->link = '';
        $this->description = '';
        $this->language = '';
        $this->copyright = '';
        $this->managingEditor = '';
        $this->webMaster = '';
        $this->pubDate = '';
        $this->lastBuildDate = '';
        $this->category = '';
        $this->cloud = '';
        $this->ttl = '';
        $this->skipHours = array();
        $this->skipDays = array();
    }

    function s($space){
        $s = '';
        for($i = 0; $i < $space; $i++){ $s .= '   '; }
        return $s;
    }

}

