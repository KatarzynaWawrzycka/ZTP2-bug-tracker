<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Bug;
use App\Repository\CommentRepository;

/**
 * Class CommentService.
 */
class CommentService implements CommentServiceInterface
{
    /**
     * Constructor.
     *
     * @param CommentRepository $commentRepository Comment repository
     */
    public function __construct(private readonly CommentRepository $commentRepository)
    {
    }

    /**
     * Get comments for a bug.
     *
     * @param Bug $bug Bug entity
     *
     * @return Comment[] Result
     */
    public function findByBug(Bug $bug): array
    {
        return $this->commentRepository->findByBug($bug);
    }

    /**
     * Save comment.
     *
     * @param Comment $comment Comment entity
     */
    public function save(Comment $comment): void
    {
        $this->commentRepository->save($comment);
    }

    /**
     * Delete entity.
     *
     * @param Comment $comment Comment entity
     */
    public function delete(Comment $comment): void
    {
        $this->commentRepository->delete($comment);
    }
}
