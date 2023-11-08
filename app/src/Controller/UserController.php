<?php

namespace App\Controller;

use Doctrine\DBAL\DriverManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Alias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsController]
  
class UserController extends AbstractController
{
    #[Route('/user')]
    public function request(Request $request): Response
    {
        $connection = $this->getConnection();
        $message = '';
        $tableExists = $this->executeRequest("SELECT * FROM information_schema.tables WHERE table_schema = 'symfony' AND table_name = 'user' LIMIT 1;");
        if (empty($tableExists)) {
            $this->createUserTable();
            $this->insertSampleData();
        }

        if ($request->isMethod('GET')) 
        {   
            $id = (int) $request->get("id");
            $action = $request->get("action");
            
            if( $action === 'delete'){
                if($id !== null && $id!==0){
                    $row = $this->getUserById($id);
                if(!empty($row)){
                    $this->deleteUser($id);
                    $message = 'Delete successfull.';
                }else{
                    $message = "User not found.";
                }
                }else{
                    $message = 'Request invalid.';
                }
                
            }
        } elseif ($request->isMethod('POST')) {
            $firstname = $request->get('firstname');
            $lastname = $request->get('lastname');
            $address = $request->get('address');
            if($firstname!=='' && $lastname !== '' && $address !== ''){
				#validate form
				#...
				#end validate form
                $checkallow = $this->filterSpecialcharacters($firstname.$lastname.$address);
                if($checkallow){
                    $row = $this->getUserByData($firstname, $lastname, $address);
                    if($row){
                        $message = 'User is exist';
                    }else{
                        $this->insertUser( $firstname, $lastname, $address);
                    
                        $message = 'Add new user successful.';
                    }
                }else{
                    $message = 'Do not use special characters, please.';
                }
                
            }else{
                $message = "Please enter complete data";
            }
            
            
        }

        $users = $this->getUsers();

        return $this->render('user.html.twig', [
            'obj' => $request->getMethod(),
            'users' => $users,
            'message' => $message
        ]);
    }
    private function filterSpecialcharacters($string){
        $pattern = "/^[a-zA-Z0-9_ ]*$/";       
        return preg_match($pattern, $string);
        
    }
    private function createUserTable()
    {
        $sql = "CREATE TABLE user (id INT AUTO_INCREMENT, firstname VARCHAR(255), lastname VARCHAR(255), address VARCHAR(255), PRIMARY KEY (id));";
        $this->executeRequest($sql);
    }

    private function insertSampleData()
    {
        $data = [
            ['firstname' => 'Barack', 'lastname' => 'Obama', 'address' => 'White House'],
            ['firstname' => 'Britney', 'lastname' => 'Spears', 'address' => 'America'],
            ['firstname' => 'Leonardo', 'lastname' => 'DiCaprio', 'address' => 'Titanic'],
        ];

        foreach ($data as $row) {
            $this->insertUser($row['firstname'], $row['lastname'], $row['address']);
        }
    }

    private function insertUser($firstname, $lastname, $address)
    {
        $sql = "INSERT INTO user (firstname, lastname, address) VALUES ('".$firstname."', '".$lastname."', '".$address."')";
        
        return $this->executeRequest($sql);
    
    }

    private function getUserById($id)
    {
        return $this->executeRequest("SELECT * FROM user WHERE id = ".$id);
    }
    private function getUserByData($firstname, $lastname, $address){
        $sql = "SELECT * FROM user WHERE firstname = '".$firstname."' and lastname = '".$lastname."' and ";
        $sql .= " address = '".$address."'";
        return $this->executeRequest($sql);
    }
    private function deleteUser($id)
    {   
        $this->executeRequest("DELETE FROM user WHERE id = " . $id);
        $this->addFlash('success', 'User has been successfully deleted.');

        return $this->redirectToRoute('user_request');
    }

    private function getUsers()
    {
        return $this->executeRequest("SELECT * FROM user;");
    }

    private function getConnection()
    {
        $connectionParams = [
            'dbname' => 'symfony',
            'user' => 'symfony',
            'password' => '',
            'host' => 'mariadb',
            'driver' => 'pdo_mysql',
        ];
        return DriverManager::getConnection($connectionParams);
    }

    private function executeRequest($sql)
    {
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}