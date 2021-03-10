<?php

namespace Osana\Challenge\Services\GitHub;

use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\UsersRepository;
use Tightenco\Collect\Support\Collection;

class GitHubUsersRepository implements UsersRepository
{
    public function findByLogin(Login $nameSearched, int $limit = 0): Collection
    {
        // TODO: implement me
        $sizeMax = floor($limit / 2);
        $data = (array) $this->getDataGithub('users');
        $result = [];
        $cantUser = 0;
        $i = 0;

        while (($cantUser < $sizeMax) && ($i <= count($data) - 1)) {
            $value = strncasecmp(
                $data[$i]->login,
                $nameSearched->getValue(),
                strlen($nameSearched->getValue())
            );

            if (
                strncasecmp(
                    $data[$i]->login,
                    $nameSearched->getValue(),
                    strlen($nameSearched->getValue())
                ) === 0
            ) {

                $name = new Name('name mock');
                $company = new Company('company mock');
                $location = new Location('location mock');
                $profile = new Profile($name, $company, $location);

                $id = new Id($data[$i]->id);
                $login = new Login($data[$i]->login);
                $user = new User($id, $login, Type::GitHub(), $profile);

                array_push($result, $user);
                $cantUser += 1;
            }
            $i += 1;
        }

        $users = new Collection($result);
        return $users;
    }

    public function getByLogin(Login $name, int $limit = 0): User
    {
        // TODO: implement me
    }

    public function add(User $user): void
    {
        throw new OperationNotAllowedException();
    }

    /**
     * Retrieve users from Github
     */
    private function getDataGithub(string $config): array
    {
        $url = 'https://api.github.com/' . $config . '?q=addClass+in:file+language:js+repo:jquery/jquery';
        $cInit = curl_init();
        curl_setopt($cInit, CURLOPT_URL, $url);
        curl_setopt($cInit, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cInit, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($cInit, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $output = curl_exec($cInit);

        $info = curl_getinfo($cInit, CURLINFO_HTTP_CODE);
        $result = json_decode($output);
        curl_close($cInit);

        return $result;
    }
}
