<?php

class Users extends Model
{
   public function __construct()
   {
      $this->pdo = parent::getPdo();
   }


   //-----> la fonction ckeckLogin : renvoi true si le pseudo entré par l'utilisateur est connu dans la bdd et renvoi un false si le pseudo entré par l'utilisateur est connu dans la bdd
   function checkLogin($pseudo)
   {
      $req = $this->pdo->prepare('SELECT pseudo, mdp, admin FROM users WHERE pseudo = :pseudo');
      $req->bindValue(':pseudo', $pseudo);
      $req->execute();
      $data = $req->fetch();
      return $data;
   }



   function insertUser($mail, $pseudo, $mdp, $avatar)
   {
      $req = $this->pdo->prepare("INSERT INTO users(mail, pseudo, mdp, avatar) VALUES ('$mail', '$pseudo', '$mdp', '$avatar')");
      $req->execute();
   }


   function pseudoExist($pseudo)
   {
      $req = $this->pdo->prepare("SELECT pseudo FROM users WHERE pseudo = :pseudo");
      $req->bindValue(':pseudo', $pseudo);
      $req->execute();
      //$data = $req->fetch();
      return $req->fetch();
   }

   function mailExist($mail)
   {
      $req = $this->pdo->prepare("SELECT mail FROM users WHERE mail = :mail");
      $req->bindValue(':mail', $mail);
      $req->execute();
      //$data = $req->fetch();
      return $req->fetch();
   }
}
