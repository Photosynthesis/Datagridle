<?php
/*
    PhotoSynthesis URL Management Class
    http://www.photosynth.ca/code/url
    
    This class allows a streamlined way of managing target URLs in web
    applications. It parses the URL of the current page and breaks it down
    into its constituent parts. These parts can then be individually and
    reliably manipulated, and the url can be output and used in intra-application
    links, preserving required GET string variables, and adding additional GET
    values as needed.
    
    This class is released under the GPL license. If you would like to use it
    in proprietary applications, versions under more permissive licenses can
    be provided. Write to adam@photosynth.ca.
    
    Suggestions and feedback are welcome.
    
    Copyright (C) 2013  A. McKenty

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

*/

class url{

  var $protocol;
  var $domain_parts = array();
  var $path_parts = array();
  var $file;
  var $query_sep = '?';
  var $query_parts = array();

  function url($url = NULL){
    if($url){
      $this->parse($url);
    }else{
      $this->detect();
    }
  }

  function get($append = NULL){
    $query_parts = $this->query_parts;
    
    if(is_array($append)){
      foreach ($append as $key=>$value) {
        if($value !== NULL && $value !== ''){
          $query_parts[$key] = $value;
        }else{
          unset($query_parts[$key]);
        }
      }
    }

    
    if(count($query_parts) > 0){
      $query_str = $this->query_sep;
      $between = '';
      foreach ($query_parts as $key=>$value) {
      	$query_str .= $between.$key.'='.$value;
      	$between = '&';
      }
      $append_with = '&';
    }else{
      $append_with = $this->query_sep;
    }
    
    if($append && !is_array($append)){
      $query_str .= $append_with.$append;
    }
  
    $out  = $this->protocol.join($this->domain_parts,'.');

    if(count($this->path_parts) > 0){
      $out .=  '/'.join($this->path_parts,'/');
    }

    if($this->file || $query_str){
      $out .=  '/'.$this->file;
    }

    $out .= $query_str;
    
    return $out;
  }

  function set_protocol($p){
    $this->protocol = $p;
  }
  function protocol(){
    return $this->protocol;
  }
  
  function set_query_pair($key,$value){
    $this->query_parts[$key] = $value;
  }
  function delete_query_pair($key){
    unset($this->query_parts[$key]);
  }
  function query_pair($key){
    return $this->query_parts[$key];
  }
  
  function parse($url){
     $query_chunks = array();
     
     if(strpos($url,'?') !== false || strpos($url,'&') !== false){
       strpos($url,'?') ? $this->query_sep = '?' : $this->query_sep = '&';
       $query = substr(strrchr($url, $this->query_sep), 1);
       $query_chunks = explode('&',$query);
     }
     
     foreach ($query_chunks as $key=>$value) {
    	 $chunk_pair = explode('=',$value);
    	 $this->query_parts[$chunk_pair[0]] = $chunk_pair[1];
     }
     
     if(strpos($url,'://') !== false){
        $parts = explode('://',$url);
        $this->protocol = $parts[0].'://';
        $url = $parts[1];
     }
     
     if(strpos($url,'/') !== false){
       $parts = explode('/',$url);
       $domain = array_shift($parts);
       $file_and_query = array_pop($parts);

       if(count($parts) > 0){
          $this->path_parts = $parts;
       }
       
     }else{
       $domain = $url;
     }
     
     $this->domain_parts = explode('.',$domain);
     
     if(strpos($file_and_query,$this->query_sep) !== false){
       $qs_pos = strpos($file_and_query,$this->query_sep);
       $this->file = substr($file_and_query,0,$qs_pos);
     }else{
       $this->file = $file_and_query;
     }
  }
  
  function detect(){
    $uri = $_SERVER['REQUEST_URI'];
    
    $_SERVER['HTTPS'] == 'on' ? $prot = 'https://' :  $prot = 'http://';
    
    $this->parse($prot.$_SERVER['HTTP_HOST'].$uri);
  }
  
  function path($leading_slash = false, $trailing_slash = false){
    if(count($this->path_parts > 0)){
      if($leading_slash) $out = '/';
      $out .= join($this->path_parts,'/');
      $out .= $trailing_slash ? '/' : '';
      return $out;
    }
  }
  
  function relative_url_no_query(){
    $p = $this->path();

    $f = $this->file;
    
    if($f && $p){
      $out = $p.'/'.$f;
    }else{
      $out = $p.$f;
    }
    
    return $out;
  }

  function relative_url(){
    $rurlnoq = $this->relative_url_no_query();
    
    $q = $this->query_str();

    return $rurlnoq.$q;
  }
  
  function url_no_query(){
  
    $rurl = $this->relative_url_no_query();

    $out = $this->long_domain();
    
    if($rurl){
      $out .= '/'.$rurl;
    }
    
    return $out;
  }
  
  function full_domain(){
    return join($this->domain_parts,'.');
  }
  
  function long_domain(){
    return $this->protocol.$this->full_domain();
  }
  
  function file(){
    return $this->file;
  }
  
  function query_str(){
    if(count($this->query_parts) > 0){
      $query_str = $this->query_sep;
      $between = '';
      foreach ($this->query_parts as $key=>$value) {
      	$query_str .= $between.$key.'='.$value;
      	$between = '&';
      }
      return $query_str;
    }
  }
  
  function define_constants(){
    define(URL,$this->get());
    define(URL_NQ,$this->url_no_query());
    define(URL_PATH,$this->path());
    define(URL_FILE,$this->file());
    define(URL_Q,$this->query_str());
    define(URL_R,$this->relative_url());
    define(URL_RNQ,$this->relative_url_no_query());
    define(URL_DM,$this->full_domain());
    define(URL_LDM,$this->long_domain());
  }
  
  function get_hidden_inputs(){
    return url::array_to_hidden_inputs($this->query_parts);
  }
  
  static function array_to_hidden_inputs($a){
    if(is_array($a)){
      foreach ($a as $key=>$value) {
      	$out .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>
        ';
      }
    }
    return $out;
  }
  
}
?>
