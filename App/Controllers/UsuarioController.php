<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\DAO\db_reservas_locais\UsuarioDAO;
use App\Model\UsuarioModel;

final class UsuarioController{
  
  private $usuarioDAO;
  
  public function __construct(){
    $this->usuarioDAO =  new UsuarioDAO();
  }

  public function getAllUsuarios(Request $request,Response $response, array $args): Response{
    $usuarios =  $this->usuarioDAO->getAllUsuarios();
    return $response->withJson($usuarios);

  }

  public function insertUsuario(Request $request,Response $response, array $args): Response{
    $data = $request->getParsedBody();
    
    $usuario = new UsuarioModel();
    
    $usuario->setIdFuncao( intval($data['id_funcao']) )
            ->setIdDepartamento(intval($data['id_departamento']))
            ->setNmUsuario(trim($data['nm_usuario']))
            ->setEmailUsuario(trim($data['email_usuario']))
            ->setTelUsuario(trim($data['tel_usuario']))
            ->setMatUsuario(intval(trim($data['matricula_usuario'])))
            ->setSenhaUsuario($data['senha_usuario'])
            ->setSenhaConfirmUsuario($data['senha_confirm']);

    $errors = $this->isValidData($usuario);
    if($errors === True){
      $usuario->setSenhaUsuario(md5($usuario->getSenhaUsuario()));
      $res =  $this->usuarioDAO->insertUsuario($usuario);
      if($res)
        return $response->withJson(['message'=>'Sua solicitação de cadastro foi feita com sucesso.']);
      return $response->withJson(['message'=>'Não foi possível realizar o cadastro.']);
    }
    return $response->withJson($errors); 
    

  }
  
  public function loginUsuario(Request $request,Response $response, array $args): Response{
    $data =  $request->getParsedBody();
    $errors =  array();
    if(empty($data['matricula_usuario']))
      array_push($errors,'O campo matrícula deve ser preenchido.');
    elseif(!intval($data['matricula_usuario']))
      array_push($errors,'A matricula é inválida');
    if(empty($data['senha_usuario']) )
      array_push($errors,'O campo senha deve ser preenchido.');

    if(count($errors)>0){
      return $response->withJson($errors);
    }else{
      $usuario = new UsuarioModel();
      $usuario->setMatUsuario(intval($data['matricula_usuario']))
              ->setSenhaUsuario(md5($data['senha_usuario']));
      $res = $this->usuarioDAO->loginUsuario($usuario);
      if($res){
        if($res['status_ativado'] == 'N')
         return $response->withJson(['message'=>'Seu cadastro se encontra em análise.']);
        return $response->withJson($res);
      }
      return $response->withJson(['message'=>'Matricula ou senha inválidos.']);
    }
  }

  public function updateUsuario(Request $request,Response $response, array $args): Response{
    $data = $request->getParsedBody();

    $usuario = new UsuarioModel();
    $usuario->setIdUsuario(intval($data['id_usuario']))
            ->setMatUsuario(intval(trim($data['matricula_usuario'])));

    $usuarioDB = $this->usuarioDAO->getUserById($usuario);
    
    if($usuarioDB !== False){
        //Atualizar os dados restantes do usuário
        $usuario->setIdFuncao( intval($data['id_funcao']) )
                ->setIdDepartamento(intval($data['id_departamento']))
                ->setNmUsuario(trim($data['nm_usuario']))
                ->setEmailUsuario(trim($data['email_usuario']))
                ->setTelUsuario(trim($data['tel_usuario']));
        
        if(array_key_exists('nv_acesso',$data))
          $usuario->setNvAcesso($data['nv_acesso']);
        if(array_key_exists('status_ativado',$data))
          $usuario->setStatusUsuario($data['status_ativado']);

        $errors = $this->isValidData($usuario,0);
        if($errors === True){
          $res =  $this->usuarioDAO->updateUsuario($usuario);
          if($res)
            return $response->withJson(['message'=>'Sua conta foi atualizada com sucesso.']);
          return $response->withJson(['message'=>'Não foi possível realizar a atualização.']);
        }
        return $response->withJson($errors); 
    }else{
      return $response->withJson(['message'=>'Usuário não encontrado.']);

    }
  
  }

  public function deleteUsuario(Request $request,Response $response, array $args): Response{
    $data =  $request->getParsedBody();
    $usuario =  new UsuarioModel();
    
    $usuario->setIdUsuario(intval($data['id_usuario']));

    if(intval($data['id_usuario']) && $this->usuarioDAO->isValidUserById($usuario)){
      $res = $this->usuarioDAO->deleteUsuario($usuario);
      if($res)
        return $response->withJson(['message'=>'Conta removida com sucesso.']);
      return $response->withJson(['message'=>'Não foi possível remover a conta cadastrada.']);

    }else{
      return $response->withJson(['message'=>'Usuário não encontrado.']);
    }

  }



  private function isValidData(UsuarioModel $usuario, $action=1){
    $errors =  array();
    $this->validateEmail($usuario,$errors);
    $this->validateMatricula($usuario,$errors);
    if(empty($usuario->getIdFuncao()))
      array_push($errors,'O campo função não foi selecionado.');
    if(empty($usuario->getIdDepartamento()))
      array_push($errors,'O campo departamento não foi selecionado.');
    if(empty($usuario->getNmUsuario()))
      array_push($errors,'O campo nome não foi preenchido.');
    if(empty($usuario->getTelUsuario()))
      array_push($errors,'O campo telefone não foi preenchido.');
    if((empty($usuario->getSenhaUsuario()) || empty($usuario->getSenhaConfirmUsuario()) ) && $action == 1)
      array_push($errors,'O(s) campo(s) senha(s) deve(m) ser preenchido(s).');
    elseif(($usuario->getSenhaUsuario() !== $usuario->getSenhaConfirmUsuario()) && $action == 1)
      array_push($errors,'As senhas não são iguais.');

    return count($errors) > 0 ? $errors : True;
  }

  private function validateEmail(UsuarioModel $usuario, &$errors){
    if(empty($usuario->getEmailUsuario()))
      array_push($errors,'O campo e-mail deve ser preenchido.');

    elseif(!filter_var($usuario->getEmailUsuario(), FILTER_VALIDATE_EMAIL))
      array_push($errors,'O e-mail preenchido não é válido.');

    elseif(!$this->usuarioDAO->isValidEmail($usuario))
        array_push($errors,'O e-mail preenchido já está em uso.');
  
  }

  private function validateMatricula(UsuarioModel $usuario, &$errors){

    if(empty($usuario->getMatUsuario()))
      array_push($errors,'O campo matrícula deve ser preenchido.');

    elseif( !filter_var($usuario->getMatUsuario(), FILTER_VALIDATE_INT))
      array_push($errors,'O campo matrícula possui valores inválidos.');
    
    elseif(!$this->usuarioDAO->isValidMatricula($usuario))
      array_push($errors,'A matrícula preenchida já está em uso.');
  }

  
}
