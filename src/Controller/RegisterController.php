<?php

declare(strict_types=1);

namespace Koabana\Controller;

use Koabana\Form\PasswordInput;
use Koabana\Model\Repository\UserRepository;
use Koabana\View\PhpTemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Koabana\Form\Form;
use Koabana\Form\TextInput;
use Koabana\Form\EmailInput;
use Koabana\Model\Entity\UserEntity;
use PHPMailer\PHPMailer\PHPMailer as Mailer;

/**
 * Contrôleur de gestion des inscriptions.
 * Gère l'inscription, la connexion et les opérations liées aux utilisateurs.
 */
final class RegisterController extends AbstractController
{
    private UserRepository $userRepository;
    private Mailer $mailer;

    public function __construct(PhpTemplateRenderer $view, UserRepository $userRepository, Mailer $mailer)
    {
        parent::__construct($view);
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
    }

    public function register(ServerRequestInterface $request, array $args): ResponseInterface
    {

        $form = new Form('register');
        $form->add(new TextInput('firstname', [
            'label' => 'Prénom',
            'required' => true,
            'infos' => 'Obligatoire, doit comporter au moins 2 caractères',
            'minLength' => 2,
        ]));

        $form->add(new TextInput('lastname', [
            'label' => 'Nom',
            'required' => true,
            'infos' => 'Obligatoire, doit comporter au moins 2 caractères',
            'minLength' => 2,
        ]));

        $form->add(new EmailInput('email', [
            'label' => 'Adresse e-mail',
            'required' => true,
            'email' => true,
            'infos' => 'Obligatoire, doit être une adresse e-mail valide',
        ]));

        $form->setCsrfToken((string) $request->getAttribute('csrf_token', ''));


        if ("POST" === $request->getMethod()) {

            $data = $request->getParsedBody() ?? [];
            $form->fill($data);

            // on verifie que l'email n'est pas déjà utilisé
            $existingUser = $this->userRepository->findByEmail(strip_tags($data['email'] ?? ''));
            if ($existingUser) {
                $this->addFlash($request, 'error', 'Cette adresse e-mail est déjà utilisée. Veuillez en choisir une autre.');
                return $this->render(
                    $request,
                    'register/register',
                    [
                        'form' => $form,
                    ],
                );
            }

            $token = bin2hex(random_bytes(16));
            $expiresAt = (new \DateTimeImmutable())->modify('+1 hour');




            if ($form->validate()) {
                $user = new UserEntity();
                $user->setFirstname(strip_tags($data['firstname']))
                     ->setLastname(strip_tags($data['lastname']))
                     ->setEmail(strip_tags($data['email']))
                     ->setToken(strip_tags($token))
                     ->setTokenExpiresAt($expiresAt);
                
                $id = $this->userRepository->create($user);

                // Tentative d'envoi de l'email d'activation
                try {
                    // Génère l'URL d'activation dynamique
                    $uri = $request->getUri();
                    $baseUrl = $uri->getScheme().'://'.$uri->getHost();
                    if (($uri->getScheme() === 'https' && $uri->getPort() !== 443) || 
                        ($uri->getScheme() === 'http' && $uri->getPort() !== 80)) {
                        $baseUrl .= ':'.$uri->getPort();
                    }
                    $activationUrl = $baseUrl.'/activate/'.$token;
                    
                    $this->mailer->addAddress(strip_tags($data['email']), strip_tags($data['firstname'].' '.$data['lastname']));
                    $this->mailer->Subject = 'Terminer votre inscription';
                    $this->mailer->isHTML(true);
                    $this->mailer->Body = '<h1>Plus qu\'une étape pour terminer votre inscription</h1>'
                        .'<p>Merci de cliquer sur le lien ci-dessous pour activer votre compte.</p>'
                        .'<p><a href="'.htmlspecialchars($activationUrl, ENT_QUOTES, 'UTF-8').'">Activer mon compte</a></p>'
                        .'<p>Si le lien ne fonctionne pas, copiez le lien dans la barre d\'url de votre navigateur : <br>'.htmlspecialchars($activationUrl, ENT_QUOTES, 'UTF-8').'</p>'
                        .'<p>Ce lien est valide pendant 1 heure.</p>';
                    $this->mailer->AltBody = 'Bienvenue sur Koabana !'."\n\n"
                        .'Plus qu\'une étape pour terminer votre inscription. Merci de cliquer sur le lien ci-dessous pour activer votre compte.'."\n\n"
                        .'Lien d\'activation : '.$activationUrl."\n\n"
                        .'Copiez le lien dans la barre d\'url de votre navigateur si le lien ne fonctionne pas.'."\n\n"
                        .'Ce lien est valide pendant 1 heure.';
                    $this->mailer->send();
                    
                    $this->addFlash($request, 'success', 'Inscription réussie ! Consultez votre email pour activer votre compte.');
                } catch (\Exception $e) {
                    
                    error_log('Erreur envoi email inscription: '.$e->getMessage());
                    $this->addFlash($request, 'warning', 'L\'email n\'a pas pu être envoyé. Contactez le support.');
                    return $this->redirect('/inscription');
                }
                
                return $this->redirect('/inscription/success');
            }
        }

        return $this->render(
            $request,
            'register/register',
            [

                'form' => $form,
            ],
        );
    }

    public function success(ServerRequestInterface $request)
    {
        return $this->render(
            $request,
            'register/success',
            []
        );
    }


    public function activate(ServerRequestInterface $request, array $args)
    {
        $csrf = $request->getAttribute('csrf_token', '');
        $token = $args['token'] ?? '';
        $user = $this->userRepository->findByToken(strip_tags($token));

        // Vérifie que le token est valide et n'a pas expiré
        if (!$user || $user->getTokenExpiresAt() < new \DateTimeImmutable()) {
           
            if ($user) {
                $user->setToken(null)->setTokenExpiresAt(null);
                $this->userRepository->update($user);
            } 
            $this->addFlash($request, 'error', 'Lien d\'activation invalide ou expiré. Veuillez recommencer l\'inscription.');
            
            
            return $this->redirect('/inscription'); 
        }


        // On efface le token et sa date d'expiration pour activer le compte
        //$user->setToken(null)->setTokenExpiresAt(null);
        //$this->userRepository->update($user);

        // On active le compte.
        $user->setIsActive(true);
        $this->userRepository->update($user);   

      

       // On prépare un formulaire de création de mot de passe pour la suite de l'inscription
        $form = new Form('set_password');
        $form->add(new PasswordInput('password', [
            'label' => 'Mot de passe',
            'required' => true,
            'infos' => 'Obligatoire, doit comporter au moins 8 caractères',
            'minLength' => 8,
        ]));
        $form->add(new PasswordInput('confirmation', [
            'label' => 'Confirmer le mot de passe',
            'required' => true,
            'infos' => 'Obligatoire, doit correspondre au mot de passe',
            'minLength' => 8,
        ]));


        $form->setCsrfToken($csrf);


        if('POST' === $request->getMethod()) {
            $data = $request->getParsedBody() ?? [];
            $form->fill($data);

            if ($form->validate()) {
                if($data['password'] !== $data['confirmation']) {
                    $this->addFieldError($request, $form, 'confirmation', 'La confirmation ne correspond pas au mot de passe');
                    return $this->render(
                        $request,
                        'register/set_password',
                        [
                            'form' => $form,
                            'userId' => $user->getId(),
                            'token' => $token,
                        ]
                    );
                }
                $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
                $user->setPasswordHash($passwordHash);
                $this->userRepository->update($user);

                //on oublie pas de supprimer le token d'activation et sa date d'expiration pour éviter toute réutilisation
                $user
                    ->setToken(null)
                    ->setTokenExpiresAt(null);

                $this->userRepository->update($user);

                $this->addFlash($request, 'success', 'Votre mot de passe a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                return $this->redirect('/connexion');
            }
        }       

        $this->addFlash($request, 'success', 'Votre compte a été activé ! Veuillez définir votre mot de passe pour finaliser votre inscription.');

        return $this->render(
            $request,
            'register/set_password',
            [
                'form' => $form,
                'userId' => $user->getId(),
                'token' => $token,
            ]
        );
    }   
}