# Form System

Système complet de gestion de formulaires avec validation côté serveur, hydratation d'entités et rendu HTML securise.

## Architecture

- **Form** : Orchestrateur principal (ajout champs, hydratation, validation, rendu)
- **Field** : Classe abstraite pour tous les champs (TextInput, EmailInput, etc.)
- **Validator** : Moteur de validation selon les règles de chaque champ
- **Inputs** : TextInput, EmailInput, PasswordInput, Checkbox, Textarea, Select

## Types de champs disponibles

### TextInput
Champ texte standard.

```php
$form->add(new TextInput('username', [
    'required' => true,
    'minLength' => 3,
    'maxLength' => 50,
    'class' => 'form-control',
    'placeholder' => 'Nom d\'utilisateur',
]));
```

### EmailInput
Champ email avec validation de format.

```php
$form->add(new EmailInput('email', [
    'required' => true,
    'class' => 'form-control',
]));
```

### PasswordInput
Champ mot de passe (masqué).

```php
$form->add(new PasswordInput('password', [
    'required' => true,
    'minLength' => 8,
]));
```

### Checkbox
Case à cocher.

```php
$form->add(new Checkbox('agree_terms', [
    'required' => true,
]));
```

### Textarea
Champ texte multiligne.

```php
$form->add(new Textarea('message', [
    'required' => true,
    'maxLength' => 500,
    'rows' => 5,
    'cols' => 40,
]));
```

### Select
Liste déroulante avec options.

```php
$form->add(new Select('country', [
    'fr' => 'France',
    'de' => 'Allemagne',
    'es' => 'Espagne',
], [
    'required' => true,
    'class' => 'form-control',
]));
```

## Regles de validation

Règles disponibles (passées dans les attributs) :

| Règle | Type | Exemple |
|-------|------|---------|
| `required` | bool | `'required' => true` |
| `email` | bool | `'email' => true` |
| `minLength` | int | `'minLength' => 3` |
| `maxLength` | int | `'maxLength' => 255` |
| `min` | int | `'min' => 0` |
| `max` | int | `'max' => 100` |
| `regex` | string | `'regex' => '/^[a-z]+$/i'` |

## Exemple complet

### Création et utilisation basique

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use Koabana\Form\Form;
use Koabana\Form\TextInput;
use Koabana\Form\EmailInput;
use Koabana\Form\PasswordInput;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RegisterController extends AbstractController
{
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $form = new Form('register');
        
        $form->add(new TextInput('username', [
            'required' => true,
            'minLength' => 3,
            'maxLength' => 50,
        ]));
        
        $form->add(new EmailInput('email', [
            'required' => true,
            'email' => true,
        ]));
        
        $form->add(new PasswordInput('password', [
            'required' => true,
            'minLength' => 8,
        ]));
        
        $form->setCsrfToken((string)$request->getAttribute('csrf_token', ''));
        
        return $this->render($request, 'auth/register', [
            'form' => $form,
        ]);
    }

    public function submit(ServerRequestInterface $request): ResponseInterface
    {
        $form = Form::createFromRequest('register', $request);
        
        $form->add(new TextInput('username', [
            'required' => true,
            'minLength' => 3,
            'maxLength' => 50,
        ]));
        
        $form->add(new EmailInput('email', [
            'required' => true,
            'email' => true,
        ]));
        
        $form->add(new PasswordInput('password', [
            'required' => true,
            'minLength' => 8,
        ]));
        
        // Valider
        if (!$form->validate()) {
            $this->addFlash($request, 'error', 'Erreurs dans le formulaire.');
            $errors = $form->errors();
            return $this->render($request, 'auth/register', [
                'form' => $form,
                'errors' => $errors,
            ]);
        }
        
        // Traiter les données
        $data = $form->getData();
        // ...
        
        $this->addFlash($request, 'success', 'Inscription réussie !');
        return $this->redirect('/');
    }
}
```

### Binding à une Entity

```php
<?php declare(strict_types=1);

namespace Koabana\Model\Entity;

final class User
{
    private string $username = '';
    private string $email = '';
    
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $value): void { $this->username = $value; }
    
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $value): void { $this->email = $value; }
}
```

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use Koabana\Form\Form;
use Koabana\Form\TextInput;
use Koabana\Form\EmailInput;
use Koabana\Model\Entity\User;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class UserController extends AbstractController
{
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $user = new User(); // Ou récupérée de la BDD
        
        $form = Form::fromEntity('user', $user, function(Form $form) {
            $form->add(new TextInput('username', [
                'required' => true,
                'minLength' => 3,
            ]));
            $form->add(new EmailInput('email', [
                'required' => true,
                'email' => true,
            ]));
        });
        
        $form->setCsrfToken((string)$request->getAttribute('csrf_token', ''));
        
        return $this->render($request, 'user/edit', [
            'form' => $form,
        ]);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $user = new User(); // Ou récupérée de la BDD
        
        $form = Form::createFromRequest('user', $request);
        $form->bind($user);
        
        $form->add(new TextInput('username', [
            'required' => true,
            'minLength' => 3,
        ]));
        $form->add(new EmailInput('email', [
            'required' => true,
            'email' => true,
        ]));
        
        if (!$form->validate()) {
            $this->addFlash($request, 'error', 'Erreurs de validation.');
            return $this->render($request, 'user/edit', [
                'form' => $form,
            ]);
        }
        
        // Hydrate l'entity et save
        $user = $form->getEntity();
        $userRepository->save($user);
        
        $this->addFlash($request, 'success', 'Profil mis à jour !');
        return $this->redirect('/profile');
    }
}
```

## Rendu dans les templates

### Dans une vue PHP

```php
<?php /** @var Koabana\Form\Form $form */ ?>

<?= $form->open('/submit', 'POST', ['class' => 'form-horizontal']) ?>

    <div class="form-group">
        <label>Pseudonyme</label>
        <?= $form->field('username')->render() ?>
        <?php if ($form->field('username')->hasErrors()): ?>
            <ul class="errors">
                <?php foreach ($form->field('username')->getErrors() as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label>Email</label>
        <?= $form->field('email')->render() ?>
    </div>
    
    <div class="form-group">
        <label>Message</label>
        <?= $form->field('message')->render() ?>
    </div>
    
    <div class="form-group">
        <label>
            <?= $form->field('agree')->render() ?>
            J'accepte les conditions
        </label>
    </div>
    
    <?= $form->csrf() ?>
    
    <button type="submit">Envoyer</button>

<?= $form->close() ?>
```

## Methodes courantes

### Ajouter un champ
```php
$form->add(new TextInput('name', ['required' => true]));
```

### Remplir depuis un tableau
```php
$form->fill(['name' => 'John', 'email' => 'john@example.test']);
```

### Binder à une Entity
```php
$form->bind($user);
```

### Valider
```php
if ($form->validate()) {
    // Valide
} else {
    $errors = $form->errors(); // Récupère toutes les erreurs
}
```

### Récupérer un champ
```php
$field = $form->field('email');
```

### Récupérer les données
```php
$data = $form->getData();
```

### Accéder aux erreurs
```php
$allErrors = $form->errors();                    // Tous les champs
$emailErrors = $form->errors('email');           // Un champ spécifique
$hasErrors = $form->field('email')->hasErrors();
```

### Rendu HTML
```php
echo $form->open('/submit', 'POST');
echo $form->field('email')->render();
echo $form->csrf();  // Token CSRF caché
echo $form->close();
```
