<?php

namespace Osana\Challenge\Services\Local;

use GrahamCampbell\ResultType\Result;
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

class LocalUsersRepository implements UsersRepository
{
    public function findByLogin(Login $login, int $limit = 0): Collection
    {
        // TODO: implement me   
        $result = $this->getDataCsv('users', $login->getValue(), $limit);
        $data = new Collection($result);
        return $data;
    }

    public function getByLogin(Login $login, int $limit = 0): User
    {
        // TODO: implement me
    }

    public function add(User $user): void
    {
        // TODO: implement me
    }

    /**
     * Retrieve user data from csv file
     */
    private function getDataCsv(string $file, string $searchedLogin, int $limit = null): array
    {
        $users  = [];
        $profiles = [];
        $result = [];
        $path = '../data/' . $file . '.csv';
        $cantUser = 0;

        $fp = fopen($path, "r");

        while (($users = fgetcsv($fp, 1000, ",")) && $cantUser < $limit) {
            if (
                strncasecmp(
                    $users[1],
                    $searchedLogin,
                    strlen($searchedLogin)
                ) === 0
            ) {
                $profiles = $this->getDataCsvById('profiles', $users[0]);

                $name = new Name($profiles[1]);
                $company = new Company($profiles[2]);
                $location = new Location($profiles[3]);
                $profile = new Profile($name, $company, $location);

                $id = new Id($users[0]);
                $login = new Login($users[1]);
                $user = new User($id, $login, Type::Local(), $profile);

                array_push($result, $user);
                $cantUser += 1;
            }
        }
        fclose($fp);
        return $result;
    }

    /**
     * Retrieve profile data from csv file
     */
    private function getDataCsvById(string $file, string $id): array
    {
        $profiles = [];
        $path = '../data/' . $file . '.csv';
        $fp = fopen($path, "r");
        while ($line = fgetcsv($fp, 1000, ",")) {
            if ($line[0] == $id) {
                $profiles = $line;
            }
        }
        fclose($fp);

        return $profiles;
    }
}
