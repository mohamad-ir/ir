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
        'galleries' => 'Photo galleries',
    ];

    public const CONTENT_ACTIONS = ['view', 'create', 'update', 'delete', 'publish'];

    public const LIBRARY = [
        'media' => ['view', 'create', 'update', 'delete'],
        'taxonomy' => ['manage'],
        'profile' => ['manage'],
        'reusable_blocks' => ['manage'],
    ];

    public const SYSTEM = [
        'dashboard.access' => 'Sign in to the dashboard (two-step verification applies)',
        'content.edit_others' => 'Edit and delete content created by other people',
        'menus.manage' => 'Manage navigation menus',
        'design.manage' => 'Change theme, branding, header and footer',
        'popups.manage' => 'Create, publish and analyse popups and announcement bars',
        'seo.manage' => 'Manage SEO settings, redirects and the 404 monitor',
        'analytics.view' => 'View analytics and reports',
        'newsletter.manage' => 'Manage subscribers and send campaigns',
        'enquiries.manage' => 'Read and answer contact enquiries',
        'comments.moderate' => 'Approve, hide, pin and delete member comments',
        'users.manage' => 'Manage user accounts',
        'roles.manage' => 'Manage roles and permissions',
        'settings.manage' => 'Change website settings and email configuration',
        'activity.view' => 'View the activity log',
        'backups.manage' => 'Create, download and delete backups',
        'system.manage' => 'Maintenance mode, cache and system tools',
        'sales.view' => 'View orders, memberships, enrolments and revenue',
        'sales.manage' => 'Manage membership plans, refunds, subscriptions and payment settings',
        'content.free_access' => 'Free access to paid and members-only content (courses, webinars, membership material)',
    ];

    /** Only a super administrator may give or remove these permissions. */
    public const SUPER_ADMIN_ONLY = ['content.free_access'];

    /**
     * Permissions fixed for the built-in roles: the five team roles always sign in to the dashboard
     * and the member role never does; free access for the team roles comes from the role itself.
     */
    public static function lockedFor(string $role): array
    {
        if (! array_key_exists($role, self::ROLES)) {
            return [];
        }

        return ['dashboard.access' => $role !== 'member', 'content.free_access' => false];
    }

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
        // Free access is part of the built-in team roles themselves, never a stored permission of theirs.
        $all = array_values(array_diff(array_keys(self::all()), self::SUPER_ADMIN_ONLY));
        $content = fn (array $actions) => collect(self::CONTENT)->keys()->crossJoin($actions)->map(fn ($p) => implode('.', $p))->all();

        return [
            'super_admin' => $all,
            'administrator' => array_values(array_diff($all, ['roles.manage'])),
            'editor' => [
                'dashboard.access', ...$content(self::CONTENT_ACTIONS), 'content.edit_others',
                'media.view', 'media.create', 'media.update', 'media.delete', 'taxonomy.manage', 'profile.manage', 'reusable_blocks.manage',
                'menus.manage', 'seo.manage', 'analytics.view', 'newsletter.manage', 'enquiries.manage', 'activity.view', 'sales.view',
                'comments.moderate', 'popups.manage',
            ],
            'author' => [
                'dashboard.access', ...collect(['cases', 'articles', 'videos', 'questions', 'galleries'])->crossJoin(['view', 'create', 'update', 'delete', 'publish'])->map(fn ($p) => implode('.', $p))->all(),
                'media.view', 'media.create', 'media.update', 'analytics.view',
            ],
            'contributor' => [
                'dashboard.access', ...collect(['cases', 'articles', 'videos', 'questions', 'galleries'])->crossJoin(['view', 'create', 'update'])->map(fn ($p) => implode('.', $p))->all(),
                'media.view', 'media.create',
            ],
            'member' => [],
        ];
    }

    /**
     * Creates missing permissions and roles. Existing role assignments are never reduced, except
     * that the permissions fixed for built-in roles (see lockedFor) are always applied.
     */
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
            self::applyLocked($model);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Roles only a super administrator may assign or remove: super administrator and roles with free access. */
    public static function superAdminOnlyRoleIds(): array
    {
        return Role::query()->where('name', 'super_admin')
            ->orWhereHas('permissions', fn ($q) => $q->whereIn('name', self::SUPER_ADMIN_ONLY))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Applies the fixed permissions of a built-in role (no effect on custom roles). */
    public static function applyLocked(Role $role): void
    {
        foreach (self::lockedFor($role->name) as $permission => $granted) {
            if (! Permission::where('name', $permission)->where('guard_name', 'web')->exists()) {
                continue;
            }
            $has = $role->hasPermissionTo($permission);
            if ($granted && ! $has) {
                $role->givePermissionTo($permission);
            } elseif (! $granted && $has) {
                $role->revokePermissionTo($permission);
            }
        }
    }
}
