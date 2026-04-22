<?php

declare(strict_types=1);

namespace Koabana\Model\Entity;

/**
 * Entité de base : id, createdAt, updatedAt et helpers d'hydratation.
 */
abstract class AbstractEntity
{
<<<<<<< HEAD
    protected int $id;
=======
    protected ?int $id = null;
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
    protected ?\DateTimeImmutable $createdAt = null;
    protected ?\DateTimeImmutable $updatedAt = null;

    /**
     * @return void
     */
    public function __construct() {}

    /**
<<<<<<< HEAD
     * @return int
     */
    public function getId(): int
=======
     * Retourne l'ID de l'entité
     *
     * @return int|null L'identifiant ou null si non défini (entité non persistée)
     */
    public function getId(): ?int
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
    {
        return $this->id;
    }

    /**
     * Retourne la date de création de l'entité
     *
     * @return ?\DateTimeImmutable
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Retourne la date de mise à jour de l'entité
     *
     * @return ?\DateTimeImmutable
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Met à jour la date de mise à jour de l'entité
     *
     * @param ?\DateTimeImmutable $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Met à jour la date de création de l'entité
     *
     * @param ?\DateTimeImmutable $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
<<<<<<< HEAD
     * Met à jour l'identifiant de l'entité
     *
     * @param int $id
=======
     * Définit l'identifiant de l'entité
     *
     * @param int $id L'identifiant
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Hydrate l'entité à partir d'un tableau de données
     *
     * @param array<string, mixed> $data
     *
     * @return void
     */
    public function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set'.ucfirst($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * Retourne un tableau associatif représentant l'entité
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];
        $reflection = new \ReflectionObject($this);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            if (!$property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $data[$property->getName()] = $value;
        }

        return $data;
    }
}
