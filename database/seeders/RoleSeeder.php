<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = [
            'submit-article',
            'review-article',
            'manage-section',
            'manage-issue',
            'publish-issue',
            'manage-users',
            'manage-settings',
            'manage-content',
            'manage-submissions',
            'manage-doi',
        ];

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Shared by editor-in-chief and managing-editor (full editorial leadership)
        $editorialLeadershipPermissions = [
            'submit-article',
            'review-article',
            'manage-section',
            'manage-issue',
            'publish-issue',
            'manage-submissions',
            'manage-doi',
        ];

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($allPermissions);

        $editorInChief = Role::firstOrCreate(['name' => 'editor-in-chief']);
        $editorInChief->syncPermissions($editorialLeadershipPermissions);

        $managingEditor = Role::firstOrCreate(['name' => 'managing-editor']);
        $managingEditor->syncPermissions($editorialLeadershipPermissions);

        $contentManager = Role::firstOrCreate(['name' => 'content-manager']);
        $contentManager->syncPermissions(['manage-content']);

        $sectionEditor = Role::firstOrCreate(['name' => 'section-editor']);
        $sectionEditor->syncPermissions([
            'submit-article',
            'manage-section',
            'review-article',
            'manage-submissions',
        ]);

        $reviewer = Role::firstOrCreate(['name' => 'reviewer']);
        $reviewer->syncPermissions(['review-article']);

        $author = Role::firstOrCreate(['name' => 'author']);
        $author->syncPermissions(['submit-article']);
    }
}
