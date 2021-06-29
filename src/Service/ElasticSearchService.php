<?php


namespace App\Service;


use Elasticsearch\ClientBuilder;

class ElasticSearchService
{

    const ELK_HOST = 'elk';
    const ELK_PORT = '9200';
    const CONNECTION_URL = ElasticSearchService::ELK_HOST . ':' . ElasticSearchService::ELK_PORT;

    const ITEMS_CONTAINER_NAME = "items";

    private $client;
    private $params;
    private $searchString;

    public function __construct(string $searchString)
    {
        $this->searchString = $searchString;

        $this->params = [
            'index' => strtolower($searchString),
            'id' => strtolower($searchString),
        ];

        $this->createConnection();
    }

    public function createConnection()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([ElasticSearchService::CONNECTION_URL])
            ->build();
    }

    public function dataInElkExists()
    {
        return $this->client->exists($this->params);
    }

    public function getData()
    {
        $result = $this->client->get($this->params);
        return $result['_source'][ElasticSearchService::ITEMS_CONTAINER_NAME];
    }

    public function indexDate($data)
    {
        $this->params['body'] = [ElasticSearchService::ITEMS_CONTAINER_NAME => $data];
        $this->params['type'] = strtolower($this->searchString);
        $this->client->index($this->params);
    }

    public function getDataFromTasksObjects($items): array
    {
        $result = [];

        foreach ($items as $item)
        {
            $result[] = [
                'id' => $item->getId(),
                'field1' => $item->getField1(),
                'field2' => $item->getField2(),
                'dateCreated' => date_format($item->getDateCreated(), 'Y-m-d H:i:s'),
            ];
        }

        return $result;
    }



}