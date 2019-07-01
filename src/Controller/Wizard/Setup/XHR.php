<?php

namespace App\Controller\Wizard\Setup;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class XHR extends Controller
{
    const DB_ENV_PATH_TEMPLATE = "DATABASE_URL=DB_DRIVER://DB_USER:DB_PASSWORD@DB_HOST/DB_NAME\n";
    const DB_ENV_PATH_PARAM_TEMPLATE = "env(DATABASE_URL): 'DB_DRIVER://DB_USER:DB_PASSWORD@DB_HOST/DB_NAME'\n";
    const DEFAULT_JSON_HEADERS = [
        'Content-Type' => 'application/json',
    ];

    private static $requiredExtensions = [
        [
            'name' => 'imap',
        ],
        [
            'name' => 'mailparse',
        ],
        [
            'name' => 'mysqli',
        ],
    ];

    public function evaluateSystemRequirements(Request $request)
    {
        // Evaluate system specification requirements
        switch (strtolower($request->request->get('specification'))) {
            case 'php-version':
                $response = [
                    'status' => version_compare(phpversion(), '7.0.0', '<') ? false : true,
                    'version' => sprintf('%s.%s.%s', PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION),
                ];

                if ($response['status']) {
                    $response['message'] = sprintf('Using PHP v%s', $response['version']);
                } else {
                    $response['message'] = sprintf('Currently using PHP v%s. Please use PHP 7 or greater.', $response['version']);
                }
                break;
            case 'php-extensions':
                $extensions_status = array_map(function ($extension) {
                    return [
                        $extension['name'] => extension_loaded($extension['name']),
                    ];
                }, self::$requiredExtensions);

                $response = [
                    'extensions' => $extensions_status,
                ];
                break;
            default:
                $code = 404;
                break;
        }
        
        return new Response(json_encode($response ?? []), $code ?? 200, self::DEFAULT_JSON_HEADERS);
    }

    public function verifyDatabaseCredentials(Request $request)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // unset($_SESSION['DB_CONFIG']);

        // Get entity manager
        $entityManager = EntityManager::create([
            'driver' => 'pdo_mysql',
            "host" => $request->request->get('serverName'),
            "port" => $request->request->get('port'),
            'user' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'dbname' => $request->request->get('database'),
        ], Setup::createAnnotationMetadataConfiguration(['src/Entity'], false));
        
        $databaseConnection = $entityManager->getConnection();
        $connectionResponse = [
            'status' => $databaseConnection->isConnected(),
        ];

        // Try connecting with the database if the connection is not active.
        if (false == $connectionResponse['status']) {
            try {    
                $databaseConnection->connect();

                $connectionResponse['status'] = true;

                $port = $request->request->get('port') ? ':' . $request->request->get('port') : '';
                $_SESSION['DB_CONFIG'] = [
                    'host' => $request->request->get('serverName') . $port,
                    'username' => $request->request->get('username'),
                    'password' => $request->request->get('password'),
                    'database' => $request->request->get('database'),
                ];
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Unable to connect with the database - Invalid Credentials.
            }
        }
        
        return new Response(json_encode($connectionResponse), 200, self::DEFAULT_JSON_HEADERS);
    }

    public function prepareSuperUserDetailsXHR(Request $request)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // unset($_SESSION['USER_DETAILS']);

        $_SESSION['USER_DETAILS'] = [
            'name' => $request->request->get('name'),
            'email' => $request->request->get('email'),
            'password' => $request->request->get('password'),
        ];

        return new Response(json_encode(['status' => true]), 200, self::DEFAULT_JSON_HEADERS);
    }

    public function updateConfigurationsXHR(Request $request, KernelInterface $kernel)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $database_host = $_SESSION['DB_CONFIG']['server'];
        $database_user = $_SESSION['DB_CONFIG']['username'];
        $database_pass = $_SESSION['DB_CONFIG']['password'];
        $database_name = $_SESSION['DB_CONFIG']['database'];

        $exit_code = $application->run(new ArrayInput([
            'command' => 'uvdesk-wizard:envvars:update', 
            'name' => 'DATABASE_URL', 
            'value' => sprintf("mysql://%s:%s@%s/%s", $database_user, $database_pass, $database_host, $database_name)
        ]), new NullOutput());

        if (0 === $exit_code) {
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false], 500);
    }

    public function migrateDatabaseSchemaXHR(Request $request, KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $resultCode = $application->run(new ArrayInput([
            'command' => 'uvdesk-wizard:migrate-database'
        ]), new NullOutput());
        
        return new Response(json_encode([]), 200, self::DEFAULT_JSON_HEADERS);
    }

    public function populateDatabaseEntitiesXHR(Request $request, KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $resultCode = $application->run(new ArrayInput([
            'command' => 'uvdesk-wizard:populate-database'
        ]), new NullOutput());

        return new Response(json_encode([]), 200, self::DEFAULT_JSON_HEADERS);
    }

    public function createDefaultSuperUserXHR(Request $request)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $entityManager = $this->getDoctrine()->getEntityManager();

        $role = $entityManager->getRepository('CoreFrameworkBundle:SupportRole')->findOneByCode('ROLE_SUPER_ADMIN');
        $userInstance = $entityManager->getRepository('CoreFrameworkBundle:UserInstance')->findOneBy([
            'isActive' => true,
            'supportRole' => $role,
        ]);
            
        if (empty($userInstance)) {
            list($name, $email, $password) = array_values($_SESSION['USER_DETAILS']);
            // Retrieve existing user or generate new empty user
            $accountExistsFlag = false;
            $user = $entityManager->getRepository('CoreFrameworkBundle:User')->findOneByEmail($email) ?: (new CoreEntities\User())->setEmail($email);

            if ($user->getId() != null) {
                $userInstance = $user->getAgentInstance();

                if (!empty($userInstance)) {
                    $accountExistsFlag = true;

                    if ($userInstance->getSupportRole()->getId() != $role->getId()) {
                        $userInstance->setSupportRole($role);

                        $entityManager->persist($userInstance);
                        $entityManager->flush();
                    }
                }
            } else {
                $username = explode(' ', $name, 2);
                $encodedPassword = $this->get('security.password_encoder')->encodePassword($user, $password);

                $user
                    ->setFirstName($username[0])
                    ->setLastName(!empty($username[1]) ? $username[1] : '')
                    ->setPassword($encodedPassword)
                    ->setIsEnabled(true);
                
                $entityManager->persist($user);
                $entityManager->flush();
            }
            
            if (false == $accountExistsFlag) {
                $userInstance = new CoreEntities\UserInstance();
                $userInstance->setSource('website');
                $userInstance->setIsActive(true);
                $userInstance->setIsVerified(true);
                $userInstance->setUser($user);
                $userInstance->setSupportRole($role);

                $entityManager->persist($userInstance);
                $entityManager->flush();
            }
        }

        return new Response(json_encode([]), 200, self::DEFAULT_JSON_HEADERS);
    }

    public function websiteConfigurationXHR(Request $request)
    {
        switch ($request->getMethod()) {
            case "GET":
                $currentWebsitePrefixCollection = $this->get('uvdesk.service')->getCurrentWebsitePrefixes();
                
                if ($currentWebsitePrefixCollection) {
                    $result = $currentWebsitePrefixCollection;
                    $result['status'] = true;
                } else {
                    $result['status'] = false;
                }
                break;
            case "POST":
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['PREFIXES_DETAILS'] = [
                    'member' => $request->request->get('member-prefix'),
                    'customer' => $request->request->get('customer-prefix'),
                ];

                $result = ['status' => true];
                break;
            default:
                break;
        }

        return new Response(json_encode($result ?? []), 200, self::DEFAULT_JSON_HEADERS);
    }

    public function updateWebsiteConfigurationXHR(Request $request)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $collectionURL= $this->get('uvdesk.service')->updateWebsitePrefixes(
            $_SESSION['PREFIXES_DETAILS']['member'],
            $_SESSION['PREFIXES_DETAILS']['customer']
        );

        return new Response(json_encode($collectionURL), 200, self::DEFAULT_JSON_HEADERS);
    }
}
