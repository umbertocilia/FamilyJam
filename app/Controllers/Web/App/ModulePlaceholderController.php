<?php

declare(strict_types=1);

namespace App\Controllers\Web\App;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;

final class ModulePlaceholderController extends BaseController
{
    public function show(string $householdSlug, string $moduleKey): string
    {
        if ($this->currentUserId === null || $this->activeHousehold === null) {
            throw SecurityException::forDisallowedAction();
        }

        $catalog = $this->appShell->moduleCatalog($householdSlug);

        if (! array_key_exists($moduleKey, $catalog)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $placeholder = $catalog[$moduleKey];

        return $this->render('modules/placeholder', [
            'pageClass' => 'module-page',
            'pageTitle' => $placeholder['title'] . ' | FamilyJam',
            'placeholder' => $placeholder,
        ]);
    }
}
