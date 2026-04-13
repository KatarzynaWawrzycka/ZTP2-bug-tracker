<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Bug;

/**
 * Interface CommentServiceInterface.
 */
interface CommentServiceInterface
{
    /**
     * Get comments for a bug.
     *
     * @param Bug $bug Bug entity
     *
     * @return Comment[] Result
     */
    public function findByBug(Bug $bug): array;

    /**
     * Save comment.
     *
     * @param Comment $comment Comment entity
     */
    public function save(Comment $comment): void;

    /**
     * Delete entity.
     *
     * @param Comment $comment Comment entity
     */
    public function delete(Comment $comment): void;
}
