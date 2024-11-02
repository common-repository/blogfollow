=== BlogFollow ===

Contributors: mattc78
Tags: comments, snippet, blog
Requires at least: 2.0.2
Tested up to: 2.6
Stable tag: trunk

BlogFollow is a WordPress pluggin that shows a snippet from a commenter's blog at the bottom on their comment.

== Description ==

BlogFollow is a WordPress pluggin that shows a snippet from a commenter’s blog at the bottom on their comment. The purpose of this is to encourage user participation in your blog, by providing an incentive - an excerpt from their blog. Both parties win. The blog using BlogFollow receives more comments/content. The commenter has a chance to win new readers using both the comment and a snippet from their blog, which may be completely unrelated to the post topic.

== Installation ==

1. Upload `blogfollow.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Everything else is automatic.  The commenter's feed will be determined automatically if they enter in a url.  You don't have to edit your tempate files either.
4. You may want to edit your css.  See below for an example.
5. You can also add the BlogFollow badge to your site, so readers know you are running the plugin.  Add to your template:
&lt;a href="http://www.pseudocoder.com/blogfollow-show-a-snippet-from-a-commenters-blog-in-the-comment"&gt;&lt;img src="/wp-content/plugins/blogfollow/BlogFollow.png" alt="This blog is running the BlogFollow plugin.  If you comment on a post an excerpt from your latest blog will appear below your comment." title="This blog is running the BlogFollow plugin.  If you comment on a post an excerpt from your latest blog will appear below your comment." /&gt;&lt;/a&gt;


== Frequently Asked Questions ==

= How can I style the feed snippet =

This is just one example.  WordPress does some wierd things as far as adding &lt;br /&gt;'s and &lt;p&gt;'s to your comments automatically.  You may have to tweek the values below to make everything look right.
/******************** BlogFollow Plugin *********************/
.blogfollow {
	margin: 10px 0 0 0;
	padding: 5px;
	line-height: .4em;
}
.blogfollow legend {
	font-weight: bold;
	padding: 0;
	margin: 0;
	font-size: .8em;
}
.blogfollow h2 {
	margin: 0 0 7px 0;
	padding: 0;
}
.blogfollow h2 a {
	font-size: .7em;
	font-weight: bold;
}
.blogfollow p {
	padding:0;
	margin: 0;
	line-height: 1.1em;
}