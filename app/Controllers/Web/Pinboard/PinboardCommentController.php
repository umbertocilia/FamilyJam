<?php

declare(strict_types=1);

namespace App\Controllers\Web\Pinboard;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class PinboardCommentController extends BaseController
{
    public function store(string $identifier, int $postId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['body']);

        if (! $this->validateData($payload, config('Validation')->pinboardCommentCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('pinboardCommentService')->create($this->currentUserId, $identifier, $postId, (string) $payload['body']);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('pinboard.show', $identifier, $postId))->with('success', 'Commento aggiunto.');
    }
}
