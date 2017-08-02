<?php
/**
 * EthereumClient.php
 *
 * @author   Ilya Sinyakin <sinyakin.ilya@gmail.com>
 */
namespace SinyakinIlya\Ethereum;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    /** @var GuzzleClient */
    public $httpClient;
    private $ip;
    private $port;

    public function __construct($ip, $port = 8545)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->httpClient = new GuzzleClient();
    }

    /**
     * @param $method
     * @param array $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function callMethod($method, array $params = [])
    {
        $id = rand(1, 100);
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => array_values($params),
            'id' => $id
        ];

        $body = json_encode($request);
        $response = $this->getResponse($body);
        $response = json_decode($response, true);

        if (json_last_error() > 0) {
            throw new \Exception(json_last_error_msg());
        }

        if ($response['id'] !== $id) {
            throw new \Exception(
                sprintf('Given ID %d, differs from expected %d', $response->id, $this->id)
            );
        }

        return $response['result'];
    }

    /**
     * @param $body
     *
     * @return string
     */
    private function getResponse($body)
    {
        return $this
            ->httpClient
            ->request(
                'post',
                $this->getUrl(),
                [
                    'body' => $body,
                    'headers' => $this->getHeaders()
                ]
            )
            ->getBody()
            ->getContents();
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        return 'http://' . $this->ip . ':' . $this->port;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     *
     * @return Client
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     *
     * @return Client
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    private function getHeaders()
    {
        return ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
    }


}