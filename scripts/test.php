<?php

include_once __DIR__ . '/../bootstrap.php';

$url = getenv('RULATE_URL');

$client = new GuzzleHttp\Client([
    'base_uri' => $url,
    'timeout' => 5.0,
    'cookies' => true
]);

$crawler = new \Symfony\Component\DomCrawler\Crawler();

$parser = new \NRH\Parsers\Rulate\RulateParser($client,$crawler);

if($parser->checkConnection()){
    $auth = $parser->authorize(
        getenv('RULATE_USER'),
        getenv('RULATE_PASS')
    );

    if($auth){
        $pages =$parser->lastNoticePage();

        $notices = [];
        if($pages === 1){
            $notices = $parser->getNoticesPage(1);
        }else{
            for ($page_index = 1; $page_index <= $pages; $page_index++){
                $notices = array_merge($notices, $parser->getNoticesPage($page_index));
            }
        }

        echo count($notices);

        if(count($notices)){
            $doubles = [];

            $books = [];

            foreach ($notices as $notice){
                if(array_key_exists($notice['href'],$books)){
                    $doubles[$notice['id']] = $notice['page'];
                }else{
                    $books[$notice['href']] = $notice;
                }
            }

            if(count($doubles)){
                $removes = [];
                foreach ($doubles as $id => $page ){
                    if($page ===1){
                        $url = '/my/notices';
                    }else{
                        $url = '/my/notices/Notice_page/' . $page;
                    }

                    $removes[$id] = $client->postAsync($url,[
                            'form_params' => [
                                'rm' => $id
                            ]
                    ]);
//                    $response = $client->post($url,[
//                        'form_params' => [
//                            'rm' => $id
//                        ]
//                    ]);
//                    $removes[$id] = $response;
//                    var_dump($response->getStatusCode());
                }

                $results = GuzzleHttp\Promise\settle($removes)->wait();
        	echo "\nrm\n" . count($removes);
            } ;
        }

    }
}

