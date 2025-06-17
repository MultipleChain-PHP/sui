<?php

declare(strict_types=1);

namespace MultipleChain\Sui;

use Sui\Client;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\BaseNetworkConfig as NetworkConfig;

class Provider implements ProviderInterface
{
    /**
     * @var Client
     */
    public Client $client;

    /**
     * @var NetworkConfig
     */
    public NetworkConfig $network;

    /**
     * @var Provider|null
     */
    private static ?Provider $instance;

    /**
     *
     * @var array<mixed>
     */
    public array $nodes = [
        'mainnet' => [
            'name' => 'Mainnet',
            'cluster' => 'mainnet',
            'rpcUrl' => 'https://fullnode.mainnet.sui.io:443',
            'explorerUrl' => 'https://suiscan.xyz/mainnet/',
            'wsUrl' => null
        ],
        'testnet' => [
            'name' => 'Testnet',
            'cluster' => 'testnet',
            'rpcUrl' => 'https://fullnode.testnet.sui.io:443',
            'explorerUrl' => 'https://suiscan.xyz/testnet/',
            'wsUrl' => null
        ]
    ];

    /**
     * @var array<string>
     */
    public array $node;

    /**
     * @param array<string,mixed> $network
     */
    public function __construct(array $network)
    {
        $this->update($network);
    }

    /**
     * @return Provider
     */
    public static function instance(): Provider
    {
        if (null === self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_NOT_INITIALIZED->value);
        }
        return self::$instance;
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public static function initialize(array $network): void
    {
        if (null !== self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_ALREADY_INITIALIZED->value);
        }
        self::$instance = new self($network);
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public function update(array $network): void
    {
        self::$instance = $this;
        $this->network = new NetworkConfig($network);
        $this->node = $this->nodes[$this->network->isTestnet() ? 'testnet' : 'mainnet'];
        $this->node['rpcUrl'] = $this->network->getRpcUrl() ?? $this->node['rpcUrl'];
        $this->node['wsUrl'] = $this->network->getWsUrl() ?? $this->node['wsUrl'];
        $this->client = new Client($this->node['rpcUrl']);
    }

    /**
     * @return bool
     */
    public function isTestnet(): bool
    {
        return $this->network->isTestnet();
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkRpcConnection(?string $url = null): bool
    {
        try {
            $curl = curl_init($url ?? $this->node['rpcUrl']);
            if (false === $curl) {
                return false;
            }
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
                'jsonrpc' => '2.0',
                'method' => 'rpc.discover',
                'id' => 1,
            ]));
            curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return 200 === $httpCode;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkWsConnection(?string $url = null): bool
    {
        return true;
    }
}
