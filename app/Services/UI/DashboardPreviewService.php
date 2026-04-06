<?php

declare(strict_types=1);

namespace App\Services\UI;

final class DashboardPreviewService
{
    /**
     * @return array<string, mixed>
     */
    public function landingData(): array
    {
        return [
            'eyebrow' => 'Household operating system',
            'headline' => 'Spese, faccende, saldi e coordinamento quotidiano nello stesso workspace.',
            'subheadline' => 'FamilyJam nasce come web app multi-tenant per coppie, coinquilini e famiglie: isolata per household, governata da RBAC e pronta per workflow finanziari e operativi reali.',
            'stats' => [
                ['value' => '14', 'label' => 'permessi minimi pronti a seed e test'],
                ['value' => '4', 'label' => 'ruoli di sistema con supporto ruoli custom household'],
                ['value' => '100%', 'label' => 'UI responsive mobile-first con dark mode'],
            ],
            'pillars' => [
                [
                    'title' => 'Multi-tenant rigoroso',
                    'copy' => 'Ogni household isola membri, dati, impostazioni e permessi. La base service/model gia imposta query household-scoped.',
                ],
                [
                    'title' => 'RBAC centrale',
                    'copy' => 'Owner, Admin, Member e Guest hanno una permission matrix chiara, estendibile con ruoli custom.',
                ],
                [
                    'title' => 'Moduli interoperabili',
                    'copy' => 'Expenses, balances, chores, shopping, board e audit condividono servizi, transazioni e audit trail coerenti.',
                ],
            ],
            'modules' => [
                'Auth & Users',
                'Households',
                'Memberships & Invitations',
                'Roles & Permissions',
                'Expenses & Settlements',
                'Recurring Rules',
                'Chores',
                'Shopping Lists',
                'Pinboard',
                'Notifications',
                'Reports',
                'Settings',
                'Attachments',
                'Audit Log',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function workspaceData(): array
    {
        return [
            'household' => [
                'name' => 'Casa Aurora',
                'subtitle' => 'Preview foundation workspace',
                'members' => 5,
                'currency' => 'EUR',
            ],
            'summary' => [
                ['label' => 'Saldo netto aperto', 'value' => 'EUR 184,50', 'trend' => '3 rimborsi consigliati'],
                ['label' => 'Faccende in arrivo', 'value' => '7', 'trend' => '2 da completare oggi'],
                ['label' => 'Articoli shopping', 'value' => '18', 'trend' => '6 urgenti'],
                ['label' => 'Notifiche non lette', 'value' => '9', 'trend' => '3 menzioni recenti'],
            ],
            'balances' => [
                ['name' => 'Luca', 'status' => 'deve', 'amount' => 'EUR 46,20'],
                ['name' => 'Marta', 'status' => 'riceve', 'amount' => 'EUR 72,00'],
                ['name' => 'Sara', 'status' => 'deve', 'amount' => 'EUR 25,80'],
                ['name' => 'Nico', 'status' => 'in pari', 'amount' => 'EUR 0,00'],
            ],
            'chores' => [
                ['title' => 'Cucina profonda', 'meta' => 'oggi - rotazione Marta', 'badge' => 'high'],
                ['title' => 'Raccolta differenziata', 'meta' => 'domani - assegnata a Luca', 'badge' => 'medium'],
                ['title' => 'Bagno ospiti', 'meta' => 'sabato - recurring weekly', 'badge' => 'low'],
            ],
            'shopping' => [
                ['name' => 'Latte', 'meta' => '2 confezioni - pantry', 'state' => 'urgent'],
                ['name' => 'Detersivo piatti', 'meta' => '1 refill - home care', 'state' => 'open'],
                ['name' => 'Frutta', 'meta' => 'mercato del sabato', 'state' => 'open'],
            ],
            'pinboard' => [
                ['title' => 'Turni weekend di aprile', 'meta' => 'Aggiornato 2 ore fa - 4 commenti'],
                ['title' => 'Budget pranzo domenica', 'meta' => 'Nuovo pin - 2 allegati'],
            ],
            'notifications' => [
                ['title' => 'Nuova spesa in Utilities', 'detail' => 'Marta ha registrato la bolletta internet.'],
                ['title' => 'Reminder faccenda', 'detail' => 'La cucina profonda scade oggi alle 21:00.'],
                ['title' => 'Lista spesa aggiornata', 'detail' => 'Sono stati aggiunti 3 articoli urgenti.'],
            ],
            'report' => [
                'title' => 'Spese ultimi 30 giorni',
                'bars' => [
                    ['label' => 'Casa', 'value' => 82],
                    ['label' => 'Food', 'value' => 64],
                    ['label' => 'Kids', 'value' => 37],
                    ['label' => 'Leisure', 'value' => 24],
                ],
            ],
            'quickActions' => [
                'Aggiungi spesa',
                'Registra rimborso',
                'Nuova faccenda',
                'Nuovo pin',
            ],
        ];
    }
}
