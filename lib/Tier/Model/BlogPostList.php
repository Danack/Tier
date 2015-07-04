<?php


namespace Tier\Model;


class BlogPostList {

    private $blogPosts;
    
    public function __construct(array $blogPosts)
    {
        $this->blogPosts = $blogPosts;
    }

    /**
     * @return array
     */
    public function getBlogPosts()
    {
        return $this->blogPosts;
    }
    
    
}

