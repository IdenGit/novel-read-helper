<?php


namespace NRH\Parsers\Rulate;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class RulateParser
 * @package NRH\Parsers\Rulate
 */
class RulateParser
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * @var array
     */
    protected $inputs = [];

    /**
     * @var bool
     */
    protected $auth = false;

    /**
     * RulateParser constructor.
     * @param $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    protected function newCrawler(string $node): Crawler
    {
        $this->crawler = new Crawler($node);
        return $this->crawler;
    }

    public function checkConnection(): bool
    {
        return $this->getRequest()->getStatusCode() === 200;
    }

    public function authorize($login, $password): bool
    {
        if (
            $this->checkConnection()
            && $this->checkAuthPage()
        ) {
            $response = $this->postRequest('/', [
                $this->inputs[0] => $login,
                $this->inputs[1] => $password,
            ]);

            if (
                $response->getStatusCode() === 200
                && !$this->checkAuthPage($response)
            ) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new \RuntimeException('connection fail');
        }
    }

    public function lastNoticePage() : int
    {
        $page = $this->newCrawler($this->getRequest('/my/notices')->getBody()->getContents());

        $paginator = $page->filter('.pagination li:last-child')->first();

        if ($paginator) {
            $last_page_href = $paginator->filter('a')->first()->attr('href');
            $match = false;
            preg_match('~/(\d){1,}~', $last_page_href, $match);
            return (int)array_pop($match);
        }
        return 0;
    }

    public function getNoticesPage(int $page){
        if($page ===1){
            $url = '/my/notices';
        }else{
            $url = '/my/notices/Notice_page/' . $page;
        }

        $data = $this->newCrawler($this->getRequest($url)->getBody()->getContents());

        $notices = $data->filter('#Notices li');

        if($notices->count()){
            return $notices->each(function (Crawler $node) use ($page){
               $result = [
                   't_id' => $node->filter('li')->first()->attr('id'),
                   'href' => $node->filter('li > p > a')->first()->attr('href'),
                   'book' => $node->filter('li > p > a')->first()->getNode(0)->textContent,
                   'text' => $node->filter('li > p')->first()->getNode(0)->textContent,
                   'page' => $page
               ];
               $result['text'] = str_replace($result['book'],'', $result['text']);

               $id = null;
               preg_match('~\d{1,}~',$result['t_id'], $id);

               $result['id'] = (int) $id[0];

               return $result;
            });
        }
        return [];
    }

    protected function checkAuthPage($page = null): bool
    {
        $page = $page ?: $this->getRequest();
        $crawler = $this->newCrawler($page->getBody()->getContents());

        $login_form = $crawler->filter('#header-login form');
        $login_form_found = (boolean)$login_form->count();

        if (!$login_form_found) {
            return false;
        }

        $form_inputs = $login_form->filter('input[type!=submit]');
        $this->inputs = $form_inputs->each(function (\Symfony\Component\DomCrawler\Crawler $input) {
            return $input->attr('name');
        });
        return (boolean)$form_inputs->count();
    }

    protected function getRequest(string $url = null): Response
    {
        $url = $url ?: '/';
        return $this->client->request('GET', $url);
    }

    public function postRequest(string $url, array $data): Response
    {
        return $this->client->request('POST', $url, [
                'form_params' => $data
            ]
        );
    }


}