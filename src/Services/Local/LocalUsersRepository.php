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
        $newUser = [
            $user->getId()->getValue(),
            $user->getLogin()->getValue(),
            $user->getType()->getValue()
        ];
        
        $this->insertDataLocal('users', $newUser);
        
        try {
            $profiles = [
                $user->getId()->getValue(),
                $user->getProfile()->getName()->getValue(),
                $user->getProfile()->getCompany()->getValue(),
                $user->getProfile()->getLocation()->getValue()
            ];

            $this->insertDataLocal('profiles', $profiles);
        } catch (\Throwable $th) {
            //Rollback in table users.csv
        }
    }

    /**
     * This function look for an available id in the local file
     */
    public function getIdAvailable(string $file = 'users'): int
    {
        # Get last line
        $path = '../data/' . $file . '.csv';
        $line = '';

        $fp = fopen($path, 'r');
        $cursor = -1;

        fseek($fp, $cursor, SEEK_END);
        $char = fgetc($fp);


        while ($char === "\n" || $char === "\r") {
            fseek($fp, $cursor--, SEEK_END);
            $char = fgetc($fp);
        }

        while ($char !== false && $char !== "\n" && $char !== "\r") {

            $line = $char . $line;
            fseek($fp, $cursor--, SEEK_END);
            $char = fgetc($fp);
        }

        fclose($fp);
        #Get column id
        $idExplode = explode(',', $line);
        $idColumn = $idExplode[0];

        #Calculate new id
        try {
            $id = (int) explode('CSV', $idColumn)[1];
        } catch (\Throwable $th) {
            $id = 0;
        }

        return $id + 1;
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

    /**
     * This inserts a new record locally
     */
    private function insertDataLocal(string $file, array $newUser): void
    {
        $path = '../data/' . $file . '.csv';
        $fp = fopen($path, "a");
        
        fputcsv($fp, $newUser);
        
        fclose($fp);
    }
}
