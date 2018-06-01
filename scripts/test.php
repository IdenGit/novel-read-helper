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

            }
        }

    }
}

//$crawler->add('<test></test>');
//$crawler->add('<a>1</a>');
//
//
//$response = $client->request('GET');
//
//$html = $response->getBody()->getContents();
//
//
//
//$login_form = $crawler->filter('#header-login form');
//$login_form_found = (boolean) $login_form->count();
//
//if($login_form_found){
//
//    $form_inputs = $login_form->filter('input[type!=submit]');
//    $found_inputs = (boolean) $form_inputs->count();
//
//    if($found_inputs){
//        $login_inputs = $form_inputs->each(function (\Symfony\Component\DomCrawler\Crawler $input){
//            return $input->attr('name');
//        });
//
//        $req = $client->request(
//            strtoupper($login_form->attr('method')),
//            $url . $form_inputs->attr('action'),
//            [
//                'form_params' =>[
//                    $login_inputs[0] => getenv('RULATE_USER'),
//                    $login_inputs[1] => getenv('RULATE_PASS'),
//                ]
//            ]
//        );
//
//        $auth =  $req->getBody()->getContents();
//
//        $crawler_auth = new \Symfony\Component\DomCrawler\Crawler($auth);
//
//        var_dump($crawler_auth->filter('#header-submenu li a')->each(function (\Symfony\Component\DomCrawler\Crawler $x){
//            return $x->attr('href');
//        }));
//
//
//        $req = $client->request(
//            'GET',
//            $url . '/my/notices'
//        );
//
//        $notices = $req->getBody()->getContents();
//
//        $crawler_notices = new \Symfony\Component\DomCrawler\Crawler($notices);
//
//        $page = $crawler_notices->filter('.pagination li:last-child')->first();
//
//        $last_page_href = $page->filter('a')->first()->attr('href');
//
//        $match = false;
//        preg_match('~/(\d){1,}~',$last_page_href,$match);
//        $last_page = (int) array_pop($match);
//
//        var_dump($last_page);
//
//
//
//
//    }
//
////
////
////    $found_inputs_name = $found_inputs

//}