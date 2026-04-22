<?php

declare(strict_types=1);

namespace Koabana\Model\Repository;
use Koabana\Model\Entity\UserEntity;

/**
 * @brief Repository pour la gestion des utilisateurs
 * 
 * Cette classe gère l'accès aux données des utilisateurs dans la base de données.
 * Elle fournit des méthodes pour rechercher des utilisateurs par différents critères.
 * 
 * @author Koabana
 * @version 1.0
 * @package Koabana\Model\Repository
 */
final class UserRepository extends AbstractRepository
{
    /**
     * @var string Nom de la table utilisateur dans la base de données
     */
    protected string $table = 'user';
    
    /**
     * @var class-string<UserEntity> Classe d'entité associée à ce repository
     */
    protected string $entityClass = UserEntity::class;

    /**
     * @brief Recherche un utilisateur par son adresse email
     * 
     * Cette méthode effectue une recherche dans la base de données pour trouver
     * un utilisateur correspondant à l'adresse email fournie.
     * 
     * @param string $email Adresse email de l'utilisateur à rechercher
     * @return UserEntity|null L'entité utilisateur si trouvée, null sinon
     * @throws \PDOException En cas d'erreur de base de données
     */
    public function findByEmail(string $email): ?UserEntity
    {
        $pdo = $this->bddFactory->getConnection();
        $sql = 'SELECT * FROM '.$this->table.' WHERE email = :email';
        $stmt = $this->statement($sql, ['email' => $email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC); 
        if (!$row) {
            return null;
        }
        return $this->hydrate($row);
    }

    /**
     * @brief Recherche un utilisateur par son token d'activation
     * 
     * Cette méthode permet de retrouver un utilisateur à partir de son token
     * d'activation unique, généralement utilisé lors de la validation de compte.
     * 
     * @param string $token Token d'activation de l'utilisateur (32 caractères hexadécimaux)
     * @return UserEntity|null L'entité utilisateur si le token est valide, null sinon
     * @throws \PDOException En cas d'erreur de base de données
     */
    public function findByToken(string $token): ?UserEntity
    {
        $pdo = $this->bddFactory->getConnection();
        $sql = 'SELECT * FROM '.$this->table.' WHERE token = :token';
        $stmt = $this->statement($sql, ['token' => $token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC); 
        if (!$row) {
            return null;
        }
        return $this->hydrate($row);
    }


    /**
     * @brief Récupère le mot de passe d'un utilisateur par son adresse email
     * 
     * Cette méthode permet d'obtenir le mot de passe (hashé) d'un utilisateur à partir de son adresse email.
     * Utile pour les processus de connexion ou de réinitialisation de mot de passe.
     * 
     * @param string $email Adresse email de l'utilisateur
     * @return string|false Le mot de passe hashé si trouvé, false sinon
     * @throws \PDOException En cas d'erreur de base de données
     */
    public function findPassword(string $email): string | false
    {
        $pdo = $this->bddFactory->getConnection();
        $sql = 'SELECT password_hash FROM '.$this->table.' WHERE email = :email';
        $stmt = $this->statement($sql, ['email' => $email]);
        return $stmt->fetchColumn(); 
    }
}