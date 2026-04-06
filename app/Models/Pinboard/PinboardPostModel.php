<?php

declare(strict_types=1);

namespace App\Models\Pinboard;

use App\Models\TenantScopedModel;

final class PinboardPostModel extends TenantScopedModel
{
    protected $table = 'pinboard_posts';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'author_user_id',
        'title',
        'body',
        'post_type',
        'is_pinned',
        'due_at',
        'deleted_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId): array
    {
        return $this->baseDetailQuery()
            ->select('(SELECT COUNT(*) FROM pinboard_comments WHERE pinboard_comments.post_id = pinboard_posts.id AND pinboard_comments.deleted_at IS NULL) AS comments_count', false)
            ->where('pinboard_posts.household_id', $householdId)
            ->where('pinboard_posts.deleted_at', null)
            ->orderBy('pinboard_posts.is_pinned', 'DESC')
            ->orderBy('pinboard_posts.due_at', 'ASC')
            ->orderBy('pinboard_posts.created_at', 'DESC')
            ->findAll();
    }

    public function findDetailForHousehold(int $householdId, int $postId, bool $withDeleted = false): ?array
    {
        $builder = $this->baseDetailQuery()
            ->where('pinboard_posts.household_id', $householdId)
            ->where('pinboard_posts.id', $postId);

        if ($withDeleted) {
            $builder->withDeleted();
        } else {
            $builder->where('pinboard_posts.deleted_at', null);
        }

        return $builder->first();
    }

    private function baseDetailQuery()
    {
        return $this->select([
                'pinboard_posts.*',
                'author.display_name AS author_name',
                'author.email AS author_email',
            ])
            ->join('users AS author', 'author.id = pinboard_posts.author_user_id', 'left');
    }
}
