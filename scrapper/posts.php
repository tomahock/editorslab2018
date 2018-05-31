<?php
/**
 * Created by PhpStorm.
 * User: tomahock
 * Date: 30/05/2018
 * Time: 13:37
 */

require_once 'vendor/autoload.php';

use GraphAware\Neo4j\Client\ClientBuilder;

$client = ClientBuilder::create()
    ->addConnection('default',
        'http://neo4j:x@localhost:7474')// Example for HTTP connection configuration (port is optional)
    ->addConnection('bolt',
        'bolt://neo4j:x@localhost:7687')// Example for BOLT connection configuration (port is optional)
    ->build();

$players = array(
    "essamelhadary1",
    "sherifekramyofficial",
    "m.elshenawy1",
    "elmohamady",
    "mohamedabdelshafy_of",
    "omargaber_4",
    "saadsamir.official",
    "karimhafez45",
    "aymanashraf12",
    "amrotarekk",
    "mahmoudhamdy_28",
    "elnennym",
    "abdallahelsaid19",
    "shikabala7",
    "ramadansobhi51",
    "tarekhamed_3",
    "mosalah",
    "marwan_mohsen9",
    "hassankouka9",
    "igorakinfeev_35",
    "vladimir_gabulov",
    "soslan_dzhanaev",
    "19smolnikov_official",
    "romainnewton",
    "19samedov84",
    "cheryshev90",
    "artem.dzyuba",
    "miranchukaleksei",
    "osamahawsawi3",
    "omar.4h",
    "iyasser12",
    "motaz_hawsawi.25",
    "29salem",
    "abdullahotayf8",
    "muslera",
    "fcmartinsilva1",
    "martin_campana",
    "diegogodin",
    "martincaceres_7",
    "josemariagimenez",
    "sebastiancoates16",
    "gsilva.94",
    "guillermovarela4",
    "nicolaslodeiro",
    "carlossanchez6",
    "vecino",
    "g10dearrascaeta",
    "rodrigo_bentancur",
    "cdlss93",
    "jonathan_urretaviscaya_oficial",
    "ltorreira34",
    "cavaniofficial21",
    "luissuarez9",
    "stuanicristhian",
    "rpatricio1",
    "brunoralves2oficiall",
    "official_pepe",
    "mfernandes1818",
    "raphaelguerreiro14",
    "f6nte",
    "cristiano",
    "j.moutinho98",
    "andresilva9",
    "joaome10",
    "bernardocarvalhosilva",
    "anthonylopes12",
    "rubendias",
    "wcarvalho14",
    "ri_pereira",
    "brunofernandes.10",
    "goncaloguedes15",
    "gelsondany77",
    "mariorui_6",
    "ricardoquaresmaoficial",
    "cedricsoares41",
    "adriensilva23",
    "alirezabeiranvand.official",
    "rashid.mazaheri12",
    "abedz",
    "p.montazeri33",
    "raminrezaeian",
    "masoudsshojaei",
    "ashkandejagah",
    "vahid.amiri.official",
    "omidebrahimi_",
    "saman.ghoddos",
    "ali_gholizadeh11",
    "kariiiiim10",
    "rgucci16",
    "alirezajb_official",
    "sardar_azmoun",
    "jbutland_",
    "jpickford1",
    "trentaa66",
    "garyjcahill",
    "fabian_delph",
    "philjones_4",
    "harrymaguire93",
    "johnstonesofficial",
    "ktrippier2",
    "kylewalker2",
    "youngy_18",
    "ericdier15",
    "jhenderson",
    "jesselingard",
    "rubey_lcheek",
    "harrykane",
    "marcusrashford",
    "sterling7",
    "vardy7",
    "dannywelbeck",
);

//$players = array(
//    'dinissilvasantos',
//    'brunofernandes.10'
//);

$file = fopen('players.csv', 'r');
while (($line = fgetcsv($file)) !== FALSE) {
    $cache = new Instagram\Storage\CacheManager('cache/');
    $api = new Instagram\Api($cache);
    $api->setUserName($line[1]);

    $feed = $api->getFeed();

    $data = array(
        '_id' => $feed->id,
        'username' => $feed->userName,
        'displayName' => $feed->fullName,
        'bio' => $feed->biography,
        'avatar' => $feed->profilePicture
    );

    $client->run('MERGE (n:Player {_id:{_id}, username:{username}, displayName:{displayName}, bio: {bio}, avatar:{avatar}, country:{country}})',
        [
            '_id' => $data['_id'],
            'username' => $data['username'],
            'displayName' => $data['displayName'],
            'bio' => $data['bio'],
            'country' => $line[0],
            'avatar' => $data['avatar'],
        ]);

    foreach ($feed->medias as $m) {
        $data = array(
            '_id' => $m->id,
            'thumb' => $m->thumbnailSrc,
            'link' => $m->link,
            'date' => $m->date->getTimestamp(),
            'src' => $m->displaySrc,
            'message' => empty($m->caption) ? '' : $m->caption,
            'urlId' => explode('/', $m->link)[4]
        );

        $client->run('MERGE (n:Post {_id:{_id}, thumb:{thumb}, link:{link}, date:{date}, src:{src}, message:{message}, urlId:{urlId}})',
            [
                '_id' => $data['_id'],
                'thumb' => $data['thumb'],
                'link' => $data['link'],
                'date' => $data['date'],
                'src' => $data['src'],
                'message' => $data['message'],
                'urlId' => $data['urlId']
            ]);
        $client->run('MATCH (n:Post), (p:Player) where n._id={post_id} and p.username={username} MERGE (n)<-[:Publish]-(p)',
            ['post_id' => $data['_id'], 'username' => $line[1]]);

        print_r($m);
    }
}
