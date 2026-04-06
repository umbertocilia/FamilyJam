<?php

declare(strict_types=1);

namespace App\Controllers\Web\Pinboard;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class PinboardController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('pinboardService')->indexContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('pinboard/index', [
            'pageClass' => 'pinboard-page',
            'pageTitle' => 'Pinboard | FamilyJam',
            'pinboardIndexContext' => $context,
        ]);
    }

    public function show(string $identifier, int $postId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('pinboardService')->detailContext($this->currentUserId, $identifier, $postId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('pinboard/show', [
            'pageClass' => 'pinboard-page',
            'pageTitle' => 'Pinboard Post | FamilyJam',
            'pinboardDetailContext' => $context,
        ]);
    }

    public function create(string $identifier): string
    {
        if ($this->currentUserId === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_PINBOARD)) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('pinboardService')->formContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('pinboard/form', [
            'pageClass' => 'pinboard-page',
            'pageTitle' => 'Create Pinboard Post | FamilyJam',
            'pinboardFormContext' => $context,
            'formMode' => 'create',
        ]);
    }

    public function store(string $identifier): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['title', 'body', 'post_type', 'is_pinned', 'due_at']);

        if (! $this->validateData($payload, config('Validation')->pinboardPostCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $post = service('pinboardService')->create($this->currentUserId, $identifier, $payload, $this->request->getFile('attachment'));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('pinboard.show', $identifier, $post['id']))->with('success', 'Post creato.');
    }

    public function edit(string $identifier, int $postId): string
    {
        if ($this->currentUserId === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_PINBOARD)) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('pinboardService')->formContext($this->currentUserId, $identifier, $postId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('pinboard/form', [
            'pageClass' => 'pinboard-page',
            'pageTitle' => 'Edit Pinboard Post | FamilyJam',
            'pinboardFormContext' => $context,
            'formMode' => 'edit',
        ]);
    }

    public function update(string $identifier, int $postId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['title', 'body', 'post_type', 'is_pinned', 'due_at']);

        if (! $this->validateData($payload, config('Validation')->pinboardPostUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('pinboardService')->update($this->currentUserId, $identifier, $postId, $payload, $this->request->getFile('attachment'));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('pinboard.show', $identifier, $postId))->with('success', 'Post aggiornato.');
    }

    public function togglePin(string $identifier, int $postId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            $post = service('pinboardService')->togglePin($this->currentUserId, $identifier, $postId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('pinboard.show', $identifier, $post['id']))->with('success', 'Stato pin aggiornato.');
    }

    public function delete(string $identifier, int $postId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            service('pinboardService')->softDelete($this->currentUserId, $identifier, $postId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('pinboard.index', $identifier))->with('success', 'Post eliminato.');
    }

    public function attachment(string $identifier, int $postId, int $attachmentId): DownloadResponse
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('pinboardService')->attachmentContext($this->currentUserId, $identifier, $postId, $attachmentId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        $path = service('attachmentStorage')->absolutePath($context['attachment']);

        return $this->response
            ->download($path, null)
            ->setFileName((string) $context['attachment']['original_name']);
    }
}
