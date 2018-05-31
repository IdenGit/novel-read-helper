<?php

include_once __DIR__ . '/../bootstrap.php';

$url = getenv('RULATE_URL');

$client = new GuzzleHttp\Client([
    'base_uri' => $url,
    'timeout' => 2.0,
    'cookies' => true
]);

$response = $client->request('GET');

$html = $response->getBody()->getContents();

$crawler = new \Symfony\Component\DomCrawler\Crawler($html);

$login_form = $crawler->filter('#header-login form');
$login_form_found = (boolean) $login_form->count();

if($login_form_found){

    $form_inputs = $login_form->filter('input[type!=submit]');
    $found_inputs = (boolean) $form_inputs->count();

    if($found_inputs){
        $login_inputs = $form_inputs->each(function (\Symfony\Component\DomCrawler\Crawler $input){
            return $input->attr('name');
        });

        $req = $client->request(
            strtoupper($login_form->attr('method')),
            $url . $form_inputs->attr('action'),
            [
                'form_params' =>[
                    $login_inputs[0] => getenv('RULATE_USER'),
                    $login_inputs[1] => getenv('RULATE_PASS'),
                ]
            ]
        );

        $auth =  $req->getBody()->getContents();

        $crawler_auth = new \Symfony\Component\DomCrawler\Crawler($auth);

        var_dump($crawler_auth->filter('#header-submenu li a')->each(function (\Symfony\Component\DomCrawler\Crawler $x){
            return $x->attr('href');
        }));


        $req = $client->request(
            'GET',
            $url . '/my/notices'
        );

        $notices = $req->getBody()->getContents();

        $crawler_notices = new \Symfony\Component\DomCrawler\Crawler($notices);

        $page = $crawler_notices->filter('.pagination li:last-child')->first();

        $last_page_href = $page->filter('a')->first()->attr('href');

        $match = false;
        preg_match('~/(\d){1,}~',$last_page_href,$match);
        $last_page = (int) array_pop($match);

        var_dump($last_page);

    }

//
//
//    $found_inputs_name = $found_inputs

}