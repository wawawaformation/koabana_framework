<?php

declare(strict_types=1);

namespace Koabana\Model\Entity;

/**
 * Entité représentant un utilisateur du système.
 * 
 * Cette entité gère les informations d'un utilisateur, incluant ses données personnelles,
 * son mot de passe hashé et son token d'activation de compte.
 *
 * @package Koabana\Model\Entity
 * @author  Koabana Lab
 */
final class UserEntity extends AbstractEntity
{
    /**
     * Prénom de l'utilisateur.
     *
     * @var string
     */
    private string $firstname;

    /**
     * Nom de famille de l'utilisateur.
     *
     * @var string
     */
    private string $lastname;

    /**
     * Adresse email de l'utilisateur.
     *
     * @var string
     */
    private string $email;

    /**
     * Hash du mot de passe de l'utilisateur.
     * Null si le compte n'est pas encore activé.
     *
     * @var string|null
     */
    private ?string $passwordHash = null;


    /**
     * Indique si le compte de l'utilisateur est actif (activé) ou non.
     *
     * @var bool
     */
    private bool $isActive = false;



    /**
     * Token de confirmation pour l'activation du compte.
     * Null si le compte est déjà activé.
     *œ
     * @var string|null
     */
    private ?string $token = null;

    /**
     * Date d'expiration du token d'activation.
     * Null si le compte est déjà activé ou si aucun token n'a été généré.
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $tokenExpiresAt = null;

    /**
     * Retourne le prénom de l'utilisateur.
     *
     * @return string Le prénom
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * Retourne le nom de famille de l'utilisateur.
     *
     * @return string Le nom de famille
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * Retourne l'adresse email de l'utilisateur.
     *
     * @return string L'adresse email
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Retourne le hash du mot de passe de l'utilisateur.
     *
     * @return string|null Le hash du mot de passe ou null si non défini
     */
    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }


    /**
     * Indique si le compte de l'utilisateur est actif (activé) ou non.
     *
     * @return bool True si le compte est actif, false sinon
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Retourne le token d'activation du compte.
     *
     * @return string|null Le token d'activation ou null si non défini
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Retourne la date d'expiration du token d'activation.
     *
     * @return \DateTimeImmutable|null La date d'expiration ou null si non définie
     */
    public function getTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    /**
     * Définit le prénom de l'utilisateur.
     *
     * @param string $firstname Le prénom (ne peut pas être vide)
     *
     * @return self L'instance courante pour le chaînage
     *
     * @throws \InvalidArgumentException Si le prénom est vide
     */
    public function setFirstname(string $firstname): self
    {
        if (empty(trim($firstname))) {
            throw new \InvalidArgumentException('Le prénom ne peut pas être vide');
        }
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Définit le nom de famille de l'utilisateur.
     *
     * @param string $lastname Le nom de famille (ne peut pas être vide)
     *
     * @return self L'instance courante pour le chaînage
     *
     * @throws \InvalidArgumentException Si le nom est vide
     */
    public function setLastname(string $lastname): self
    {
        if (empty(trim($lastname))) {
            throw new \InvalidArgumentException('Le nom ne peut pas être vide');
        }
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Définit l'adresse email de l'utilisateur.
     *
     * @param string $email L'adresse email (ne peut pas être vide)
     *
     * @return self L'instance courante pour le chaînage
     *
     * @throws \InvalidArgumentException Si l'email est vide
     */
    public function setEmail(string $email): self
    {
        if (empty(trim($email))) {
            throw new \InvalidArgumentException('L\'email ne peut pas être vide');
        }
        $this->email = $email;
        return $this;
    }

    /**
     * Définit le hash du mot de passe de l'utilisateur.
     *
     * @param string|null $passwordHash Le hash du mot de passe ou null pour un compte non activé
     *
     * @return self L'instance courante pour le chaînage
     */
    public function setPasswordHash(?string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }


    /**
     * Définit si le compte de l'utilisateur est actif (activé) ou non.
     *
     * @param bool $isActive True pour activer le compte, false pour le désactiver
     *
     * @return self L'instance courante pour le chaînage
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Définit le token d'activation du compte.
     *
     * @param string|null $token Le token d'activation ou null si le compte est activé
     *
     * @return self L'instance courante pour le chaînage
     */
    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Définit la date d'expiration du token d'activation.
     *
     * @param \DateTimeImmutable|null $tokenExpiresAt La date d'expiration ou null
     *
     * @return self L'instance courante pour le chaînage
     */
    public function setTokenExpiresAt(?\DateTimeImmutable $tokenExpiresAt): self
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
        return $this;
    }
}