<?php

declare(strict_types=1);

namespace App\Models\Pinboard;

use App\Models\BaseModel;

final class PinboardCommentModel extends BaseModel
{
    protected $table = 'pinboard_comments';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'post_id',
        'author_user_id',
        'body',
        'deleted_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForPost(int $postId): array
    {
        return $this->select([
                'pinboard_comments.*',
                'author.display_name AS author_name',
                'author.email AS author_email',
            ])
            ->join('users AS author', 'author.id = pinboard_comments.author_user_id', 'left')
            ->where('pinboard_comments.post_id', $postId)
            ->where('pinboard_comments.deleted_at', null)
            ->orderBy('pinboard_comments.created_at', 'ASC')
            ->orderBy('pinboard_comments.id', 'ASC')
            ->findAll();
    }

    public function findForPost(int $postId, int $commentId, bool $withDeleted = false): ?array
    {
        $builder = $this->select([
                'pinboard_comments.*',
                'author.display_name AS author_name',
                'author.email AS author_email',
            ])
            ->join('users AS author', 'author.id = pinboard_comments.author_user_id', 'left')
            ->where('pinboard_comments.post_id', $postId)
            ->where('pinboard_comments.id', $commentId);

        if ($withDeleted) {
            $builder->withDeleted();
        } else {
            $builder->where('pinboard_comments.deleted_at', null);
        }

        return $builder->first();
    }
}
