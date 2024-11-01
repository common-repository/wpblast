<?php

namespace Smartfire\Wordpress\WPBlast;

class LinkPrerender
{
    /**
     * @var Settings
     */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function adminInit()
    {
        add_filter('page_row_actions', [$this, 'addRowAction'], 10, 2);
        add_filter('post_row_actions', [$this, 'addRowAction'], 10, 2);
    }

    public function isAllowedToSeeCrawlerLink($permalink)
    {
        $user = wp_get_current_user();
        if ($user && preg_match($this->settings->getCrawlerUserRegex(), $user->user_email)) {
            if ($this->settings->isUrlActive($permalink)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function addRowAction($actions, $post)
    {
        if (apply_filters('wpblast_permissions_crawler_link', $this->isAllowedToSeeCrawlerLink(get_permalink($post->ID)))) {
            $actions['wpblast'] = sprintf(
                '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
                add_query_arg(PageRender::WPBLAST_CRAWLER, 'true', get_permalink($post->ID)),
                /* translators: %s: Post title. */
                esc_attr(sprintf(__('View &#8220;%s&#8221;', 'wpblast'), $post->title)),
                __('View as Web Crawler', 'wpblast')
            );
        }
        return $actions;
    }
}
