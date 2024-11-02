<?php
/*
Plugin Name: BlogFollow
Version: 1.1
Plugin URI: http://www.pseudocoder.com/blogfollow-show-a-snippet-from-a-commenters-blog-in-the-comment/
Description: This plugin shows an snippet from the commenters blog when they comment on your blog.  It is a way or rewarding people for participating in your blog.
Author: Matt Curry
Author URI: http://www.pseudocoder.com
*/

class blogfollow {
    var $db;
    var $comments;
    var $loaded;
    var $table = "blogfollow";

    function blogfollow() {
      global $wpdb;

      $this->db = $wpdb;
      $this->table = $this->db->prefix . "blogfollow";
      $this->comments = array();
      $this->loaded = array();

      add_action('activate_blogfollow/blogfollow.php', array(&$this, 'install'));
      add_action('deactivate_blogfollow/blogfollow.php', array(&$this, 'uninstall'));
      add_filter('comment_text', array(&$this, 'add_latest'), 8);
    }

    function install() {
      $result = $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->table}` (
                                 `id` int(10) unsigned NOT NULL auto_increment,
                                 `site` varchar(255) NOT NULL default '',
                                 `feed` varchar(255 )NOT NULL default '',
                                 `title` varchar(255) NOT NULL default '',
                                 `link` varchar(255) NOT NULL default '',
                                 `description` text NOT NULL,
                                 `pubdate` datetime,
                                 `updated` datetime NOT NULL default '0000-00-00 00:00:00',
                                 PRIMARY KEY  (`id`),
                                 UNIQUE KEY `site_idx` (`site`)
                                 )");
    }

    function uninstall() {
      $result = $this->db->query("DROP TABLE IF EXISTS `{$this->table}`");
    }

    function add_latest($text='') {
      global $post, $comment;

      $comment_table = $this->db->prefix . "comments";

      //load all the entries on the first comment
      if (empty($this->comments) && $post->ID) {
        $result = $this->db->get_results("SELECT * FROM  `{$comment_table}` AS c
                                         LEFT JOIN `{$this->table}` AS cf ON cf.site = c.comment_author_url
                                         WHERE c.comment_post_ID = {$post->ID}
                                         AND c.comment_approved = '1'", ARRAY_A);

        foreach($result as $row) {
          $this->comments[$row['comment_ID']] = $row;
        }
      }


      //easier then typing $this->comments[$comment->comment_ID] everytime
      $me = &$this->comments[$comment->comment_ID];

      //don't show for pingbacks
      if ($me['comment_type'] == 'pingback') {
        return $text;
      }

      $url = @parse_url($me['comment_author_url']);

      //check if the site was loaded in a previous comment
      if (isset($this->loaded[$me['comment_author_url']])) {
        $me['site'] = $this->comments[$this->loaded[$me['comment_author_url']]]['site'];
        $me['feed'] = $this->comments[$this->loaded[$me['comment_author_url']]]['feed'];
        $me['title'] = $this->comments[$this->loaded[$me['comment_author_url']]]['title'];
        $me['link'] = $this->comments[$this->loaded[$me['comment_author_url']]]['link'];
        $me['pubdate'] = $this->comments[$this->loaded[$me['comment_author_url']]]['pubdate'];
        $me['description'] = $this->comments[$this->loaded[$me['comment_author_url']]]['description'];
        $me['updated'] = $this->comments[$this->loaded[$me['comment_author_url']]]['updated'];
      }

      //first time
      if ($me['comment_author_url'] && !$me['site'] && $url['host']) {
        $site = $this->remote_fopen($me['comment_author_url']);

        //find the auto discovery feed
        $pattern = '/application\/(rss\+xml|atom\+xml|rdf\+xml|xml\+rss|xml\+atom|xml\+rdf)(.*)href=(\\\'|\")(.*)(\\\'|\")/';
        $feed = '';
        if (preg_match ($pattern, $site, $match)) {
          $feed = $match[4];
        }

        $me['site'] = $me['comment_author_url'];
        $me['feed'] = $feed;

        //create a record
        $sql = sprintf("INSERT INTO `{$this->table}` (site, feed) VALUES ('%s', '%s')",
                       $me['site'], $me['feed']);

        $this->db->query($sql);
        $this->loaded[$me['comment_author_url']] = $comment->comment_ID;
      }


      //site doesn't have a feed
      if (!$me['feed']) {
        return $text;
      }

      //get the latest post
      if ($me['updated'] < date('Y-m-d H:i:s', strtotime('-1 day'))) {
        require_once(ABSPATH . WPINC . '/rss-functions.php');
        $rss = fetch_rss($me['feed']);

        $me['title'] = trim(strip_tags($rss->items[0]['title']));
        $me['link'] = $rss->items[0]['link'];
        $me['pubdate'] = $rss->items[0]['pubdate'];

        //I wish magpie had a get_description function.  The whole class is pretty fraken useless without it.
        if (isset($rss->items[0]['content']['encoded'])) {
					$desc = $rss->items[0]['content']['encoded'];
        } else if (isset($rss->items[0]['description'])) {
        	$desc = $rss->items[0]['description'];
       	}

        $me['description'] = trim(strip_tags($desc));
        $me['updated'] = date('Y-m-d H:i:s');

        if (strlen($me['description']) > 200) {
          $me['description'] = substr($me['description'], 0, 200) . '[...]';
        }

        $sql = sprintf('UPDATE `%s` SET title = "%s", link = "%s", pubdate = "%s", description = "%s", updated = "%s" WHERE site = "%s"',
                       $this->table,
                       $me['title'],
                       $me['link'],
                       date('Y-m-d H:i:s', strtotime($me['pubdate'])),
                       $me['description'],
                       $me['updated'],
                       $me['site']
                      );

        $this->db->query($sql);
      }

      //show the post
      if ($me['title'] && $me['description']) {
        $text .= '<fieldset class="blogfollow">';
        $text .= '<legend>Read more from ' . $me['comment_author'] . '</legend>';
        $text .= '<h2><a rel="external nofollow" href="' . $me['link'] . '">' . $me['title'] . '</a></h2>';
        $text .= '<p>' . $me['description'] . '</p>';
        $text .= '</fieldset>';
      }

      return $text;
    }

    //had to snag this function from /wp-includes/functions.php
    //wanted a bit more control
    function remote_fopen( $uri ) {
      $timeout = 10;
      $parsed_url = @parse_url($uri);

      if ( !$parsed_url || !is_array($parsed_url) )
        return false;

      if ( !isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], array('http','https')) )
        $uri = 'http://' . $uri;

      if ( function_exists('curl_init') ) {
        $handle = curl_init();
        curl_setopt ($handle, CURLOPT_URL, $uri);
        curl_setopt ($handle, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt ($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($handle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt ($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($handle, CURLOPT_MAXREDIRS, 1);
        $buffer = curl_exec($handle);
        curl_close($handle);
        return $buffer;
      } else if ( ini_get('allow_url_fopen') ) {
        $fp = @fopen( $uri, 'r' );
        if ( !$fp )
          return false;

        //stream_set_timeout($fp, $timeout); // Requires php 4.3
        $linea = '';
        while ( $remote_read = fread($fp, 4096) )
          $linea .= $remote_read;
        fclose($fp);
        return $linea;
      } else {
        return false;
      }
    }
}

$blogfollow =& new blogfollow();
?>