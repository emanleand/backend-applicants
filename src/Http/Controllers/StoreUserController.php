<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\Company;
use Respect\Validation\Validator as v;

class StoreUserController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            $input = json_decode($request->getBody(), true);

            if (!$this->validate($input)) {

                $response->getBody()->write(json_encode([
                    'message' => 'Bad Request'
                ]));

                return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(404, 'bad request');
            }

            #found next id
            $idAvailable = $this->localUsersRepository->getIdAvailable();
            $id = new Id('CSV' . $idAvailable);
            $login = new Login($input['login']);
            
            $name = new Name($input['profile']['name']);
            $company = new Company($input['profile']['company']);
            $location = new Location($input['profile']['location']);
            $profile = new Profile($name, $company, $location);
            
            $user = new User($id, $login, Type::Local(), $profile);            

            #insert new user | profile
            $this->localUsersRepository->add($user);

            #prepare output
            $data = [
                'id' => $id->getValue(),
                'login' => $input['login'],
                'type' => Type::Local(),
                'profile' => [
                    'name' => $input['profile']['name'],
                    'company' => $input['profile']['company'],
                    'location' => $input['profile']['location']
                ]
            ];

            $response->getBody()->write(json_encode($data));

            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(201, 'Created');
        } catch (\Throwable $th) {

            $response->getBody()->write(json_encode([
                'message' => 'Conflict'
            ]));

            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403, 'conflict');
        }
    }

    /**
     * This validates the input data
     */
    private function validate(array $input): bool
    {
        $validateLogin = v::stringType()->notEmpty();

        $validationScheme =
            v::key(
                'profile',
                v::key('name', v::stringType()->notEmpty())
                    ->key('company', v::stringType()->notEmpty())
                    ->key('location', v::stringType()->notEmpty())
            )
            ->validate($input);

        if (
            !$validateLogin->validate($input['login']) ||
            !$validationScheme
        ) {
            return false;
        }

        return true;
    }
}
