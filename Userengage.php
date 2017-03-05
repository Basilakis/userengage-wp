<?php
#####################################################################
#                                                                   #
#  This library allows you to communicate with                      #
#  the UserEnageg public API using PHP.                             #
#                                                                   #
#  Documentation for the API is online here:                        #
#  https://userengage.io/en-gb/api/introduction/                    #
#                                                                   #
#####################################################################
#                                                                   #
#  Works with PHP >= 5.3                                            #
#                                                                   #
#  @author    Ben Major <ben.major88@gmail.com>                     #
#  @copyright 2017 WeddingVenues.com                                #
#  @license   https://opensource.org/licenses/MIT The MIT License   #
#  @version   GIT: <git_id>                                         #
#  @link      https://userengage.io/en-gb/api/introduction          #
#                                                                   #
#####################################################################

    class UserEngage
    {
        public $endpoint;
        public $method;
        public $fields;
        
        private $_apiKey;
        private static $_apiURL = 'https://app.userengage.io/api/public/';
        
        function __construct( $key = null )
        {
            $this->method = 'POST';
            $this->fields = null;
            
            if( $key != null )
            {
                $this->_apiKey = trim( $key );
            }
        }
        
        public function setKey( $key )
        {
            if( empty($key) )
            {
                throw new Exception( 'API Key cannot be empty.' );
            }
            
            $this->_apiKey = trim($key);
        }
        
        public function setEndpoint( $endpoint )
        {
            // Trim the endpoint and force a clean version:
            $this->endpoint = trim($endpoint, '/');
            
            if( !strstr($endpoint, '?') )
            {
                $this->endpoint.= '/';
            }
            
            return $this->endpoint;
        }
        
        public function setMethod( $method = 'POST' )
        {
            $this->method = strtoupper( trim($method) );
            return $this->method;
        }
        
        public function addField( $name, $value )
        {
            if( is_null($this->fields) )
            {
                $this->fields = new stdClass();
            }
            
            $this->fields->{$name} = $value;
            
            return $this->fields;
        }
        
        public function debug( $header = true )
        {
            // Make sure that the headers haven't already been sent to the browser:
            if( $header && !headers_sent() )
            {
                header('Content-Type: application/json');
            }
            
            echo json_encode($this->fields);
        }
        
        public function send( $decode = true )
        {
            // Make sure we have their API key:
            if( $this->_apiKey == null || empty($this->_apiKey) )
            {
                throw new Exception( 'No API Key specified. Use the setKey() method before sending the request.' );
            }
            
            // Make sure we have everything:
            if( empty($this->method) )
            {
                throw new Exception( 'HTTP method was not specified. Please use POST, GET, DELETE or UPDATE.' );
            }
            
            if( empty($this->endpoint) )
            {
                throw new Exception( 'No endpoint was specified. Please specify an API endpoint before sending a request.' );
            }
            
            $curl = curl_init();
            $url  = self::$_apiURL.ltrim($this->endpoint, '/');
    
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $this->method,
                CURLOPT_POSTFIELDS => ( (is_null($this->fields)) ? '' : json_encode($this->fields) ),
                CURLOPT_HTTPHEADER => array(
                    "authorization: Token ".$this->_apiKey,
                    "content-type: application/json"
                ),
            ));
            
            $response = curl_exec($curl);
            $err      = curl_error($curl);
            
            curl_close($curl);
            
            if( $err )
            {
                throw new Exception( 'There was a problem sending the request to '.$url.': <b>'.$err.'</b>' );
            }
            else
            {
                return ($decode) ? json_decode($response) : $response;
            }
        }
    }
