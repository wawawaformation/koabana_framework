<?php

declare(strict_types=1);

namespace Koabana\Controller;

use Koabana\Form\EmailInput;
use Koabana\Form\Form;
use Koabana\Form\PasswordInput;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Koabana\Model\Repository\UserRepository;
use Koabana\View\PhpTemplateRenderer;
use PHPMailer\PHPMailer\PHPMailer as Mailer;
use Koabana\Log\LoggerFactory as Logger;

/**
 * LoginController
 */
final class LoginController extends AbstractController
{
    private $userRepository;
    private $mailer;
    private $logger;

    public function __construct(PhpTemplateRenderer $view, UserRepository $userRepository, Mailer $mailer, Logger $logger)
    {
        parent::__construct($view);
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }
    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $form = new Form('login');

        $form->add(new EmailInput('email', [
            'required' => true,
            'email' => true,
            'class' => 'form-control',
            'placeholder' => 'John Doe <john.doe@example.com>',
            'label' => 'Adresse email (Obligatoire)',
        ]));

        $form->add(new PasswordInput('password', [
            'required' => true,
            'class' => 'form-control',

            'label' => 'Mot de passe (Obligatoire)',
        ]));

        // Récupère le token CSRF
        $csrfToken = $request->getAttribute('csrf_token', '');
        $form->setCsrfToken($csrfToken);

        if ("POST" === $request->getMethod()) {

            $data = $request->getParsedBody() ?? [];
            $form->fill($data);

            if ($form->Validate()) {
                $hash = $this->userRepository->findPassword($data['email']);
                if ($hash && password_verify($data['password'], $hash)) {
                    $user = $this->userRepository->findByEmail($data['email']);
                    if ($user) {
                        $profile = $request->getAttribute('profile');
                        if ($profile) {
                            $profile->set([
                                'user_id' => $user->getId(),
                                'user_firstname' => $user->getFirstname(),
                                'user_lastname' => $user->getLastname(),
                                'user_email' => $user->getEmail(),
                            ]);
                        }

                        $this->addFlash($request, 'success', 'Connexion réussie !');
                        return $this->redirect('/mon-espace');
                    }
                } else {
                    $this->addFlash($request, 'error', 'Email ou mot de passe incorrect.');
                }

            }
        }

        return $this->render($request, 'utilisateur/connexion', [
            'form' => $form,
        ]);
    }

    public function logout(ServerRequestInterface $request): ResponseInterface
    {
        $profile = $request->getAttribute('profile');
        $profile->clear();


        // Redirige vers la page de connexion
        $this->addFlash($request, 'success', 'Déconnexion réussie !');
        return $this->redirect('/connexion');
    }


    public function forgotPassword(ServerRequestInterface $request): ResponseInterface
    {

        $form = new Form('login');

        $form->add(new EmailInput('email', [
            'required' => true,
            'email' => true,
            'class' => 'form-control',
            'placeholder' => 'John Doe <john.doe@example.com>',
            'label' => 'Adresse email (Obligatoire)',
        ]));

        $form->setCsrfToken($request->getAttribute('csrf_token', ''));


        if ("POST" === $request->getMethod()) {

            $data = $request->getParsedBody() ?? [];
            $form->fill($data);

            if ($form->Validate()) {
                $user = $this->userRepository->findByEmail($data['email']);
                if ($user) {
                    // Générer un token de réinitialisation
                    $token = bin2hex(random_bytes(16));
                    $user->setToken($token);
                    $user->setTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
                    $this->userRepository->update($user);


                   $baseUrl = $this->getBaseUrl($request);
                    $resetUrl = $baseUrl . '/reinitialisation-mot-de-passe/' . $token;

                    try {
                        $this->mailer->setFrom('contact@koabana.fr', 'Koabana');
                        $this->mailer->addAddress(strip_tags($data['email']));
                        $this->mailer->Subject = 'Réinitialisation de votre mot de passe';
                        $this->mailer->isHTML(true);

                        $html = '<p>Vous avez perdu votre mot de passe ?<br> Cliquez sur le lien ci-dessous pour en créer un autre</p>'
                            . '<p><a href="' . $resetUrl . '">Réinitialiser mon mot de passe</a></p>'
                            . '<p>Vous pouvez aussi copier-coller ce lien dans votre navigateur : ' . $resetUrl . '</p>'
                            . '<p>Si vous n\'avez pas demandé de réinitialisation, ignorez simplement cet email.</p>';

                        $altBody = "Vous avez perdu votre mot de passe ?\n\n Cliquez sur le lien ci-dessous pour en créer un autre :\n\n " . $resetUrl . "\n\n 
                        Vous pouvez aussi copier-coller ce lien dans votre navigateur : " . $resetUrl . "\n\n
                        Si vous n'avez pas demandé de réinitialisation, ignorez simplement cet email.";

                        $this->mailer->Body = $html;
                        $this->mailer->AltBody = $altBody;
                        $this->mailer->send();

                        $this->addFlash($request, 'success', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.');
                        return $this->redirect('/connexion');

                    } catch (\Exception $e) {
                        // Gérer l'erreur d'envoi d'email

                        $this->addFlash($request, 'error', 'Erreur lors de l\'envoi de l\'email de réinitialisation.');
                        return $this->redirect('/connexion');
                    }

                    // Envoyer l'email de réinitialisation (à implémenter)
                    // sendPasswordResetEmail($user->getEmail(), $token);
                }



                $this->addFlash($request, 'success', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.');
                return $this->redirect('/connexion');
            }
        }

        return $this->render($request, '/utilisateur/mot_de_passe_oublie', [
            'form' => $form,
        ]);
    }

    public function resetPassword(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $token = $args['token'] ?? '';
        $user = $this->userRepository->findByToken(strip_tags($token));

        if (!$user || $user->getTokenExpiresAt() < new \DateTimeImmutable()) {

            if ($user) {
                $user->setToken(null)->setTokenExpiresAt(null);
                $this->userRepository->update($user);
            }
            $this->addFlash($request, 'error', 'Lien de réinitialisation invalide ou expiré. Veuillez recommencer la réinitialisation.');


            return $this->redirect('/mot-de-passe-oublie');
        }


        $form = new Form('reset_password');
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

        $form->setCsrfToken($request->getAttribute('csrf_token', ''));

        if("POST" === $request->getMethod()) {
            $data = $request->getParsedBody() ?? [];
            $form->fill($data);

            if($form->Validate()) {
                // on verifie que les deux champs de mot de passe correspondent
                if($data['password'] !== $data['confirmation']) {
                    $this->addFieldError(
                        $request, 
                        $form, 'confirmation',
                         'Le mot de passe de confirmation ne correspond pas.');
                         
                   
                }else{

                $user->setPasswordHash(password_hash($data['password'], PASSWORD_DEFAULT));
                $user->setToken(null)->setTokenExpiresAt(null);
                $this->userRepository->update($user);

                $this->addFlash($request, 'success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirect('/connexion');
// on efface le token et sa date d'expiration $user->setToken(null)->setTokenExpiresAt(null); // on met à jour le mot de passe $user->setPassword(password_hash($data['password'], PASSWORD_DEFAULT)); $this->userRepository->update($user); $this->addFlash($request, 'success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.'); return $this->redirect('/connexion'); }$user->setToken(null)->setTokenExpiresAt(null); $user->set
                }

            }
        }

        return $this->render(
            $request,
            'utilisateur/reset_password',
            [
                'form' => $form,
                'userId' => $user->getId(),
                'urlForm' => $this->getBaseUrl($request) . '/reinitialisation-mot-de-passe/' . $token,
            ]
        );

    }
}
