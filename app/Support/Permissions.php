<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Central definition of every permission and the default roles. */
class Permissions
{
    /** Content types with an editorial workflow. */
    public const CONTENT = [
        'cases' => 'OCT cases',
        'articles' => 'Articles',
        'videos' => 'Videos',
        'questions' => 'Exam questions',
        'exam_topics' => 'Exam topics',
        'pages' => 'Pages',
        'courses' => 'Courses',
        'webinars' => 'Webinars',
    ];

    public const CONTENT_ACTIONS = ['view', 'create', 'update', 'delete', 'publish'];

    public const LIBRARY = [
        'media' => ['view', 'create', 'update', 'delete'],
        'taxonomy' => ['manage'],
        'profile' => ['manage'],
        'reusable_blocks' => ['manage'],
    ];

    public const SYSTEM = [
        'content.edit_others' => 'Edit and delete content created by other people',
        'menus.manage' => 'Manage navigation menus',
        'design.manage' => 'Change theme, branding, header and footer',
        'seo.manage' => 'Manage SEO settings, redirects and the 404 monitor',
        'analytics.view' => 'View analytics and reports',
        'newsletter.manage' => 'Manage subscribers and send campaigns',
        'enquiries.manage' => 'Read and answer contact enquiries',
        'users.manage' => 'Manage user accounts',
        'roles.manage' => 'Manage roles and permissions',
        'settings.manage' => 'Change website settings and email configuration',
        'activity.view' => 'View the activity log',
        'backups.manage' => 'Create, download and delete backups',
        'system.manage' => 'Maintenance mode, cache and system tools',
        'sales.view' => 'View orders, memberships, enrolments and revenue',
        'sales.manage' => 'Manage membership plans, refunds, subscriptions and payment settings',
    ];

    public const ROLES = [
        'super_admin' => 'Super administrator',
        'administrator' => 'Administrator',
        'editor' => 'Editor',
        'author' => 'Author',
        'contributor' => 'Contributor',
        'member' => 'Member (public account)',
    ];

    /** @return array<string, string> permission => human label */
    public static function all(): array
    {
        $perms = [];
        foreach (self::CONTENT as $key => $label) {
            foreach (self::CONTENT_ACTIONS as $action) {
                $perms["{$key}.{$action}"] = "{$label}: {$action}";
            }
        }
        foreach (self::LIBRARY as $key => $actions) {
            foreach ($actions as $action) {
                $perms["{$key}.{$action}"] = str($key)->replace('_', ' ')->ucfirst().": {$action}";
            }
        }

        return $perms + self::SYSTEM;
    }

    public static function defaults(): array
    {
        $all = array_keys(self::all());
        $content = fn (array $actions) => collect(self::CONTENT)->keys()->crossJoin($actions)->map(fn ($p) => implode('.', $p))->all();

        return [
            'super_admin' => $all,
            'administrator' => array_values(array_diff($all, ['roles.manage'])),
            'editor' => [
                ...$content(self::CONTENT_ACTIONS), 'content.edit_others',
                'media.view', 'media.create', 'media.update', 'media.delete', 'taxonomy.manage', 'profile.manage', 'reusable_blocks.manage',
                'menus.manage', 'seo.manage', 'analytics.view', 'newsletter.manage', 'enquiries.manage', 'activity.view', 'sales.view',
            ],
            'author' => [
                ...collect(['cases', 'articles', 'videos', 'questions'])->crossJoin(['view', 'create', 'update', 'delete', 'publish'])->map(fn ($p) => implode('.', $p))->all(),
                'media.view', 'media.create', 'media.update', 'analytics.view',
            ],
            'contributor' => [
                ...collect(['cases', 'articles', 'videos', 'questions'])->crossJoin(['view', 'create', 'update'])->map(fn ($p) => implode('.', $p))->all(),
                'media.view', 'media.create',
            ],
            'member' => [],
        ];
    }

    /** Creates missing permissions and roles. Existing role assignments are never reduced. */
    public static function sync(bool $resetRoles = false): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::all()) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (self::defaults() as $role => $perms) {
            $model = Role::findOrCreate($role, 'web');
            if ($resetRoles || $model->wasRecentlyCreated) {
                $model->syncPermissions($perms);
            } elseif ($role === 'super_admin' || $role === 'administrator') {
                $model->givePermissionTo(array_diff($perms, $model->permissions->pluck('name')->all()));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
