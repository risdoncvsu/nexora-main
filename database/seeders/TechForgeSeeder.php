<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;

class TechForgeSeeder extends Seeder
{
    /**
     * Seed TechForge company, departments, and department accounts.
     */
    public function run(): void
    {
        // ──────────────────────────────────────────────────────────
        // 1. Create departments in the HR database
        // ──────────────────────────────────────────────────────────
        $departments = [
            ['department_name' => 'Human Resources',       'department_code' => 'HR'],
            ['department_name' => 'Information Technology', 'department_code' => 'IT'],
            ['department_name' => 'Sales & Marketing',      'department_code' => 'SALES'],
            ['department_name' => 'Finance',                'department_code' => 'FIN'],
            ['department_name' => 'Operations',             'department_code' => 'OPS'],
            ['department_name' => 'E-Commerce',             'department_code' => 'ECOM'],
            ['department_name' => 'Customer Relationship Management', 'department_code' => 'CRM'],
            ['department_name' => 'Procurement',            'department_code' => 'PROC'],
            ['department_name' => 'Manufacturing',          'department_code' => 'MFG'],
            ['department_name' => 'Inventory & Warehousing', 'department_code' => 'INV'],
            ['department_name' => 'Business Intelligence',  'department_code' => 'BI'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['department_code' => $dept['department_code']],
                $dept
            );
        }

        $this->command->info('✅ Departments seeded: '.count($departments));

        // ──────────────────────────────────────────────────────────
        // 2. Create the TechForge admin user (main app users table)
        // ──────────────────────────────────────────────────────────
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@techforge.io'],
            [
                'name'     => 'TechForge Admin',
                'username' => 'techforge_admin',
                'password' => Hash::make('TechForge@2026!'),
                'role'     => 'admin',
            ]
        );

        $this->command->info('✅ Admin user created: admin@techforge.io / TechForge@2026!');

        // ──────────────────────────────────────────────────────────
        // 3. Create the TechForge Company
        // ──────────────────────────────────────────────────────────
        $company = Company::firstOrCreate(
            ['company_email' => 'admin@techforge.io'],
            [
                'company_name'    => 'TechForge Solutions',
                'ecommerce_slug'  => 'techforge',
                'industry'        => 'Technology',
                'phone_no'        => '+1-555-0100',
                'admin_name'      => 'TechForge Admin',
                'status'          => 'Active',
                'admin_user_id'   => $adminUser->id,
                'setup_completed_at' => now(),
            ]
        );

        $this->command->info('✅ Company created: TechForge Solutions (ID: '.$company->id.')');

        // ──────────────────────────────────────────────────────────
        // 4. Link admin user to the company
        // ──────────────────────────────────────────────────────────
        if (! $adminUser->company_id) {
            $adminUser->company_id = $company->id;
            $adminUser->save();
        }

        // ──────────────────────────────────────────────────────────
        // 5. Create employee accounts for each department
        //    (stored in the HR database, linked via client_id)
        // ──────────────────────────────────────────────────────────
        $employees = [
            [
                'first_name' => 'Helena',
                'last_name'  => 'Reyes',
                'email'      => 'hr@techforge.io',
                'department' => 'Human Resources',
                'position'   => 'HR Manager',
                'phone'      => '+1-555-0101',
            ],
            [
                'first_name' => 'Derek',
                'last_name'  => 'Nakamura',
                'email'      => 'it@techforge.io',
                'department' => 'Information Technology',
                'position'   => 'IT Director',
                'phone'      => '+1-555-0102',
            ],
            [
                'first_name' => 'Samantha',
                'last_name'  => 'Chen',
                'email'      => 'sales@techforge.io',
                'department' => 'Sales & Marketing',
                'position'   => 'Sales Manager',
                'phone'      => '+1-555-0103',
            ],
            [
                'first_name' => 'Marcus',
                'last_name'  => 'Williams',
                'email'      => 'finance@techforge.io',
                'department' => 'Finance',
                'position'   => 'Finance Manager',
                'phone'      => '+1-555-0104',
            ],
            [
                'first_name' => 'Aisha',
                'last_name'  => 'Patel',
                'email'      => 'ops@techforge.io',
                'department' => 'Operations',
                'position'   => 'Operations Manager',
                'phone'      => '+1-555-0105',
            ],
            [
                'first_name' => 'Ethan',
                'last_name'  => 'Brooks',
                'email'      => 'ecommerce@techforge.io',
                'department' => 'E-Commerce',
                'position'   => 'E-Commerce Manager',
                'phone'      => '+1-555-0106',
            ],
            [
                'first_name' => 'Olivia',
                'last_name'  => 'Martinez',
                'email'      => 'crm@techforge.io',
                'department' => 'Customer Relationship Management',
                'position'   => 'CRM Director',
                'phone'      => '+1-555-0107',
            ],
            [
                'first_name' => 'James',
                'last_name'  => 'Khalil',
                'email'      => 'procurement@techforge.io',
                'department' => 'Procurement',
                'position'   => 'Procurement Manager',
                'phone'      => '+1-555-0108',
            ],
            [
                'first_name' => 'Lisa',
                'last_name'  => 'Thompson',
                'email'      => 'manufacturing@techforge.io',
                'department' => 'Manufacturing',
                'position'   => 'Manufacturing Manager',
                'phone'      => '+1-555-0109',
            ],
            [
                'first_name' => 'Raj',
                'last_name'  => 'Singh',
                'email'      => 'inventory@techforge.io',
                'department' => 'Inventory & Warehousing',
                'position'   => 'Inventory Manager',
                'phone'      => '+1-555-0110',
            ],
            [
                'first_name' => 'Yuki',
                'last_name'  => 'Tanaka',
                'email'      => 'bi@techforge.io',
                'department' => 'Business Intelligence',
                'position'   => 'BI Analyst Lead',
                'phone'      => '+1-555-0111',
            ],
        ];

        $password = 'TechForge@2026!';
        $hashedPassword = Hash::make($password);

        foreach ($employees as $data) {
            Employee::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name'         => $data['first_name'],
                    'last_name'          => $data['last_name'],
                    'department'         => $data['department'],
                    'position'           => $data['position'],
                    'phone'              => $data['phone'],
                    'company_email'      => $data['email'],
                    'client_id'          => $company->id,
                    'temporary_password' => $hashedPassword,
                    'approval_status'    => 'active',
                    'hire_date'          => now()->subMonths(6)->toDateString(),
                ]
            );
        }

        $this->command->info('✅ '.count($employees).' department employees created');
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║  🚀 TechForge seeding complete!              ║');
        $this->command->info('║                                              ║');
        $this->command->info('║  Company:    TechForge Solutions             ║');
        $this->command->info('║  Ecommerce:  techforge.techforge.localhost   ║');
        $this->command->info('║  Admin:      admin@techforge.io              ║');
        $this->command->info('║  Password:   TechForge@2026!                 ║');
        $this->command->info('║  Employees:  '.count($employees).' accounts              ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
    }
}
