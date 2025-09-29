<?php

declare(strict_types=1);

namespace App\Service;

use App\Traits\HttpClientAwareTrait;
use Ramsey\Uuid\Uuid;

class AccountService
{
    use HttpClientAwareTrait;

    private string $path;

    public function __construct()
    {
        $this->path = '/share/domru/accounts';
        $this->checkStorage();
    }

    public function checkStorage()
    {
        $pathFolder = dirname($this->path);

        if (!is_dir($pathFolder)) {
            mkdir($pathFolder, 0777, true);
        }

        if (! file_exists($this->path)) {
            file_put_contents($this->path, '');
            chmod($this->path, 0777);
        }
    }

    public function getAccounts(): array
    {
        if (!file_exists($this->path)) {
            return [];
        }

        $rows = explode("\n", file_get_contents($this->path));

        $accounts = [];
        foreach ($rows as $row) {
            if (!trim($row)) {
                continue;
            }

            $data = explode('|', $row, 2);
            $accountData = json_decode($data[1], true);
            if (! isset($accountData['uuid'])) {
                $accountData['uuid'] = mb_strtoupper(Uuid::uuid4()->toString());
            }
            $accounts[$data[0]] = $accountData;
        }

        return $accounts;
    }

    public function addAccount($accountData): bool
    {
        $accounts = $this->getAccounts();

        $key = $accountData['id'];
        $accounts[$key] = $accountData;

        $data = [];
        foreach ($accounts as $key => $account) {
            $data[] = $key.'|'.json_encode($account, JSON_UNESCAPED_UNICODE);
        }

        return (bool)file_put_contents($this->path, implode("\n", $data));
    }

    public function removeAccount($accountId): bool
    {
        $accounts = $this->getAccounts();

        foreach ($accounts as $id => $account) {
            if ($id === $accountId) {
                unset($accounts[$id]);
            }
        }

        $data = [];
        foreach ($accounts as $key => $account) {
            $data[] = $key.'|'.json_encode($account, JSON_UNESCAPED_UNICODE);
        }

        return (bool)file_put_contents($this->path, implode("\n", $data) . "\n");
    }

    public function fetchApi()
    {
        $response = $this->getHttp()->request('GET', 'http://127.0.0.1:8080/api?events=true');
        $content = $response->getBody()->getContents();

        return json_decode($content, true);
    }
}
