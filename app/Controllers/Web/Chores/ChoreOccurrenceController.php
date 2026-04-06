<?php

declare(strict_types=1);

namespace App\Controllers\Web\Chores;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class ChoreOccurrenceController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $filters = $this->request->getGet(['status', 'assigned_user_id']);
        $context = service('choreOccurrenceService')->occurrencesContext($this->currentUserId, $identifier, $filters);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/occurrences', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'Chore Occurrences | FamilyJam',
            'choreOccurrenceContext' => $context,
        ]);
    }

    public function my(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('choreOccurrenceService')->myContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/my', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'My Chores | FamilyJam',
            'myChoreContext' => $context,
        ]);
    }

    public function calendar(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('choreOccurrenceService')->calendarContext($this->currentUserId, $identifier, $this->request->getGet('start'));

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/calendar', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'Chore Agenda | FamilyJam',
            'choreCalendarContext' => $context,
        ]);
    }

    public function fairness(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('choreFairnessService')->dashboardContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/fairness', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'Chore Fairness | FamilyJam',
            'choreFairnessContext' => $context,
        ]);
    }

    public function createForTemplate(string $identifier, int $choreId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['due_at']);

        if (! $this->validateData($payload, config('Validation')->choreOccurrenceCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('choreOccurrenceService')->createOccurrenceForChore($this->currentUserId, $identifier, $choreId, (string) $payload['due_at']);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('chores.occurrences', $identifier))->with('success', 'Occorrenza chore generata.');
    }

    public function complete(string $identifier, int $occurrenceId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            service('choreOccurrenceService')->complete($this->currentUserId, $identifier, $occurrenceId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', 'Faccenda completata.');
    }

    public function skip(string $identifier, int $occurrenceId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['skip_reason']);

        if (! $this->validateData($payload, config('Validation')->choreOccurrenceSkip)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('choreOccurrenceService')->skip($this->currentUserId, $identifier, $occurrenceId, (string) $payload['skip_reason']);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', 'Faccenda segnata come skipped.');
    }
}
