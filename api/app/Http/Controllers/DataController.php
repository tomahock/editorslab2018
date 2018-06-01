<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GraphAware\Neo4j\Client\ClientBuilder;
use lotsofcode\TagCloud\TagCloud;

class DataController extends Controller
{
    private function getClient()
    {
        return ClientBuilder::create()
            ->addConnection('default',
                'http://neo4j:x@localhost:7474')// Example for HTTP connection configuration (port is optional)
            ->addConnection('bolt',
                'bolt://neo4j:x@localhost:7687')// Example for BOLT connection configuration (port is optional)
            ->build();
    }

    public function getLast()
    {
        $client = $this->getClient();

        $query = "match (post:Post)<-[:Commented]-(c:Comment), 
                    (post)<-[:Publish]-(owner:Player), 
                    (c)<-[:Create]-(commentator:Player) 
                    where not owner.username=commentator.username
                    return post,collect({c:c,owner:owner,commentator:commentator}) as meta 
                    order by post.date desc
                    limit 10";

        $result = $client->run($query);

        $records = $result->getRecords();

        $response = array();

        foreach ($records as $r) {
            $_r = array();

            $_r['post'] = $r->get('post')->values();

            $meta = $r->get('meta');

            $_r['owner'] = $meta[0]['owner']->values();

            $comments = array();

            foreach ($meta as $m) {
                $_c = array();
                $_c['comment'] = $m['c']->values();
                $_c['owner'] = $m['commentator']->values();

                if ($_c['owner']['username'] === $_r['owner']['username']) {
                    $_c['owner']['same'] = true;
                } else {
                    $_c['owner']['same'] = false;
                }

                $comments[] = $_c;
            }

            $_r['comments'] = $comments;
            $response[] = $_r;
        }


        return \Response::json($response);
    }

    public function getPlayers()
    {
        $client = $this->getClient();

        $query = "MATCH (p:Player) return p order by p.username";

        $result = $client->run($query);

        $records = $result->getRecords();

        $response = array();
        foreach ($records as $r) {
            $response[] = $r->get('p')->values();
        }

        return \Response::json($response);
    }

    public function getPlayer($playerId)
    {
        $client = $this->getClient();

        $query = "MATCH (p:Player {username:{playerId}}) return p";

        $result = $client->run($query, ["playerId" => $playerId]);

        $records = $result->getRecords();

        $response = array();
        foreach ($records as $r) {
            $response = $r->get('p')->values();
        }

        return \Response::json($response);
    }

    public function getPlayersCompare($playerId, $player2Id)
    {
        $client = $this->getClient();

        $query = "match (p:Player {username:{playerId}})-[:Publish]->(post:Post)
                    with p,post
                    match (post)<-[:Commented]-(c:Comment)
                    match (c)-[]-(person:Player {username:{player2Id}})
                    where not p.username=person.username
                    return count(c) as totalComments, count(post) as totalPosts";

        $result = $client->run($query, ['playerId' => $playerId, 'player2Id' => $player2Id]);

        $records = $result->getRecords();

        $response = array();

        foreach ($records as $r) {
            $response[$player2Id]['comments'] = $r->get('totalComments');
            $response[$playerId]['posts'] = $r->get('totalPosts');
        }

        $result = $client->run($query, ['playerId' => $player2Id, 'player2Id' => $playerId]);

        $records = $result->getRecords();

        foreach ($records as $r) {
            $response[$playerId]['comments'] = $r->get('totalComments');
            $response[$player2Id]['posts'] = $r->get('totalPosts');
        }

        $queryTotals = "match (p:Player {username:{playerId}})-[:Publish]->(post:Post)
                        OPTIONAL match (p)-[:Create]->(c:Comment)
                        OPTIONAL match (post)<-[:Commented]->(cc:Comment)
                        return p,count(post) as totalPosts,count(c) as totalComments, count(cc) as totalCommentsReceived";

        $result = $client->run($queryTotals, ['playerId' => $playerId]);

        $records = $result->getRecords();

        foreach ($records as $r) {
            $response[$playerId]['totalPosts'] = $r->get('totalPosts');
            $response[$playerId]['totalComments'] = $r->get('totalComments');
            $response[$playerId]['totalCommentsReceived'] = $r->get('totalCommentsReceived');
            $p = $r->get('p')->values();
            $response[$playerId] = array_merge($response[$playerId], $p);
        }

        $result = $client->run($queryTotals, ['playerId' => $player2Id]);

        $records = $result->getRecords();

        foreach ($records as $r) {
            $response[$player2Id]['totalPosts'] = $r->get('totalPosts');
            $response[$player2Id]['totalComments'] = $r->get('totalComments');
            $response[$player2Id]['totalCommentsReceived'] = $r->get('totalCommentsReceived');
            $p = $r->get('p')->values();
            $response[$player2Id] = array_merge($response[$player2Id], $p);
        }

        return \Response::json($response);
    }

    public function getPlayerPosts($playerId)
    {
        $client = $this->getClient();

        $query = "match (p:Player {username:{playerId}})-[:Publish]->(post:Post)
            
                    OPTIONAL MATCH (post)<-[:Commented]-(c:Comment)
                    OPTIONAL MATCH (c)-[:Create]-(commentator:Player) 
                    return p,post, collect({c:c,commentator:commentator}) as meta
                    order by post.date desc";

        $result = $client->run($query, ['playerId' => $playerId]);

        $records = $result->getRecords();

        $response = array();
        foreach ($records as $r) {
            $_r = array();

            $_r['post'] = $r->get('post')->values();

            $meta = $r->get('meta');

            $comments = array();

            foreach ($meta as $m) {
                $_c = array();
                if ($m['c']) {
                    $_c['comment'] = $m['c']->values();
                    $_c['owner'] = $m['commentator']->values();
                    $comments[] = $_c;
                }
            }

            $_r['comments'] = $comments;
            $response[] = $_r;
        }

        return \Response::json($response);
    }

    public function getPlayerPostCloud($playerId)
    {
        $client = $this->getClient();

        $query = "match (p:Player {username:{playerId}})-[:Publish]->(post:Post)
                    return post.message as text";

        $result = $client->run($query, ['playerId' => $playerId]);

        $records = $result->getRecords();

        $cloud = new TagCloud();
        $response = array();
        foreach ($records as $r) {
            $cloud->addString($r->get('text'));
        }

        $response['cloud'] = $cloud->render();

        return \Response::json($response);
    }

    public function getPlayerCommentsCloud($playerId)
    {
        $client = $this->getClient();

        $query = "match (p:Player {username:{playerId}})-[:Create]->(c:Comment)
                    return c.text as text";

        $result = $client->run($query, ['playerId' => $playerId]);

        $records = $result->getRecords();

        $cloud = new TagCloud();
        $response = array();
        foreach ($records as $r) {
            $cloud->addString($r->get('text'));
        }

        $response['cloud'] = $cloud->render();

        return \Response::json($response);
    }


    public function getPlayerStats($playerId)
    {
        $client = $this->getClient();

        $query = "match (p:Player {username:{playerId}})-[:Publish]->(post:Post)
                    with p,post
                    match (post)<-[:Commented]-(c:Comment)
                    match (c)-[]-(person:Player)
                    where not p.username=person.username
                    return count(person.country) as total, person.country as country, count(person) as totalx";

        $result = $client->run($query, ['playerId' => $playerId]);

        $records = $result->getRecords();

        $response = array(
            'comments' => array()
        );
        foreach ($records as $r) {
            $_r = array();
            $_r['incoming']['country'] = $r->get('country');
            $_r['incoming']['total'] = $r->get('total');

            $response['comments'][] = $_r;
            $response['receive'] = $r->get('totalx');
        }

        $query = "match (p:Player {username:{playerId}})-[:Create]-(c:Comment)
                    match (c)-[]-(post:Post)
                    match (post)-[]-(pp:Player)
                    return count(pp.country) as total, pp.country as country, count(pp) as totalx";

        $result = $client->run($query, ['playerId' => $playerId]);

        $records = $result->getRecords();

        foreach ($records as $r) {
            $_r = array();
            $_r['outgoing']['country'] = $r->get('country');
            $_r['outgoing']['total'] = $r->get('total');

            $response['comments'][] = $_r;
            $response['sent'] = $r->get('totalx');
        }

        if (!isset($response['sent'])) {
            $response['sent'] = 0;
        }

        if (!isset($response['receive'])) {
            $response['receive'] = 0;
        }

        $query = "match (p:Player {username:{playerId}})-[:Publish]->(post:Post)
                    with p,post
                    match (post)<-[:Commented]-(c:Comment)
                    match (c)-[]-(person:Player)
                    where not p.username=person.username
                    with person, count(c) as c order by c
                    return person as player,c";

        $result = $client->run($query, ['playerId' => $playerId]);

        if($result->size()){
            $r = $result->getRecord();

            $response['mostCommentsReceive'] = array(
                'total' => $r->get('c'),
                'player' => $r->get('player')->values(),
            );
        } else {
            $response['mostCommentsReceive'] = array(
            );
        }

        $query = "match (p:Player {username:{playerId}})-[:Create]->(c:Comment)
                    with p,c
                     match (post:Post)<-[:Commented]-(c)
                    match (post)<-[:Publish]-(person:Player)
                    where not p.username=person.username
                    with person,c order by c desc
                    return count(c) as c, person as player";

        $result = $client->run($query, ['playerId' => $playerId]);

        if($result->size()){
            $r = $result->getRecord();

            $response['mostCommentsSent'] = array(
                'total' => $r->get('c'),
                'player' => $r->get('player')->values(),
            );
        } else {
            $response['mostCommentsSent'] = array(
            );
        }

        $query = "match (p:Player {username:{playerId}})-[:Create]->(c:Comment)
                     with p,c
                     match (post:Post)<-[:Commented]-(c),
                     (post)<-[:Publish]-(person:Player)
                    where p.username=person.username
                    return count(c) as total";

        $result = $client->run($query, ['playerId' => $playerId]);

        if($result->size()){
            $r = $result->getRecord();

            $response['selfComments'] = $r->get('total');
        } else {
            $response['selfComments'] = 0;
        }


        $postHours = $this->getPostHours($playerId);
        $commentsHours = $this->getCommentsHours($playerId);

        $response['postHours'] = $postHours;
        $response['commentsHours'] = $commentsHours;
        return \Response::json($response);
    }

    private function getPostHours($playerId)
    {
        $client = $this->getClient();

        $query = "match (p:Player {username:{playerId}})-[:Publish]->(post:Post)
                   return post.date as d";

        $result = $client->run($query, ['playerId' => $playerId]);

        $records = $result->getRecords();

        $hours = array(
            "0" => 0,
            "1" => 0,
            "2" => 0,
            "3" => 0,
            "4" => 0,
            "5" => 0,
            "6" => 0,
            "7" => 0,
            "8" => 0,
            "9" => 0,
            "10" => 0,
            "11" => 0,
            "12" => 0,
            "13" => 0,
            "14" => 0,
            "15" => 0,
            "16" => 0,
            "17" => 0,
            "18" => 0,
            "19" => 0,
            "20" => 0,
            "21" => 0,
            "22" => 0,
            "23" => 0,
        );

        foreach ($records as $r) {
            $hour = (int)date("h", $r->get('d'));
            $_h = (string)$hour;
            $hours[$_h] = $hours[$_h] + 1;
        }

        return $hours;
    }

    private function getCommentsHours($playerId)
    {

        $client = $this->getClient();

        $query = "match (p:Player {username:{playerId}})-[:Create]->(comment:Comment)
                   return comment.date as d";

        $result = $client->run($query, ['playerId' => $playerId]);

        $records = $result->getRecords();

        $hours = array(
            "0" => 0,
            "1" => 0,
            "2" => 0,
            "3" => 0,
            "4" => 0,
            "5" => 0,
            "6" => 0,
            "7" => 0,
            "8" => 0,
            "9" => 0,
            "10" => 0,
            "11" => 0,
            "12" => 0,
            "13" => 0,
            "14" => 0,
            "15" => 0,
            "16" => 0,
            "17" => 0,
            "18" => 0,
            "19" => 0,
            "20" => 0,
            "21" => 0,
            "22" => 0,
            "23" => 0,
        );

        foreach ($records as $r) {
            $hour = (int)date("h", $r->get('d'));
            $_h = (string)$hour;
            $hours[$_h] = $hours[$_h] + 1;
        }

        return $hours;
    }
}
