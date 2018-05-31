<?php
/**
 * Created by PhpStorm.
 * User: tomahock
 * Date: 30/05/2018
 * Time: 14:22
 */

require_once 'vendor/autoload.php';

use GraphAware\Neo4j\Client\ClientBuilder;

$client = ClientBuilder::create()
    ->addConnection('default',
        'http://neo4j:x@localhost:7474')// Example for HTTP connection configuration (port is optional)
    ->addConnection('bolt',
        'bolt://neo4j:x@localhost:7687')// Example for BOLT connection configuration (port is optional)
    ->build();

$gclient = new \GuzzleHttp\Client(['cookies' => true]);

$result = $client->run('MATCH (n:Post)<-[:Publish]-(p:Player) RETURN n,p');

$posts = $result->getRecords();

$url = "https://www.instagram.com/p/%s/?__a=1";

foreach ($posts as $p) {
    $_p = $p->get('n');
    $player = $p->get('p');
    $_url = sprintf($url, $_p->value('urlId'));

    $options = array(
        'headers' => array(
            'X-Requested-With' =>' XMLHttpRequest',
        )
    );
    try{
        $res = $gclient->request('GET', $_url, $options);

        $cookieJar = $gclient->getConfig('cookies');

        $response = json_decode($res->getBody());
        $comments = $response->graphql->shortcode_media->edge_media_to_comment;

        foreach ($comments->edges as $c) {
            $owner = $c->node->owner->username;
            if(usernameExists($owner)){
                addComment($c,$player->get('username'), $_p->get('_id'));
            }
        }
    } catch (GuzzleHttp\Exception\RequestException $e){
        var_dump('lel');
    }


//    if($comments->page_info->has_next_page){
//        paginateRequest($_p->value('urlId'),$comments->page_info->end_cursor,$player,$_p);
//    }

}

function paginateRequest($shortcode,$after_token,$player,$_p)
{
    echo 'paginating' . PHP_EOL;
    echo $player->get('username') . PHP_EOL;
    $queryHash = '33ba35852cb50da46f5b5e889df7d159';

    $variables = array(
        'shortcode' => $shortcode,
        'first' => 24,
        'after' => $after_token
    );

    $vars = json_encode($variables);

    $url = "https://www.instagram.com/graphql/query/?query_hash={$queryHash}&variables={$vars}";

    var_dump($url);

    $gclient = new \GuzzleHttp\Client();

    $res = $gclient->request('GET', $url);

    $response = json_decode($res->getBody());
    $comments = $response->graphql->shortcode_media->edge_media_to_comment;

    foreach ($comments->edges as $c) {
        $owner = $c->node->owner->username;
        if(usernameExists($owner)){
            addComment($c,$player->get('username'), $_p->get('_id'));
        }
    }

    if($comments->page_info->has_next_page){
        paginateRequest($_p->value('urlId'),$comments->page_info->end_cursor,$player,$_p);
    }
}

function addComment($comment, $playerUsername, $postId)
{
    $client = ClientBuilder::create()
        ->addConnection('default',
            'http://neo4j:x@localhost:7474')// Example for HTTP connection configuration (port is optional)
        ->addConnection('bolt',
            'bolt://neo4j:x@localhost:7687')// Example for BOLT connection configuration (port is optional)
        ->build();

    $data = array(
        '_id' => $comment->node->id,
        'text' => $comment->node->text,
        'date' => $comment->node->created_at,
    );

    echo  $comment->node->text . PHP_EOL;

    $client->run('MERGE (n:Comment {_id:{_id}, text:{text}, date:{date}})', ['_id' => $data['_id'], 'text' => $data['text'], 'date' => $data['date']]);

    $client->run('MATCH (p:Post), (c:Comment) where p._id={postId} and c._id={commentId} MERGE (p)<-[:Commented]-(c)', ['postId'=>$postId, 'commentId' => $data['_id']]);
    $client->run('MATCH (p:Player), (c:Comment) where p.username={playerUsername} and c._id={commentId} MERGE (p)-[:Create]->(c)', ['playerUsername'=>$comment->node->owner->username, 'commentId' => $data['_id']]);

}

function usernameExists($username)
{
    $client = ClientBuilder::create()
        ->addConnection('default',
            'http://neo4j:x@localhost:7474')// Example for HTTP connection configuration (port is optional)
        ->addConnection('bolt',
            'bolt://neo4j:x@localhost:7687')// Example for BOLT connection configuration (port is optional)
        ->build();


    $result = $client->run('MATCH (n:Player) where n.username={username} RETURN n', ['username' => $username]);

    return boolval($result->size());
}