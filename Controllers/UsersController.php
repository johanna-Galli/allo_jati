<?php
class UsersController extends Controller
{
   public function __construct()
   {
      //$this->twig = parent::getTwig();
      parent::__construct();
      $this->model = new Users();
   }

   /**
    *  Affichage du template pour $slug = null (formulaire de connexion)
    */
   public function connexion($slug = null)
   {
      //$slug est null
      $title = "Connexion";

      //si slug est défini et différent de "Inscription" et différent de "MotDePasseOublie" (en gros si l'utilisateur met nimp dans l'url) alors :
      if (isset($slug) && $slug !== "MotDePasseOublie"  && $slug !== "mailEnvoye" && $slug !== "ChangerMotDePasse" && $slug !== "Inscription") {
         //Affiche une erreur 303 dans la console :
         header("HTTP/1.0 303 Redirection");

         //Fait une redirection vers la page d'accueil :
         header("Location: $this->baseUrl");
      }

      //Affichage
      $pageTwig = 'Users/login.html.twig';
      $template = $this->twig->load($pageTwig);

      echo $template->render([
         'slug' => $slug,
         "title" => $title,
      ]);
   }

   /**
    *  Gestion de l'envoi du formulaire de connexion
    */
    public function login($slug = null)
    {
       $errorMdp = null;
       $errorPseudo = null;
       $userInfo = null;
       $inputPseudo = null;
 
       //si pseudo vide
       if (empty($_POST['pseudo'])) {
          $errorPseudo = " ";
       }
 
       //si mdp vide
       if (empty($_POST['mdp'])) {
          $errorMdp = " ";
       }
 
       //si mdp vide et pseudo non vide
       if (empty($_POST['mdp']) && !empty($_POST['pseudo'])) {
          $userInfo = $this->model->checkLogin($_POST["pseudo"]);
 
          //si le pseudo est connu de la bdd
          if ($userInfo) {
             $inputPseudo = $_POST['pseudo'];
          } else {
             $inputPseudoFalse = $_POST['pseudo'];
             $errorPseudo = "Le pseudo : '$inputPseudoFalse' est inconnu de la base de donnée";
          }
       }
 
 
       // si l'input pseudo et mdp n'est pas vide
       else if (!empty($_POST['mdp']) && !empty($_POST['pseudo'])) {
 
          // $user info appelle la fonction checkLogin
          $userInfo = $this->model->checkLogin($_POST["pseudo"]);
 
          //Si $userInfo a pour valeur true
          if ($userInfo) {
             $inputPseudo = $_POST['pseudo'];
             //var_dump($userInfo);
             $hashMdp = $userInfo["mdp"];
 
             // si le mot de passe est bon
             if (password_verify($_POST['mdp'], $hashMdp)) {
 
                /*********************ANTHONY*************************/
                $instanceHome = new HomeController();
                $instanceHome->__set('utilisateur', $_POST['pseudo']);
 
                //on recherche si l'utilisateur connecté est administrateur
                $this->checkAdministrator($instanceHome->__get('utilisateur'));
 
                // Si location existe on redirige vers postAfterLogin()
                if (isset($_SESSION['location'])) {
                   $instanceComments = new CommentsController();
                   $instanceComments->postAfterLogin();
                   /****************************************************/
                } else {
                   //Sinon on redirige l'utilisateur sur la page d'accueil
                   if (!$instanceHome->__empty('utilisateur')) {
 
                      $pageTwig = 'index.html.twig';
                      $template = $this->twig->load($pageTwig);
                      echo $template->render(['status' => $_SESSION['status']]);
                      exit;
                   }
                }
             } else {
                $errorMdp = "Mot de passe incorrect";
             }
          } else {
             $errorPseudo = "Vous êtes qui ?! :S";
          }
       }
 
       $title = "Connexion";
 
       $pageTwig = 'Users/login.html.twig';
       $template = $this->twig->load($pageTwig);
       echo $template->render([
          'slug' => $slug,
          'title' => $title,
          'errorMdp' => $errorMdp,
          'errorPseudo' => $errorPseudo,
          'inputPseudo' => $inputPseudo,
       ]);
    }
   /**
    *  Gestion de l'envoi du formulaire de Mot De Passe Oublié
    */
   public function forgetPassword($slug = "MotDePasseOublie")
   {
      //déclaration des variables
      $mail = null;
      $errorMail = "";

      if (!empty($_POST)) {
         //si mail vide a l'envoi
         if (empty($_POST['mail'])) {
            $errorMail = " ";
         } else {
            $mail = $_POST['mail'];

            $userMail = $this->model->mailExist($mail);

            //mail n'existe pas dans la bdd ?
            if ($userMail == false) {
               $errorMail = "Le mail : $mail n'est pas connu de la base de donnée";
            } else {
               //on va checher le pseudo qui correspond au mail entré
               $pseudo = $this->model->recupPseudo($mail);
               //on créer un nouveau numéro aléatoire
               $randomString = $this->model->random(10);
               //on insert les nouvelles données dans la table intermédiaire
               $this->model->insertUsersIntermediar($randomString, $mail);

               var_dump($mail);
               var_dump($pseudo);
               var_dump($randomString);

               //contenu mail
               $sujet = "Mot de passe oublié";
               $mailBody = "<h2>Bonjour " . $pseudo . "!</h2><p>Vous avez demandé à changer de mot de passe.</p><br><a href='http://localhost/allo_jati/ChangerMotDePasse/$randomString'>Changer de mot de passe</a>";
            }

            //si on est en local
            if ($_SERVER['SERVER_NAME'] === "localhost") {
               //on charge Swiftmailer
               require_once('vendor/autoload.php');

               //on instancie une nouvelle méthode d'envois du mail
               $transport = (new Swift_SmtpTransport('smtp.mailtrap.io', 465))
                  //Port 25 ou 465 selon votre configuration
                  //identifiant et mote de passe pour votre swiftmailer
                  ->setUsername('fb4412351e7042')
                  ->setPassword('9377fb0dbcb0f8');
               //on instancie un nouveau mail
               $mailer = new Swift_Mailer($transport);
               //on instancie un nouveau corps de document mail
               $message = (new Swift_Message($sujet))
                  ->setFrom(['galli.johanna.g2@gmail.com'])
                  ->setTo(['galli.johanna.g2@gmail.com'])
                  ->setBody($mailBody, 'text/html');
               //on récupère et modifie le header du mail pour l'envois en HTML
               $type = $message->getHeaders()->get('Content-Type');
               $type->setValue('text/html');
               $type->setParameter('charset', 'utf-8');
               //On envois le mail en local
               $result = $mailer->send($message);

               if ($result) {
                  $slug = "mailEnvoye";
                  header("Location: $this->baseUrl/$slug");
               } else {
                  echo "Votre mail n'a pas pu être envoyé";
               }
            }
         }

         $title = "Mot de passe oublié";

         //affichage
         $pageTwig = 'Users/login.html.twig';
         $template = $this->twig->load($pageTwig);
         echo $template->render([
            'slug' => $slug,
            'title' => $title,
            'mail' => $mail,
            'errorMail' => $errorMail,
            //'randomString' => $randomString,
         ]);
      }

      //affichage
      $pageTwig = 'Users/login.html.twig';
      $template = $this->twig->load($pageTwig);
      echo $template->render([
         'slug' => $slug,
         'mail' => $mail,
         'errorMail' => $errorMail,
         //'randomString' => $randomString,
      ]);
   }


   public function mailEnvoye($slug = "mailEnvoye")
   {
      $title = "Mail envoyé";

      //affichage
      $pageTwig = 'Users/login.html.twig';
      $template = $this->twig->load($pageTwig);
      echo $template->render([
         'slug' => $slug,
         'title' => $title,
      ]);
   }

   /**
    *
    */
   public function changePassword($slug = "ChangerMotDePasse")
   {
      //déclaration des variables
      $mdp = "";
      $errorMdp = "";
      $userPseudo = "";
      $randomString = $this->model->returnUrl();

      //si le champ mdp est vide
      if (empty($_POST['mdp'])) {
         $errorMdp = " ";
      } else {
         $mdp = $_POST['mdp'];
         if (preg_match('`^([a-zA-Z0-9-_]{2,16})$`', $mdp)) {
            //fonction insérer mdp
            $this->model->updateMdp($userPseudo, $mdp);
            //message tout est ok
            $message = 'Voici un message en javascript écrit par php';
            echo '<script type="text/javascript">window.alert("' . $message . '");</script>';
         } else {
            $errorMdp = "Votre mot de passe doit contenir des lettres (en majuscule et/ou en minuscule) et/ou des chiffres. 2 - 16 caractères";
         }
      }

      $title = "Changer mot de passe";

      //affichage
      $pageTwig = 'Users/login.html.twig';
      $template = $this->twig->load($pageTwig);
      echo $template->render([
         'slug' => $slug,
         'title' => $title,
         'mdp' => $mdp,
         'errorMdp' => $errorMdp,
         'randomString' => $randomString,
      ]);
   }

   /**
    *  gestion de l'envoi du formulaire d'inscription
    */
   public function register($slug = "Inscription")
   {
      session_start();
      //déclaration des variables

      $mail = NULL;
      $mailError = NULL;
      $pseudo = NULL;
      $pseudoError = NULL;
      $mdp = NULL;
      $mdpError = NULL;
      $avatar = NULL;
      $avatarError = NULL;
      $generalError = NULL;

      if (!empty($_POST)) {
         $mail = $_POST['mail'];
         $pseudo = $_POST['pseudo'];
         $mdp = $_POST['mdp'];


         // les champs sont remplis ?
         if (!empty($mail) && !empty($pseudo) && !empty($mdp)) {

            // mail correspond aux attentes ?
            if (preg_match("#^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]{2,}\.[a-z]{2,4}$#", $mail)) {
               $userMail = $this->model->mailExist($mail);
               // mail existe dans la bdd ?
               if ($userMail == false) {

                  //pseudo correspond aux attentes ?
                  if (preg_match('`^([a-zA-Z0-9-_]{2,16})$`', $pseudo)) {
                     $userPseudo = $this->model->pseudoExist($pseudo);

                     // le pseudo entré existe dans la bdd ?
                     if ($userPseudo == false) {

                        // mot de passe correspond aux attentes ?
                        if (preg_match('`^([a-zA-Z0-9-_]{2,16})$`', $mdp)) {

                           // hashage du mot de passe :
                           $hashMdp = password_hash($mdp, PASSWORD_DEFAULT);

                           // une photo a été inséré dans l'insciption ?
                           if ($_POST['avatar']) {
                              $avatar = $_POST['avatar'];
                              $info = new SplFileInfo($avatar);
                              $extensionAvatar = $info->getExtension();
                              $extensionAvatar = strtolower($extensionAvatar);
                              $extensionsAutorisees = array('jpg', 'jpeg', 'gif', 'png', 'tiff');

                              if (in_array($extensionAvatar, $extensionsAutorisees)) {
                                 // insertion des données dans la bdd
                                 $this->model->insertUser($mail, $pseudo, $hashMdp, $avatar);
                                 header("Location: $this->baseUrl");
                              } else {
                                 $avatarError = "L'extension de votre fichier n'est pas autorisée";
                              }
                           } else {
                              // insertion des données dans la bdd
                              $this->model->insertUser($mail, $pseudo, $hashMdp, $avatar = "avatar.jpg");

                              header("Location: $this->baseUrl");
                           }
                        } else {
                           $mdpError = "Votre mot de passe doit contenir des lettres (en majuscule et/ou en minuscule) et/ou des chiffres. 2 - 16 caractères";
                        }
                     } else {
                        $pseudoError = "Ce pseudo existe déjà";
                     }
                  } else {
                     $pseudoError = "Votre pseudo doit contenir des lettres (en majuscule et/ou en minuscule) et/ou des chiffres. 2 - 36 caractères";
                  }
               } else {
                  $mailError = "Cette adresse mail possède déja un compte";
               }
            } else {
               $mailError = "L'adresse email '$mail' n'est pas considérée comme valide.";
            }
         } else {
            $generalError = "Veuillez remplir tous les champs recquis !";
         }
      }

      $title = "Inscription";

      $pageTwig = 'Users/login.html.twig';
      $template = $this->twig->load($pageTwig);
      echo $template->render([
         'slug' => $slug,
         'title' => $title,
         'generalError' => $generalError,
         'mailError' => $mailError,
         'pseudoError' => $pseudoError,
         'mdpError' => $mdpError,
         'inputMail' => $mail,
         'inputPseudo' => $pseudo,
         //'avatar' => $avatar,
         'avatarError' => $avatarError,
      ]);
   }

   /********************************ANTHONY************************************/
   /**
    *  On déconnecte la SESSION
    */
   public function logout()
   {
      $instanceHome = new HomeController();
      $instanceHome->destroy();
      header("Location: $this->baseUrl");
   }

   /**
    *
    */
   public function checkAdministrator($pseudo)
   {
      $instanceHome = new HomeController();

      //On récupère l'id utilisateur par le pseudo
      $id_user = $this->model->getOneIdUser($pseudo);
      //On vérifie si l'id utilisateur est Admin
      $admin = $this->model->checkAdmin($id_user['id_user']);

      $instanceHome = new HomeController();

      if ($admin['admin'] == 1) {
         $_SESSION['status'] = 1;
      } else {
         $_SESSION['status'] = 2;
      }
   }
}
