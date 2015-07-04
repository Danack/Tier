<?php


namespace Tier\Controller;

use Tier\Model\BlogPost;
use Tier\Model\BlogPostList;

class UsesDatabase {

    public function display(\PDO $pdo)
    {
        $result = $pdo->query("select title, text from blog.posts limit 10");

        if (!$result) {
            throw new \Exception("Database unavailable");
        }

        $blogPosts = [];
            
        while($row = $result->fetch(\PDO::FETCH_ASSOC)){ 
            $blogPosts[] = new BlogPost($row['title'], $row['text']);
        }

        return getTemplateCallable('pages/usesDB', ['Tier\Model\BlogPostList' => new BlogPostList($blogPosts)]);
    }
}
