<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

final class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Time::now()->toDateTimeString();

        $categories = [
            ['code' => 'groceries', 'name' => 'Groceries', 'color' => '#1f9d55', 'icon' => 'basket', 'sort_order' => 10],
            ['code' => 'utilities', 'name' => 'Utilities', 'color' => '#2563eb', 'icon' => 'bolt', 'sort_order' => 20],
            ['code' => 'rent', 'name' => 'Rent', 'color' => '#7c3aed', 'icon' => 'home', 'sort_order' => 30],
            ['code' => 'internet', 'name' => 'Internet', 'color' => '#0f766e', 'icon' => 'wifi', 'sort_order' => 40],
            ['code' => 'cleaning', 'name' => 'Cleaning', 'color' => '#d97706', 'icon' => 'sparkles', 'sort_order' => 50],
            ['code' => 'transport', 'name' => 'Transport', 'color' => '#dc2626', 'icon' => 'car', 'sort_order' => 60],
            ['code' => 'entertainment', 'name' => 'Entertainment', 'color' => '#db2777', 'icon' => 'ticket', 'sort_order' => 70],
            ['code' => 'misc', 'name' => 'Misc', 'color' => '#6b7280', 'icon' => 'dots', 'sort_order' => 80],
        ];

        foreach ($categories as $category) {
            $query = $this->db->query(
                'SELECT `id` FROM `expense_categories` WHERE `household_id` IS NULL AND `code` = ? LIMIT 1',
                [$category['code']],
            );

            if ($query === false) {
                $error = $this->db->error();
                throw new \RuntimeException('Seed expense categories failed: ' . ($error['message'] ?? 'unknown database error'));
            }

            $existing = $query->getRowArray();

            $payload = [
                'household_id' => null,
                'scope_household_id' => 0,
                'code' => $category['code'],
                'name' => $category['name'],
                'color' => $category['color'],
                'icon' => $category['icon'],
                'is_system' => 1,
                'sort_order' => $category['sort_order'],
                'is_active' => 1,
                'created_by' => null,
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($existing === null) {
                $payload['created_at'] = $now;
                $this->db->table('expense_categories')->insert($payload);
                continue;
            }

            $this->db->table('expense_categories')->where('id', (int) $existing['id'])->update($payload);
        }
    }
}
