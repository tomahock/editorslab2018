<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GraphAware\Neo4j\Client\ClientBuilder;

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
                    return post,collect({c:c,owner:owner,commentator:commentator}) as meta 
                    order by post.date desc
                    limit 50";

        $result = $client->run($query);

        $records = $result->getRecords();

        $response = array();

        foreach($records as $r){
            $_r = array();

            $_r['post'] = $r->get('post')->values();

            $meta = $r->get('meta');

            $_r['owner'] = $meta[0]['owner']->values();

            $comments = array();

            foreach($meta as $m){
                $_c = array();
                $_c['comment'] = $m['c']->values();
                $_c['owner'] = $m['commentator']->values();

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
        foreach($records as $r){
            $response[] = $r->get('p')->values();
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

        $result = $client->run($query,['playerId' => $playerId]);

        $records = $result->getRecords();

        $response = array();
        foreach($records as $r){
            $_r = array();

            $_r['post'] = $r->get('post')->values();

            $meta = $r->get('meta');

            $comments = array();

            foreach($meta as $m){
                $_c = array();
                if($m['c']){
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
}
