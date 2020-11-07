<?php

namespace Jeto\Synclastic\Configuration;

final class ElasticConfiguration
{
    private string $serverUrl;

    public function __construct(string $serverUrl)
    {
        $this->serverUrl = $serverUrl;
    }

    public function getServerUrl(): string
    {
        return $this->serverUrl;
    }
}
