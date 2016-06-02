<?php
/**
* Given a username returns latest feed
*/
class Instagram 
{
    private var $access_token;

    private var $instagram_id;

    private var $username;

    private var $transient_prefix;

    private var $count;

    function __construct()
    {
        
        $this->$access_token = 'XXXXYYYYYZZZZ11122233445566AAABBCCCDDD'; // Get yours, ie: http://instagram.pixelunion.net/
        
        $this->api_url = 'https://api.instagram.com/v1/users/';

        $this->username = 'kimkardashian';

        $this->$transient_prefix = 'instagram';

        $this->private_var = 12;
    }

    /* Get instagram User ID */
    public function get_instagram_id( $username = false ) {
        
        if($username){
            $this->username = $username;
        }

        $this->username = str_replace('@', '', $this->username);
        
        $this->instagram_id = false;
        
        $url =  $this->api_url.'users/search?q=' . $this->username . '&access_token=' . $this->access_token;
        
        $get = wp_remote_request($url);
        
        if (is_array($get) && isset($get['body'])) {
            $json = json_decode($get['body']);
            if (isset($json) && isset($json->data)) {
                foreach ($json->data as $user) {
                    if ($user->username == $this->username) {
                        $this->instagram_id = $user->id;
                    }
                }
            }
        }
    }


    public function get_instagram($username = false ) {
    
        if($username){
            $this->username = $username;
        }

        
        $trans_name = $this->transient_prefix . '_feed_' . $this->username;
        
        if (false === ($instagram = unserialize(base64_decode(get_transient($trans_name))))) {
            
            $instagram = $this->fetch_instagram();

        } elseif (isset($instagram['expiry']) && $instagram['expiry'] < time()) {
            
            $instagram['expiry'] = (time() + (2 * HOUR_IN_SECONDS));
            
            set_transient($trans_name, base64_encode(serialize($instagram)), 24 * HOUR_IN_SECONDS);
           
        }
        return $instagram;
    }


    private function fetch_instagram() {
        
        $instagram = array();
        $content = array();
        $trans_name = $this->transient_prefix . '_feed_' . $this->username;
      
        
        //now get feed
        if ($this->instagram_id) {
            
            $url = $this->api_url . $this->instagram_id . '/media/recent/?count=' . $count . '&access_token=' . $this->access_token;
            
            $obj = wp_remote_request($url);
            
            if (isset($obj['body'])) {
                $obj = json_decode($obj['body']);
                if (isset($obj)) {
                    foreach ($obj->data as $key => $value) {
                        $content[$key]['username'] = $value->user->username;
                        if (isset($value->caption) && $value->caption->text != '') $content[$key]['text'] = remove_emoji($value->caption->text);
                        
                        $content[$key]['image'] = $value->images;
                        $content[$key]['link'] = $value->link;
                    }
                }
            }
            
            $instagram['expiry'] = (time() + (2 * HOUR_IN_SECONDS));
            $instagram['body'] = $content;
            
            set_transient($trans_name, base64_encode(serialize($instagram)), 24 * HOUR_IN_SECONDS);
             // This is the soft expire
            
        }
        
        return $instagram;
    }

    private function remove_emoji($text) {
    
        $clean_text = "";
            
        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $text);
        
        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);
        
        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);
        
        return $clean_text;
    }

}

